<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Project;
use App\Models\RabItem;
use App\Models\RabItemMaterial;
use App\Models\RabNode;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RabBuilderController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::orderBy('name')->get();
        if ($projects->isEmpty()) {
            return redirect()->route('projects.create')->with('info', 'Belum ada proyek aktif. Silakan buat proyek baru atau impor file Excel RAB terlebih dahulu.');
        }

        $projectId = $request->query('project_id') ?? session('active_project_id');
        $project = Project::find($projectId) ?? $projects->first();
        session(['active_project_id' => $project->id]);

        $units = Unit::orderBy('name')->get();
        $materials = Material::where('is_active', true)->orderBy('name')->get();

        // Eager load 5 levels
        $rootNodes = RabNode::with([
            'children.children.rabItems.materials.material',
            'children.children.rabItems.unit',
            'children.rabItems.materials.material',
            'children.rabItems.unit',
            'rabItems.materials.material',
            'rabItems.unit',
        ])
        ->where('project_id', $project->id)
        ->whereNull('parent_id')
        ->orderBy('sort_order')
        ->get();

        // Items with BOM available for cloning
        $itemsWithBom = RabItem::whereHas('materials')
            ->with(['rabNode.project', 'materials.material'])
            ->get();

        return view('rab.builder', compact(
            'project',
            'projects',
            'units',
            'materials',
            'rootNodes',
            'itemsWithBom'
        ));
    }

    public function storeNode(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'parent_id' => 'nullable|exists:rab_nodes,id',
            'level' => 'required|integer|in:1,2,3',
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
        ]);

        $project = Project::findOrFail($validated['project_id']);

        // Auto-generate code if empty
        if (empty($validated['code'])) {
            $validated['code'] = $this->generateNodeCode($project, $validated['level'], $validated['parent_id']);
        }

        $sortOrder = RabNode::where('project_id', $project->id)
            ->where('parent_id', $validated['parent_id'])
            ->count() + 1;

        $validated['sort_order'] = $sortOrder;

        $node = RabNode::create($validated);
        $project->recalculateAllSubtotals();

        return redirect()->route('rab.builder', ['project_id' => $project->id])
            ->with('success', "Kategori '{$node->code} - {$node->name}' berhasil ditambahkan!");
    }

    public function updateNode(Request $request, RabNode $node)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
        ]);

        $node->update($validated);

        return redirect()->back()->with('success', "Kategori '{$node->code}' berhasil diperbarui!");
    }

    public function destroyNode(RabNode $node)
    {
        $project = $node->project;
        $name = $node->name;
        $node->delete();
        $project->recalculateAllSubtotals();

        return redirect()->back()->with('success', "Kategori '{$name}' berhasil dihapus.");
    }

    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'rab_node_id' => 'required|exists:rab_nodes,id',
            'item_no' => 'nullable|string|max:50',
            'name' => 'required|string',
            'volume' => 'required|numeric|min:0.0001',
            'unit_id' => 'required|exists:units,id',
            'unit_price' => 'required|numeric|min:0',
        ]);

        $node = RabNode::findOrFail($validated['rab_node_id']);
        $totalPrice = (float) $validated['volume'] * (float) $validated['unit_price'];

        if (empty($validated['item_no'])) {
            $itemCount = $node->rabItems()->count() + 1;
            $validated['item_no'] = "{$itemCount}.0";
        }

        $validated['total_price'] = $totalPrice;
        $validated['sort_order'] = $node->rabItems()->count() + 1;
        $validated['is_composite'] = false;

        $item = RabItem::create($validated);
        $node->refreshSubtotal();

        return redirect()->route('rab.builder', ['project_id' => $node->project_id])
            ->with('success', "Item pekerjaan '{$item->name}' berhasil ditambahkan!");
    }

    public function updateItem(Request $request, RabItem $item)
    {
        $validated = $request->validate([
            'item_no' => 'required|string|max:50',
            'name' => 'required|string',
            'volume' => 'required|numeric|min:0.0001',
            'unit_id' => 'required|exists:units,id',
            'unit_price' => 'required|numeric|min:0',
        ]);

        $validated['total_price'] = (float) $validated['volume'] * (float) $validated['unit_price'];
        $item->update($validated);

        return redirect()->back()->with('success', "Item pekerjaan '{$item->name}' berhasil diperbarui!");
    }

    public function destroyItem(RabItem $item)
    {
        $projectId = $item->rabNode->project_id;
        $name = $item->name;
        $item->delete();

        return redirect()->route('rab.builder', ['project_id' => $projectId])
            ->with('success', "Item '{$name}' berhasil dihapus.");
    }

    public function storeItemMaterial(Request $request, RabItem $item)
    {
        $validated = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'volume' => 'required|numeric|min:0.0001',
            'unit_id' => 'required|exists:units,id',
            'unit_price' => 'required|numeric|min:0',
        ]);

        $validated['rab_item_id'] = $item->id;
        $validated['total_price'] = (float) $validated['volume'] * (float) $validated['unit_price'];
        $validated['input_by'] = Auth::id();

        RabItemMaterial::create($validated);

        return redirect()->route('rab.builder', ['project_id' => $item->rabNode->project_id])
            ->with('success', "Material dasar berhasil ditambahkan ke item '{$item->name}'!");
    }

    public function destroyItemMaterial(RabItemMaterial $material)
    {
        $item = $material->rabItem;
        $projectId = $item->rabNode->project_id;
        $material->delete();

        return redirect()->route('rab.builder', ['project_id' => $projectId])
            ->with('success', "Breakdown material berhasil dihapus.");
    }

    public function cloneBom(Request $request, RabItem $item)
    {
        $request->validate([
            'source_item_id' => 'required|exists:rab_items,id',
        ]);

        $source = RabItem::with('materials')->findOrFail($request->source_item_id);

        foreach ($source->materials as $mat) {
            RabItemMaterial::create([
                'rab_item_id' => $item->id,
                'material_id' => $mat->material_id,
                'volume' => $mat->volume,
                'unit_id' => $mat->unit_id,
                'unit_price' => $mat->unit_price,
                'total_price' => $mat->total_price,
                'input_by' => Auth::id(),
            ]);
        }

        return redirect()->route('rab.builder', ['project_id' => $item->rabNode->project_id])
            ->with('success', "Breakdown BOM dari '{$source->name}' berhasil diduplikasi ke '{$item->name}'!");
    }

    private function generateNodeCode(Project $project, int $level, ?int $parentId): string
    {
        if ($level === 1) {
            $romans = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
            $count = $project->rootRabNodes()->count();
            return $romans[$count % count($romans)];
        }

        if ($level === 2) {
            $parent = RabNode::find($parentId);
            $parentCode = $parent ? $parent->code : 'I';
            $letters = range('A', 'Z');
            $childCount = RabNode::where('parent_id', $parentId)->count();
            return $parentCode . '.' . ($letters[$childCount % count($letters)]);
        }

        if ($level === 3) {
            $letters = range('A', 'Z');
            $childCount = RabNode::where('parent_id', $parentId)->count();
            return ($letters[$childCount % count($letters)]) . '.1';
        }

        return (string) rand(1, 99);
    }
}
