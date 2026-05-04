<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialInventory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class LearningMaterialInventoryController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'district' => trim((string) $request->query('district', '')),
            'school' => trim((string) $request->query('school', '')),
            'material' => trim((string) $request->query('material', '')),
        ];

        $districtOptions = User::query()
            ->where('role', 'teacher')
            ->whereNotNull('district')
            ->where('district', '!=', '')
            ->orderBy('district')
            ->distinct()
            ->pluck('district')
            ->values();

        $schoolOptionsQuery = User::query()
            ->where('role', 'teacher')
            ->whereNotNull('school_name')
            ->where('school_name', '!=', '');

        if ($filters['district'] !== '') {
            $schoolOptionsQuery->where('district', $filters['district']);
        }

        $schoolOptions = $schoolOptionsQuery
            ->orderBy('school_name')
            ->distinct()
            ->pluck('school_name')
            ->values();

        if (! $this->inventoryTablesReady()) {
            return Inertia::render('Admin/Resources/MaterialsInventory', [
                'materials' => [],
                'submissions' => [],
                'filters' => $filters,
                'filterOptions' => [
                    'districts' => $districtOptions,
                    'schools' => $schoolOptions,
                ],
                'stats' => [
                    'total_materials' => 0,
                    'total_submissions' => 0,
                    'total_quantity' => 0,
                ],
                'loadError' => 'Inventory tables are missing. Run: php artisan migrate',
            ]);
        }

        $submissionQuery = LearningMaterialInventory::query()
            ->with([
                'material:id,name,author,learning_area,grade_level,resource_type,publication_date,publisher',
                'user:id,name,role,district,school_name',
            ])
            ->whereHas('user', function ($query) {
                $query->where('role', 'teacher');
            });

        if ($filters['district'] !== '') {
            $submissionQuery->whereHas('user', function ($query) use ($filters) {
                $query->where('district', $filters['district']);
            });
        }

        if ($filters['school'] !== '') {
            $submissionQuery->whereHas('user', function ($query) use ($filters) {
                $query->where('school_name', $filters['school']);
            });
        }

        if ($filters['material'] !== '') {
            $submissionQuery->whereHas('material', function ($query) use ($filters) {
                $query->where('name', 'like', '%'.$filters['material'].'%');
            });
        }

        $submissions = $submissionQuery
            ->latest('updated_at')
            ->get()
            ->map(function (LearningMaterialInventory $entry) {
                return [
                    'id' => $entry->id,
                    'material_name' => $entry->material?->name ?? 'Unknown Material',
                    'material_author' => $entry->material?->author ?: 'N/A',
                    'teacher_name' => $entry->user?->name ?? 'Unknown Teacher',
                    'district' => $entry->user?->district ?: 'N/A',
                    'school_name' => $entry->user?->school_name ?: 'N/A',
                    'quantity' => (int) $entry->quantity,
                    'updated_at' => $entry->updated_at?->toDateTimeString(),
                ];
            })
            ->values();

        return Inertia::render('Admin/Resources/MaterialsInventory', [
            'materials' => LearningMaterial::query()
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'author',
                    'learning_area',
                    'grade_level',
                    'resource_type',
                    'publication_date',
                    'publisher',
                    'created_at',
                ]),
            'submissions' => $submissions,
            'filters' => $filters,
            'filterOptions' => [
                'districts' => $districtOptions,
                'schools' => $schoolOptions,
            ],
            'stats' => [
                'total_materials' => LearningMaterial::count(),
                'total_submissions' => $submissions->count(),
                'total_quantity' => (int) $submissions->sum('quantity'),
            ],
            'loadError' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->inventoryTablesReady()) {
            return to_route('admin.materials.inventory', [], 303)
                ->with('error', 'Inventory tables are missing. Run: php artisan migrate');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:learning_materials,name'],
            'author' => ['nullable', 'string', 'max:255'],
            'learning_area' => ['nullable', 'string', 'max:255'],
            'grade_level' => ['nullable', 'string', 'max:120'],
            'resource_type' => ['nullable', 'string', 'max:255'],
            'publication_date' => ['nullable', 'date'],
            'publisher' => ['nullable', 'string', 'max:255'],
        ]);

        LearningMaterial::create([
            'name' => $validated['name'],
            'author' => $validated['author'] ?: null,
            'learning_area' => $validated['learning_area'] ?: null,
            'grade_level' => $validated['grade_level'] ?: null,
            'resource_type' => $validated['resource_type'] ?: null,
            'publication_date' => $validated['publication_date'] ?: null,
            'publisher' => $validated['publisher'] ?: null,
        ]);

        return to_route('admin.materials.inventory', [], 303)
            ->with('success', 'Learning material added successfully.');
    }

    public function destroy(int $material): RedirectResponse
    {
        if (! $this->inventoryTablesReady()) {
            return to_route('admin.materials.inventory', [], 303)
                ->with('error', 'Inventory tables are missing. Run: php artisan migrate');
        }

        LearningMaterial::query()->findOrFail($material)->delete();

        return to_route('admin.materials.inventory', [], 303)
            ->with('success', 'Learning material deleted successfully.');
    }

    private function inventoryTablesReady(): bool
    {
        return Schema::hasTable('learning_materials')
            && Schema::hasTable('learning_material_inventories')
            && Schema::hasColumns('learning_materials', [
                'author',
                'learning_area',
                'grade_level',
                'resource_type',
                'publication_date',
                'publisher',
            ]);
    }
}
