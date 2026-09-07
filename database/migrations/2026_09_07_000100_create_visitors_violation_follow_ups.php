<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Follow-up Previous Violations in a New Visit.
 *
 * A follow-up is a separate event recorded during a later visit. It references
 * one or more previous violations (the sane eligibility rules live in
 * ViolationFollowUpService) through a dedicated pivot, and never modifies the
 * historical visit/report snapshot.
 *
 * 1. visitors_violation_follow_ups — one record per follow-up action:
 *    linked to the current visit + inspection item, the performer, an
 *    optional note, an optional result (resolved | still_open) and when it
 *    was performed. The original violations stay untouched.
 * 2. visitors_violation_follow_up_items — pivot connecting a follow-up to
 *    every previous violation it addressed (one follow-up can reference
 *    several violations, e.g. #45 + #52).
 * 3. visitors_visit_photos gains an optional violation_follow_up_id so
 *    follow-up evidence photos are attributed to the follow-up record.
 *
 * SQL Server cascade-safety: only visit_id cascades; all other FKs use
 * noActionOnDelete exactly like the existing visitors transaction tables.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('visitors_violation_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('visitors_visits')->cascadeOnDelete();
            $table->foreignId('visit_item_id')->constrained('visitors_visit_items')->noActionOnDelete();
            $table->foreignId('performed_by_id')->constrained('users')->noActionOnDelete();
            $table->text('follow_up_note')->nullable();
            $table->string('result')->default('resolved'); // resolved | still_open
            $table->timestamp('followed_up_at')->nullable();
            $table->timestamps();
            $table->index('visit_id');
            $table->index('visit_item_id');
        });

        Schema::create('visitors_violation_follow_up_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('violation_follow_up_id')->constrained('visitors_violation_follow_ups')->cascadeOnDelete();
            $table->foreignId('capa_action_id')->constrained('visitors_capa_actions')->noActionOnDelete();
            $table->timestamps();
            $table->unique(['violation_follow_up_id', 'capa_action_id']);
            $table->index('capa_action_id');
        });

        Schema::table('visitors_visit_photos', function (Blueprint $table) {
            $table->foreignId('violation_follow_up_id')->nullable()
                ->after('evidence_role')
                ->constrained('visitors_violation_follow_ups')
                ->noActionOnDelete();
            $table->index('violation_follow_up_id');
        });
    }

    public function down(): void
    {
        Schema::table('visitors_visit_photos', function (Blueprint $table) {
            $table->dropIndex(['violation_follow_up_id']);
            $table->dropConstrainedForeignId('violation_follow_up_id');
        });

        Schema::dropIfExists('visitors_violation_follow_up_items');
        Schema::dropIfExists('visitors_violation_follow_ups');
    }
};