<?php

namespace App\Http\Controllers;

use App\Models\LearningMaterial;
use App\Models\LearningMaterialInventory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class LearningMaterialInventoryController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user?->is_admin) {
            return to_route('admin.materials.inventory', [], 303);
        }

        if (! $this->inventoryTablesReady()) {
            return Inertia::render('User/Materials/Index', [
                'materials' => [],
                'loadError' => 'Inventory tables are missing. Ask your admin to run: php artisan migrate',
            ]);
        }

        $materials = LearningMaterial::query()
            ->orderBy('name')
            ->with(['inventories' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->get()
            ->map(function (LearningMaterial $material) {
                return [
                    'id' => $material->id,
                    'name' => $material->name,
                    'description' => $material->description,
                    'resource_type' => $material->resource_type ?: 'N/A',
                    'learning_area' => $material->learning_area ?: 'N/A',
                    'grade_level' => $material->grade_level ?: 'N/A',
                    'author' => $material->author ?: 'N/A',
                    'publisher' => $material->publisher ?: 'N/A',
                    'publication_date' => $material->publication_date?->toDateString() ?: 'N/A',
                    'quantity' => (int) ($material->inventories->first()?->quantity ?? 0),
                ];
            })
            ->values();

        return Inertia::render('User/Materials/Index', [
            'materials' => $materials,
            'loadError' => null,
        ]);
    }

    public function store(Request $request, int $material): RedirectResponse
    {
        $user = $request->user();

        if ($user?->is_admin) {
            return to_route('admin.materials.inventory', [], 303);
        }

        if (! $this->inventoryTablesReady()) {
            return to_route('materials.index', [], 303)
                ->with('error', 'Inventory tables are missing. Ask your admin to run: php artisan migrate');
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        $materialModel = LearningMaterial::query()->findOrFail($material);

        LearningMaterialInventory::updateOrCreate(
            [
                'learning_material_id' => $materialModel->id,
                'user_id' => $user->id,
            ],
            [
                'quantity' => (int) $validated['quantity'],
            ]
        );

        return back(303)->with('success', 'Learning material quantity saved.');
    }

    private function inventoryTablesReady(): bool
    {
        return Schema::hasTable('learning_materials')
            && Schema::hasTable('learning_material_inventories');
    }
}
