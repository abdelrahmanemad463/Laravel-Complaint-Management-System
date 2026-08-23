<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_id')->constrained('complaint_sources')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('complaint_categories')->restrictOnDelete();
            $table->foreignId('type_id')->constrained('complaint_types')->restrictOnDelete();
            $table->foreignId('priority_id')->constrained()->restrictOnDelete();
            $table->foreignId('status_id')->constrained('complaint_statuses')->restrictOnDelete();
            $table->string('short_description'); $table->text('description'); $table->date('complaint_date')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable(); $table->text('resolution')->nullable();
            $table->timestamps(); $table->softDeletes();
            $table->index(['branch_id', 'complaint_date']); $table->index(['status_id', 'complaint_date']);
        });
    }
    public function down(): void { Schema::dropIfExists('complaints'); }
};
