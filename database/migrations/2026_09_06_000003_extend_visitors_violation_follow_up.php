<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the visitors tables to support the two-report workflow:
 *
 * Inspection Report (immutable, visitors_visits/visitors_visit_items) vs
 * Corrective Actions / Follow-up Report (dynamic, driven by the violation
 * records in visitors_capa_actions).
 *
 * No new tables: the existing visitors_capa_actions is the "violation"
 * entity, visitors_capa_updates is its follow-up timeline, and the photo
 * table gains the linkage needed to distinguish initial vs resolution
 * evidence.
 *
 * 1. visitors_capa_actions gains the Pending Review lifecycle signals:
 *    submitted_review_at (when the inspector submitted resolution for
 *    review) and reject_reason (why a reviewer bounced it back).
 * 2. visitors_visit_items records which follow-up action an inspector chose
 *    on the inspection form when an existing open violation was detected:
 *    still_open | resolved | new_violation, plus the linked open violation.
 * 3. visitors_visit_photos distinguishes initial evidence (uploads on the
 *    inspection form) from resolution evidence (uploads for a follow-up),
 *    and links resolution evidence to its violation.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('visitors_capa_actions', function (Blueprint $table) {
            $table->timestamp('submitted_review_at')->nullable()->after('reviewed_at');
            $table->text('reject_reason')->nullable()->after('submitted_review_at');
        });

        Schema::table('visitors_visit_items', function (Blueprint $table) {
            $table->string('follow_up_action')->nullable()->after('period_hours');
            $table->foreignId('linked_capa_action_id')->nullable()
                ->after('follow_up_action')
                ->constrained('visitors_capa_actions')
                ->noActionOnDelete();
            $table->index('linked_capa_action_id');
        });

        Schema::table('visitors_visit_photos', function (Blueprint $table) {
            $table->foreignId('capa_action_id')->nullable()
                ->after('visit_item_id')
                ->constrained('visitors_capa_actions')
                ->noActionOnDelete();
            $table->string('evidence_role')->default('initial')->after('capa_action_id'); // initial | resolution
            $table->index('capa_action_id');
        });
    }

    public function down(): void
    {
        Schema::table('visitors_visit_photos', function (Blueprint $table) {
            $table->dropIndex(['capa_action_id']);
            $table->dropConstrainedForeignId('capa_action_id');
            $table->dropColumn('evidence_role');
        });

        Schema::table('visitors_visit_items', function (Blueprint $table) {
            $table->dropIndex(['linked_capa_action_id']);
            $table->dropConstrainedForeignId('linked_capa_action_id');
            $table->dropColumn('follow_up_action');
        });

        Schema::table('visitors_capa_actions', function (Blueprint $table) {
            $table->dropColumn(['submitted_review_at', 'reject_reason']);
        });
    }
};