<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->string('serial_number', 255)->nullable();
            $table->decimal('price', 12, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (Schema::hasColumn('complaints', 'price')) {
                $table->dropColumn('price');
            }
            if (Schema::hasColumn('complaints', 'serial_number')) {
                $table->dropColumn('serial_number');
            }
        });
    }
};