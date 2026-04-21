<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_materials', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('learning_material_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_material_id')->constrained('learning_materials')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();

            $table->unique(
                ['learning_material_id', 'user_id'],
                'lm_inv_material_user_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_material_inventories');
        Schema::dropIfExists('learning_materials');
    }
};
