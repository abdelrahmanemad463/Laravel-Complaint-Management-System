<?php

use App\Services\Visitors\DueDateService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the free-text `deadline` concept with a numeric `period_hours`
 * value (in hours, decimals allowed) so due dates can be computed precisely:
 *   due_date = action.created_at + period_hours      (period_hours > 0)
 *   due_date = NULL                                  (period_hours = 0 -> Immediate)
 *
 * The period is snapshotted on checklist items, visit items and CAPA actions.
 * Historical records keep the period that was in force when they were created;
 * legacy `deadline`/`due_date` values are migrated once here and are never
 * recomputed against later master-data changes.
 */
return new class extends Migration {
    public function up(): void
    {
        // 1. period_hours on the master checklist items.
        Schema::table('visitors_checklist_items', function (Blueprint $table) {
            $table->decimal('period_hours', 8, 2)->nullable()->after('responsible');
        });

        // 2. period_hours snapshot on the visit items.
        Schema::table('visitors_visit_items', function (Blueprint $table) {
            $table->decimal('period_hours', 8, 2)->nullable()->after('responsible');
        });

        // 3. period_hours snapshot + high-precision due_at on CAPA actions.
        Schema::table('visitors_capa_actions', function (Blueprint $table) {
            $table->decimal('period_hours', 8, 2)->nullable()->after('due_date');
            $table->timestamp('due_at')->nullable()->after('period_hours');
            $table->index('due_at');
        });

        $this->migrateExistingData();

        // 4. The old free-text column is fully replaced.
        Schema::table('visitors_checklist_items', function (Blueprint $table) {
            $table->dropColumn('deadline');
        });
        Schema::table('visitors_visit_items', function (Blueprint $table) {
            $table->dropColumn('deadline');
        });
    }

    public function down(): void
    {
        Schema::table('visitors_visit_items', function (Blueprint $table) {
            $table->string('deadline')->nullable();
        });
        Schema::table('visitors_checklist_items', function (Blueprint $table) {
            $table->string('deadline')->nullable();
        });

        Schema::table('visitors_capa_actions', function (Blueprint $table) {
            $table->dropIndex(['due_at']);
            $table->dropColumn('due_at');
            $table->dropColumn('period_hours');
        });

        Schema::table('visitors_visit_items', function (Blueprint $table) {
            $table->dropColumn('period_hours');
        });
        Schema::table('visitors_checklist_items', function (Blueprint $table) {
            $table->dropColumn('period_hours');
        });
    }

    /**
     * Convert existing text deadlines into period_hours and backfill the CAPA
     * due dates. Runs idempotently so it is safe on both fresh and existing
     * databases.
     */
    private function migrateExistingData(): void
    {
        Schema::table('visitors_checklist_items', function (Blueprint $table) {
            $hasDeadline = Schema::hasColumn('visitors_checklist_items', 'deadline');
            $hasPeriodHours = Schema::hasColumn('visitors_checklist_items', 'period_hours');
            if (!$hasDeadline || !$hasPeriodHours) {
                return;
            }
            $items = DB::table('visitors_checklist_items')
                ->whereNotNull('deadline')
                ->where('deadline', '!=', '')
                ->whereNull('period_hours')
                ->select('id', 'deadline')
                ->get();
            foreach ($items as $item) {
                $hours = $this->toHours($item->deadline);
                if ($hours !== null) {
                    DB::table('visitors_checklist_items')->where('id', $item->id)->update(['period_hours' => $hours]);
                }
            }
        });

        Schema::table('visitors_visit_items', function (Blueprint $table) {
            $hasDeadline = Schema::hasColumn('visitors_visit_items', 'deadline');
            $hasPeriodHours = Schema::hasColumn('visitors_visit_items', 'period_hours');
            if (!$hasDeadline || !$hasPeriodHours) {
                return;
            }
            $items = DB::table('visitors_visit_items')
                ->whereNotNull('deadline')
                ->where('deadline', '!=', '')
                ->whereNull('period_hours')
                ->select('id', 'deadline')
                ->get();
            foreach ($items as $item) {
                $hours = $this->toHours($item->deadline);
                if ($hours !== null) {
                    DB::table('visitors_visit_items')->where('id', $item->id)->update(['period_hours' => $hours]);
                }
            }
        });

        Schema::table('visitors_capa_actions', function (Blueprint $table) {
            $hasPeriodHours = Schema::hasColumn('visitors_capa_actions', 'period_hours');
            $hasDueAt = Schema::hasColumn('visitors_capa_actions', 'due_at');
            if (!$hasPeriodHours || !$hasDueAt) {
                return;
            }

            $actions = DB::table('visitors_capa_actions')
                ->whereNull('period_hours')
                ->select('id', 'visit_item_id', 'created_at', 'due_date', 'due_at')
                ->get();
            foreach ($actions as $action) {
                $hours = null;
                if ($action->visit_item_id !== null) {
                    $itemHours = DB::table('visitors_visit_items')
                        ->where('id', $action->visit_item_id)
                        ->value('period_hours');
                    if ($itemHours !== null) {
                        $hours = (float) $itemHours;
                    }
                }

                $base = $action->created_at ? \Carbon\Carbon::parse($action->created_at) : now();
                $dueAt = null;
                if ($hours !== null && $hours > 0) {
                    $dueAt = DueDateService::dueDate((float) $hours, $base);
                }

                // Legacy actions had a date-only due date with no period
                // snapshot; preserve a best-effort due timestamp for those.
                if ($dueAt === null && $action->due_date !== null && $base !== null) {
                    $dueAt = \Carbon\Carbon::parse($action->due_date)->startOfDay();
                }

                DB::table('visitors_capa_actions')->where('id', $action->id)->update([
                    'period_hours' => $hours,
                    'due_at' => $dueAt?->format('Y-m-d H:i:s'),
                ]);
            }
        });
    }

    private function toHours(mixed $value): ?float
    {
        try {
            return DueDateService::periodToHours($value);
        } catch (\Throwable) {
            return null;
        }
    }
};