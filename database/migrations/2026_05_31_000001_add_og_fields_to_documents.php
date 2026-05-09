<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'og_title')) {
                $table->string('og_title')->nullable()->after('meta_description');
            }

            if (!Schema::hasColumn('documents', 'og_description')) {
                $table->text('og_description')->nullable()->after('og_title');
            }

            if (!Schema::hasColumn('documents', 'og_image')) {
                $table->string('og_image')->nullable()->after('og_description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(array_filter(
                ['og_title', 'og_description', 'og_image'],
                fn ($col) => Schema::hasColumn('documents', $col),
            ));
        });
    }
};
