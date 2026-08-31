<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Adds the master-data management supporting tables for the Quality Visits
     * Excel import feature.
     *
     * The existing master tables are reused as the canonical runtime source:
     *   - visitors_visit_types   -> inspection types  (daily / monthly / occupational_safety)
     *   - visitors_sections      -> inspection sections
     *   - visitors_checklist_items -> inspection items (code + type unique)
     *   - visitors_root_causes   -> root-cause master
     *
     * This migration adds the severity master and the auditable Excel import
     * history tables plus a suggested default root cause on each checklist item.
     */
    public function up(): void
    {
        Schema::create('visitors_severities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('visitors_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->noActionOnDelete();
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('extension', 10)->nullable();
            // pending | validating | ready | imported | failed | cancelled
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->unsignedInteger('created_records')->default(0);
            $table->unsignedInteger('updated_records')->default(0);
            $table->unsignedInteger('failed_records')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index('user_id');
        });

        Schema::create('visitors_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('visitors_imports')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('code')->nullable();
            $table->string('inspection_type')->nullable();
            $table->boolean('valid')->default(false);
            $table->text('errors')->nullable();
            $table->text('data')->nullable();
            $table->boolean('created')->default(false);
            $table->boolean('updated')->default(false);
            $table->timestamps();
            $table->index('import_id');
            $table->index('valid');
        });

        // Suggested/default root cause for a checklist item (validated against
        // visitors_root_causes on import). The inspector still chooses freely
        // at Non-Compliant time; snapshots keep the visit immutable.
        Schema::table('visitors_checklist_items', function (Blueprint $table) {
            $table->foreignId('root_cause_id')->nullable()
                ->after('severity')
                ->constrained('visitors_root_causes')
                ->noActionOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('visitors_checklist_items', function (Blueprint $table) {
            $table->dropForeign(['root_cause_id']);
            $table->dropColumn('root_cause_id');
        });
        Schema::dropIfExists('visitors_import_rows');
        Schema::dropIfExists('visitors_imports');
        Schema::dropIfExists('visitors_severities');
    }
};
