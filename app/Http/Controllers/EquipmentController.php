<?php

namespace App\Http\Controllers;

use App\Models\EquipmentCategory;
use App\Models\EquipmentMaster;
use App\Models\Project;
use App\Models\ProjectEquipment;
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

        $categories = EquipmentCategory::withCount('equipment')->get();

        $activeEquipment = ProjectEquipment::with(['equipmentMaster.category', 'equipmentMaster.defaultUnit', 'user'])
            ->where('project_id', $project->id)
            ->orderByDesc('created_at')
            ->get();

        // Catalog for Fast Input
        $catalog = EquipmentMaster::with(['category', 'defaultUnit'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('equipment.index', compact(
            'project',
            'projects',
            'categories',
            'activeEquipment',
            'catalog'
        ));
    }

    public function fastBulkStore(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'items' => 'required|array|min:1',
            'items.*.selected' => 'nullable|boolean',
            'items.*.equipment_master_id' => 'required|exists:equipment_master,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.source' => 'required|in:milik_sendiri,sewa',
            'items.*.condition' => 'required|in:baru,layak_pakai,perlu_perbaikan',
            'items.*.notes' => 'nullable|string',
        ]);

        $savedCount = 0;
        foreach ($validated['items'] as $itemData) {
            // Only save if item was checked/selected
            if (!empty($itemData['selected'])) {
                ProjectEquipment::updateOrCreate(
                    [
                        'project_id' => $validated['project_id'],
                        'equipment_master_id' => $itemData['equipment_master_id'],
                    ],
                    [
                        'qty' => $itemData['qty'],
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

        return redirect()->route('equipment.index', ['project_id' => $validated['project_id']])
            ->with('success', "{$savedCount} alat & mesin berhasil dialokasikan ke proyek via Fast Input!");
    }

    public function destroyProjectEquipment(ProjectEquipment $equipment)
    {
        $projectId = $equipment->project_id;
        $name = $equipment->equipmentMaster->name;
        $equipment->delete();

        return redirect()->route('equipment.index', ['project_id' => $projectId])
            ->with('success', "Alat '{$name}' telah dihapus dari proyek.");
    }
}
