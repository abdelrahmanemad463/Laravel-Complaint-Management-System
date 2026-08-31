<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('visitors_visit_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
        Schema::create('visitors_sections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
        Schema::create('visitors_root_causes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
        Schema::create('visitors_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_type_id')->constrained('visitors_visit_types')->noActionOnDelete();
            $table->foreignId('section_id')->constrained('visitors_sections')->noActionOnDelete();
            $table->string('code');
            $table->string('title');
            $table->string('severity'); // critical | major | minor
            $table->unsignedInteger('deduction_score')->default(0);
            $table->boolean('photo_required')->default(false);
            $table->text('immediate_action')->nullable();
            $table->text('corrective_action')->nullable();
            $table->text('preventive_action')->nullable();
            $table->string('responsible')->nullable();
            $table->string('deadline')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['visit_type_id', 'section_id', 'code']);
            $table->index(['visit_type_id', 'section_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('visitors_checklist_items');
        Schema::dropIfExists('visitors_root_causes');
        Schema::dropIfExists('visitors_sections');
        Schema::dropIfExists('visitors_visit_types');
    }
};
