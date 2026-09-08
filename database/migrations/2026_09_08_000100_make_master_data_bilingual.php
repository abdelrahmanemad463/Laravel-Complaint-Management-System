<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'branches',
        'services',
        'complaint_sources',
        'complaint_categories',
        'complaint_types',
        'priorities',
        'complaint_statuses',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('name_en', 255)->nullable()->after('name');
                $blueprint->string('name_ar', 255)->nullable()->after('name_en');
            });

            DB::table($table)->update([
                'name_en' => DB::raw('name'),
                'name_ar' => DB::raw('name'),
            ]);

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('name');
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('name', 255)->nullable()->after('id');
            });

            DB::table($table)->update([
                'name' => DB::raw('COALESCE(name_en, name_ar)'),
            ]);

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn(['name_en', 'name_ar']);
            });
        }
    }
};