<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('resource_files') || Schema::hasColumn('resource_files', 'category')) {
            return;
        }

        Schema::table('resource_files', function (Blueprint $table) {
            $table->string('category')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('resource_files') || ! Schema::hasColumn('resource_files', 'category')) {
            return;
        }

        Schema::table('resource_files', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
