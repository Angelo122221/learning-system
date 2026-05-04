<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('learning_materials')) {
            return;
        }

        $hasAuthor = Schema::hasColumn('learning_materials', 'author');
        $hasLearningArea = Schema::hasColumn('learning_materials', 'learning_area');
        $hasGradeLevel = Schema::hasColumn('learning_materials', 'grade_level');
        $hasResourceType = Schema::hasColumn('learning_materials', 'resource_type');
        $hasPublicationDate = Schema::hasColumn('learning_materials', 'publication_date');
        $hasPublisher = Schema::hasColumn('learning_materials', 'publisher');

        if (
            $hasAuthor
            && $hasLearningArea
            && $hasGradeLevel
            && $hasResourceType
            && $hasPublicationDate
            && $hasPublisher
        ) {
            return;
        }

        Schema::table('learning_materials', function (Blueprint $table) use (
            $hasAuthor,
            $hasLearningArea,
            $hasGradeLevel,
            $hasResourceType,
            $hasPublicationDate,
            $hasPublisher,
        ) {
            if (! $hasAuthor) {
                $table->string('author')->nullable();
            }

            if (! $hasLearningArea) {
                $table->string('learning_area')->nullable();
            }

            if (! $hasGradeLevel) {
                $table->string('grade_level')->nullable();
            }

            if (! $hasResourceType) {
                $table->string('resource_type')->nullable();
            }

            if (! $hasPublicationDate) {
                $table->date('publication_date')->nullable();
            }

            if (! $hasPublisher) {
                $table->string('publisher')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('learning_materials')) {
            return;
        }

        $columnsToDrop = [];
        foreach ([
            'author',
            'learning_area',
            'grade_level',
            'resource_type',
            'publication_date',
            'publisher',
        ] as $column) {
            if (Schema::hasColumn('learning_materials', $column)) {
                $columnsToDrop[] = $column;
            }
        }

        if ($columnsToDrop === []) {
            return;
        }

        Schema::table('learning_materials', function (Blueprint $table) use ($columnsToDrop) {
            $table->dropColumn($columnsToDrop);
        });
    }
};
