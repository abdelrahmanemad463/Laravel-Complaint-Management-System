<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('visitors_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_type_id')->constrained('visitors_visit_types')->noActionOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->noActionOnDelete();
            $table->foreignId('inspector_id')->constrained('users')->noActionOnDelete();
            $table->date('visit_date')->index();
            $table->string('status')->default('in_progress')->index(); // in_progress | completed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['inspector_id', 'status']);
            $table->index(['branch_id', 'visit_date']);
        });
        Schema::create('visitors_visit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('visitors_visits')->cascadeOnDelete();
            $table->foreignId('checklist_item_id')->constrained('visitors_checklist_items')->noActionOnDelete();
            $table->string('status')->default('pending'); // pending | ok | nc | na
            $table->timestamp('visited_at')->nullable();
            $table->foreignId('root_cause_id')->nullable()->constrained('visitors_root_causes')->noActionOnDelete();
            $table->text('note')->nullable();
            $table->boolean('main_kitchen')->default(false);
            $table->string('support_department')->nullable();
            // Snapshot of the checklist configuration at visit time
            $table->string('item_code');
            $table->string('item_title');
            $table->string('section_name');
            $table->string('severity');
            $table->unsignedInteger('deduction_score')->default(0);
            $table->boolean('photo_required')->default(false);
            $table->text('immediate_action')->nullable();
            $table->text('corrective_action')->nullable();
            $table->text('preventive_action')->nullable();
            $table->string('responsible')->nullable();
            $table->string('deadline')->nullable();
            $table->timestamps();
            $table->index('visit_id');
            $table->index('checklist_item_id');
            $table->unique(['visit_id', 'checklist_item_id']);
        });
        Schema::create('visitors_visit_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('visitors_visits')->cascadeOnDelete();
            $table->foreignId('visit_item_id')->constrained('visitors_visit_items')->noActionOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('original_size')->default(0);
            $table->unsignedBigInteger('compressed_size')->default(0);
            $table->timestamps();
            $table->index('visit_id');
            $table->index('visit_item_id');
        });
        Schema::create('visitors_capa_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('visitors_visits')->cascadeOnDelete();
            $table->foreignId('visit_item_id')->constrained('visitors_visit_items')->noActionOnDelete();
            $table->string('title');
            $table->text('immediate_action')->nullable();
            $table->text('corrective_action')->nullable();
            $table->text('preventive_action')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->noActionOnDelete();
            $table->date('due_date')->nullable();
            $table->string('status')->default('open'); // open | in_progress | overdue | closed | rejected
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->noActionOnDelete();
            $table->timestamps();
            $table->index('visit_id');
            $table->index('status');
        });
        Schema::create('visitors_capa_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capa_action_id')->constrained('visitors_capa_actions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status');
            $table->text('comment')->nullable();
            $table->string('photo')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('capa_action_id');
        });
    }
    public function down(): void {
        Schema::dropIfExists('visitors_capa_updates');
        Schema::dropIfExists('visitors_capa_actions');
        Schema::dropIfExists('visitors_visit_photos');
        Schema::dropIfExists('visitors_visit_items');
        Schema::dropIfExists('visitors_visits');
    }
};
