<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('customers', function (Blueprint $table) {
            $table->id(); $table->string('name');
            $table->string('phone_primary')->index(); $table->string('phone_2')->nullable()->index();
            $table->string('phone_3')->nullable()->index(); $table->string('phone_4')->nullable()->index();
            $table->text('address')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('branches', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('code')->nullable()->index();
            $table->boolean('is_active')->default(true)->index(); $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps(); $table->softDeletes();
        });
        foreach (['services', 'complaint_sources', 'complaint_categories', 'complaint_types'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id(); $table->string('name'); $table->string('color')->nullable();
                $table->boolean('is_active')->default(true)->index(); $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps(); $table->softDeletes();
            });
        }
        Schema::create('priorities', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('color'); $table->unsignedInteger('level')->nullable();
            $table->boolean('is_active')->default(true)->index(); $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('complaint_statuses', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('color');
            $table->boolean('is_active')->default(true)->index(); $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps(); $table->softDeletes();
        });
    }
    public function down(): void {
        Schema::dropIfExists('complaint_statuses'); Schema::dropIfExists('priorities');
        Schema::dropIfExists('complaint_types'); Schema::dropIfExists('complaint_categories');
        Schema::dropIfExists('complaint_sources'); Schema::dropIfExists('services');
        Schema::dropIfExists('branches'); Schema::dropIfExists('customers');
    }
};
