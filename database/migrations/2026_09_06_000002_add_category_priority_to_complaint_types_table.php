<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('complaint_types', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('sort_order')->constrained('complaint_categories')->nullOnDelete();
            $table->foreignId('priority_id')->nullable()->after('category_id')->constrained('priorities')->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('complaint_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropConstrainedForeignId('priority_id');
        });
    }
};