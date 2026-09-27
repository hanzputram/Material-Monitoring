<?php

namespace App\Http\Controllers;

use App\Models\EquipmentCategory;
use App\Models\EquipmentMaster;
use App\Models\Project;
use App\Models\ProjectEquipment;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EquipmentController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::orderBy('name')->get();
        if ($projects->isEmpty()) {
            return redirect()->route('projects.create')->with('info', 'Belum ada proyek aktif. Silakan buat proyek baru terlebih dahulu.');
        }

        $projectId = $request->query('project_id') ?? session('active_project_id');
        $project = Project::find($projectId) ?? $projects->first();
        session(['active_project_id' => $project->id]);

        $activeTab = $request->query('tab', 'master');
        $search = trim($request->query('search', ''));
        $categoryId = $request->query('category_id', 'all');
        $status = $request->query('status', '');

        // 1. Categories & Units
        $categories = EquipmentCategory::withCount('equipment')->orderBy('name')->get();
        $units = Unit::orderBy('code')->get();

        // 2. Master Equipment Query (Paginated & Filterable)
        $masterQuery = EquipmentMaster::with(['category', 'defaultUnit'])
            ->withCount('projectEquipments');

        if (!empty($search)) {
            $masterQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('spec', 'like', "%{$search}%");
            });
        }

        if (!empty($categoryId) && $categoryId !== 'all') {
            $masterQuery->where('equipment_category_id', $categoryId);
        }

        if ($status === 'active') {
            $masterQuery->where('is_active', true);
        } elseif ($status === 'inactive') {
            $masterQuery->where('is_active', false);
        }

        $masterEquipment = $masterQuery->orderBy('code')->paginate(20)->withQueryString();

        // 3. KPI Statistics
        $stats = [
            'total_equipment' => EquipmentMaster::count(),
            'total_mesin' => EquipmentMaster::whereHas('category', function ($q) {
                $q->where('name', 'like', '%mesin%');
            })->count(),
            'total_alat_berat' => EquipmentMaster::whereHas('category', function ($q) {
                $q->where('name', 'like', '%berat%');
            })->count(),
            'total_peralatan' => EquipmentMaster::whereHas('category', function ($q) {
                $q->where('name', 'like', '%peralatan%');
            })->count(),
            'active_in_project' => ProjectEquipment::where('project_id', $project->id)->count(),
        ];

        // 4. Equipment Active in Current Project
        $activeEquipment = ProjectEquipment::with(['equipmentMaster.category', 'equipmentMaster.defaultUnit', 'user'])
            ->where('project_id', $project->id)
            ->orderByDesc('created_at')
            ->get();

        // 5. Catalog for Fast Input (All Active Equipment)
        $catalog = EquipmentMaster::with(['category', 'defaultUnit'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('equipment.index', compact(
            'project',
            'projects',
            'categories',
            'units',
            'masterEquipment',
            'stats',
            'activeEquipment',
            'catalog',
            'activeTab',
            'search',
            'categoryId',
            'status'
        ));
    }

    public function storeMaster(Request $request)
    {
        $validated = $request->validate([
            'equipment_category_id' => 'required|exists:equipment_categories,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:equipment_master,code',
            'default_unit_id' => 'nullable|exists:units,id',
            'price' => 'required|numeric|min:0',
            'spec' => 'nullable|string|max:1000',
            'is_active' => 'nullable',
        ]);

        if (empty($validated['code'])) {
            $category = EquipmentCategory::find($validated['equipment_category_id']);
            $prefix = 'EQP-PLT-';
            if ($category && stripos($category->name, 'berat') !== false) {
                $prefix = 'EQP-BRT-';
            } elseif ($category && stripos($category->name, 'mesin') !== false) {
                $prefix = 'EQP-MSN-';
            }

            $existingCodes = EquipmentMaster::where('code', 'like', "{$prefix}%")->pluck('code')->toArray();
            $maxNum = 0;
            foreach ($existingCodes as $code) {
                if (preg_match('/(\d+)$/', $code, $m)) {
                    $num = intval($m[1]);
                    if ($num > $maxNum) {
                        $maxNum = $num;
                    }
                }
            }
            $validated['code'] = $prefix . str_pad($maxNum + 1, 3, '0', STR_PAD_LEFT);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        EquipmentMaster::create($validated);

        return redirect()->route('equipment.index', ['tab' => 'master'])
            ->with('success', "Alat/Mesin '{$validated['name']}' ({$validated['code']}) berhasil ditambahkan ke katalog!");
    }

    public function updateMaster(Request $request, EquipmentMaster $equipmentMaster)
    {
        $validated = $request->validate([
            'equipment_category_id' => 'required|exists:equipment_categories,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:equipment_master,code,' . $equipmentMaster->id,
            'default_unit_id' => 'nullable|exists:units,id',
            'price' => 'required|numeric|min:0',
            'spec' => 'nullable|string|max:1000',
            'is_active' => 'nullable',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $equipmentMaster->update($validated);

        return redirect()->route('equipment.index', ['tab' => 'master'])
            ->with('success', "Alat/Mesin '{$equipmentMaster->name}' berhasil diperbarui!");
    }

    public function destroyMaster(EquipmentMaster $equipmentMaster)
    {
        $allocatedCount = $equipmentMaster->projectEquipments()->count();
        if ($allocatedCount > 0) {
            return redirect()->route('equipment.index', ['tab' => 'master'])
                ->with('error', "Alat '{$equipmentMaster->name}' tidak dapat dihapus karena sedang dialokasikan di {$allocatedCount} proyek! Anda dapat menonaktifkan statusnya.");
        }

        $name = $equipmentMaster->name;
        $equipmentMaster->delete();

        return redirect()->route('equipment.index', ['tab' => 'master'])
            ->with('success', "Alat/Mesin '{$name}' berhasil dihapus dari katalog master.");
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:equipment_categories,name',
        ]);

        $category = EquipmentCategory::create($validated);

        return redirect()->route('equipment.index', ['tab' => 'master'])
            ->with('success', "Kategori '{$category->name}' berhasil ditambahkan!");
    }

    public function fastBulkStore(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'items' => 'required|array|min:1',
            'items.*.selected' => 'nullable|boolean',
            'items.*.equipment_master_id' => 'required|exists:equipment_master,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.rental_rate' => 'nullable|numeric|min:0',
            'items.*.source' => 'required|in:milik_sendiri,sewa',
            'items.*.condition' => 'required|in:baru,layak_pakai,perlu_perbaikan',
            'items.*.notes' => 'nullable|string',
        ]);

        $savedCount = 0;
        foreach ($validated['items'] as $itemData) {
            // Only save if item was checked/selected
            if (!empty($itemData['selected'])) {
                $master = EquipmentMaster::find($itemData['equipment_master_id']);
                $rentalRate = (isset($itemData['rental_rate']) && $itemData['rental_rate'] !== '')
                    ? $itemData['rental_rate']
                    : ($master?->price ?? 0);

                ProjectEquipment::updateOrCreate(
                    [
                        'project_id' => $validated['project_id'],
                        'equipment_master_id' => $itemData['equipment_master_id'],
                    ],
                    [
                        'qty' => $itemData['qty'],
                        'rental_rate' => $rentalRate,
                        'source' => $itemData['source'],
                        'condition' => $itemData['condition'],
                        'added_by' => Auth::id(),
                        'notes' => $itemData['notes'] ?? null,
                    ]
                );
                $savedCount++;
            }
        }

        if ($savedCount === 0) {
            return redirect()->back()->with('warning', 'Tidak ada alat yang dicentang untuk disimpan.');
        }

        return redirect()->route('equipment.index', ['project_id' => $validated['project_id'], 'tab' => 'active'])
            ->with('success', "{$savedCount} alat & mesin berhasil dialokasikan ke proyek via Fast Input!");
    }

    public function updateProjectEquipment(Request $request, ProjectEquipment $equipment)
    {
        $validated = $request->validate([
            'qty' => 'required|integer|min:1',
            'rental_rate' => 'nullable|numeric|min:0',
            'source' => 'required|in:milik_sendiri,sewa',
            'condition' => 'required|in:baru,layak_pakai,perlu_perbaikan',
            'notes' => 'nullable|string',
        ]);

        $equipment->update($validated);

        return redirect()->route('equipment.index', ['project_id' => $equipment->project_id, 'tab' => 'active'])
            ->with('success', "Alokasi alat '{$equipment->equipmentMaster->name}' berhasil diperbarui!");
    }

    public function destroyProjectEquipment(ProjectEquipment $equipment)
    {
        $projectId = $equipment->project_id;
        $name = $equipment->equipmentMaster->name;
        $equipment->delete();

        return redirect()->route('equipment.index', ['project_id' => $projectId, 'tab' => 'active'])
            ->with('success', "Alat '{$name}' telah dihapus dari proyek.");
    }
}
