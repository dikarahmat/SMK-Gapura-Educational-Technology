<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasUnique = DB::select(
            "SHOW INDEX FROM students WHERE Key_name = 'students_nisn_unique'"
        );

        if (! empty($hasUnique)) {
            Schema::table('students', function ($table) {
                $table->dropUnique('students_nisn_unique');
            });
        }

        $hasIndex = DB::select(
            "SHOW INDEX FROM students WHERE Key_name = 'students_nisn_index'"
        );

        if (empty($hasIndex)) {
            Schema::table('students', function ($table) {
                $table->index('nisn');
            });
        }
    }

    public function down(): void
    {
        $hasIndex = DB::select(
            "SHOW INDEX FROM students WHERE Key_name = 'students_nisn_index'"
        );

        if (! empty($hasIndex)) {
            Schema::table('students', function ($table) {
                $table->dropIndex('students_nisn_index');
            });
        }
    }
};