<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('complaint_status_histories', function (Blueprint $table) {
            $table->id(); $table->foreignId('complaint_id')->constrained()->restrictOnDelete();
            $table->foreignId('from_status_id')->nullable()->constrained('complaint_statuses')->restrictOnDelete();
            $table->foreignId('to_status_id')->constrained('complaint_statuses')->restrictOnDelete();
            $table->text('reason')->nullable(); $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('changed_at')->index(); $table->timestamps();
        });
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index(); $table->nullableMorphs('subject'); $table->text('description')->nullable();
            $table->json('old_values')->nullable(); $table->json('new_values')->nullable(); $table->timestamp('created_at')->useCurrent();
            $table->index('created_at');
        });
    }
    public function down(): void { Schema::dropIfExists('activity_logs'); Schema::dropIfExists('complaint_status_histories'); }
};
