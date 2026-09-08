<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Resolution review workflow columns.
 *
 * A violation that an inspector submits for review must record WHO submitted
 * the resolution (separation of duties: the submitter may never approve their
 * own resolution), WHAT the resolution note was, and WHO closed the violation
 * after review approval. The reviewers themselves are already covered by
 * reviewed_by/reviewed_at; completed_at doubles as the closed timestamp.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('visitors_capa_actions', function (Blueprint $table) {
            $table->foreignId('submitted_by')->nullable()
                ->after('submitted_review_at')
                ->constrained('users')
                ->noActionOnDelete();
            $table->text('resolution_note')->nullable()->after('submitted_by');
            $table->foreignId('closed_by')->nullable()->after('reviewed_by')
                ->constrained('users')
                ->noActionOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('visitors_capa_actions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropColumn('resolution_note');
            $table->dropConstrainedForeignId('closed_by');
        });
    }
};