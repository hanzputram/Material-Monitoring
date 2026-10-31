<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialRealization;
use App\Models\Project;
use App\Models\RabItem;
use App\Models\RabItemMaterial;
use App\Models\RabNode;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $materials = Material::with('defaultUnit')->where('is_active', true)->orderBy('name')->get();

        // Eager load 5 levels plus sub-breakdown materials
        $rootNodes = RabNode::with(RabNode::treeRelations([
            'materials.material.defaultUnit',
            'materials.breakdowns.material.defaultUnit',
            'materials.breakdowns.unit',
            'materials.unit',
            'unit',
        ]))
            ->where('project_id', $project->id)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        // Items with BOM available for cloning
        $itemsWithBom = RabItem::whereHas('materials')
            ->whereHas('rabNode.project')
            ->with(['rabNode.project', 'materials'])
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
        $validated['parent_id'] = $validated['parent_id'] ?? null;
        $validated['level'] = (int) $validated['level'];

        // Parent (bila ada) wajib berada di proyek yang sama dan levelnya lebih tinggi (angka lebih kecil).
        // Kategori level 2/3 tanpa induk diizinkan sebagai kategori utama (RAB dengan sebagian level).
        if ($validated['level'] > 1 && $validated['parent_id']) {
            $parent = RabNode::find($validated['parent_id']);
            if (! $parent || $parent->project_id !== $project->id || $parent->level >= $validated['level']) {
                return back()->withInput()->with('error', 'Induk kategori tidak valid untuk level yang dipilih.');
            }
        } elseif ($validated['level'] === 1) {
            $validated['parent_id'] = null;
        }

        // Auto-generate code if empty
        if (empty($validated['code'])) {
            $validated['code'] = $this->generateNodeCode($project, $validated['level'], $validated['parent_id']);
        }

        $sortOrder = RabNode::where('project_id', $project->id)
            ->where('parent_id', $validated['parent_id'])
            ->count() + 1;

        $validated['sort_order'] = $sortOrder;

        // Node baru bersubtotal 0, sehingga tidak perlu rekalkulasi seluruh proyek
        $node = RabNode::create($validated);

        return redirect()->route('rab.builder', ['project_id' => $project->id])
            ->with('success', "Kategori '{$node->code} - {$node->name}' berhasil ditambahkan!");
    }

    public function updateNode(Request $request, RabNode $node)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
        ]);

        if (empty($validated['code'])) {
            $validated['code'] = $node->code;
        }

        // Hanya kode/nama yang berubah, subtotal tidak terpengaruh
        $node->update($validated);

        return redirect()->back()->with('success', "Kategori '{$node->code} - {$node->name}' berhasil diperbarui!");
    }

    public function destroyNode(RabNode $node)
    {
        $project = $node->project;
        $name = $node->name;

        // Event "deleted" pada RabNode sudah menghitung ulang realisasi material proyek
        $node->delete();
        $project?->recalculateAllSubtotals();

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

        // Event "saved" pada RabItem menghitung subtotal node (bubble-up) & cost realization
        $item = RabItem::create($validated);

        return redirect()->route('rab.builder', ['project_id' => $node->project_id])
            ->with('success', "Item pekerjaan '{$item->name}' berhasil ditambahkan!");
    }

    public function updateItem(Request $request, RabItem $item)
    {
        $validated = $request->validate([
            'item_no' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'volume' => 'required|numeric|min:0.0001',
            'unit_id' => 'required|exists:units,id',
            'unit_price' => 'required|numeric|min:0',
        ]);

        if (empty($validated['item_no'])) {
            $validated['item_no'] = $item->item_no;
        }

        $validated['total_price'] = (float) $validated['volume'] * (float) $validated['unit_price'];

        // Event "saved" pada RabItem menghitung subtotal node (bubble-up) & cost realization
        $item->update($validated);

        return redirect()->back()->with('success', "Item pekerjaan '{$item->name}' berhasil diperbarui!");
    }

    public function destroyItem(RabItem $item)
    {
        $projectId = $item->rabNode->project_id;
        $name = $item->name;

        // Event "deleted" pada RabItem menghitung ulang subtotal, cost & realisasi material proyek
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

    /**
     * Simpan rincian breakdown material (misal untuk material hasil jadi / composite)
     */
    public function storeMaterialBreakdown(Request $request, RabItemMaterial $material)
    {
        $validated = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'volume' => 'required|numeric|min:0.0001',
            'unit_id' => 'required|exists:units,id',
            'unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ], [
            'material_id.required' => 'Pilih material rincian dasar dari katalog.',
            'volume.required' => 'Masukkan volume kebutuhan rincian.',
            'unit_id.required' => 'Pilih satuan kuantiti.',
            'unit_price.required' => 'Masukkan estimasi harga satuan.',
        ]);

        $validated['rab_item_id'] = $material->rab_item_id;
        $validated['parent_id'] = $material->id;
        $validated['total_price'] = (float) $validated['volume'] * (float) $validated['unit_price'];
        $validated['input_by'] = Auth::id();

        $sub = RabItemMaterial::create($validated);

        return redirect()->back()
            ->with('success', "Rincian breakdown '{$sub->material?->name}' berhasil ditambahkan ke material hasil jadi '{$material->material?->name}'!");
    }

    public function updateItemMaterial(Request $request, RabItemMaterial $material)
    {
        $validated = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'volume' => 'required|numeric|min:0.0001',
            'unit_id' => 'required|exists:units,id',
            'unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $validated['total_price'] = (float) $validated['volume'] * (float) $validated['unit_price'];
        $material->update($validated);

        return redirect()->back()
            ->with('success', "Data material '{$material->material?->name}' berhasil diperbarui!");
    }

    public function destroyItemMaterial(RabItemMaterial $material)
    {
        $item = $material->rabItem;
        $projectId = $item?->rabNode?->project_id;
        $name = $material->material?->name ?? 'Material';
        $material->delete();
        if ($projectId) {
            MaterialRealization::recalculateAllForProject($projectId);
        }

        return redirect()->back()
            ->with('success', "Breakdown material '{$name}' berhasil dihapus.");
    }

    public function cloneBom(Request $request, RabItem $item)
    {
        $request->validate([
            'source_item_id' => 'required|exists:rab_items,id',
        ]);

        $source = RabItem::with('materials')->findOrFail($request->source_item_id);

        DB::transaction(function () use ($source, $item) {
            // Salin BOM beserta hirarki breakdown (parent_id dipetakan ke baris hasil salinan),
            // agar rincian tidak berubah menjadi material root dan kuota tidak terhitung ganda.
            $idMap = [];
            $pending = $source->materials->sortBy('id')->values();

            while ($pending->isNotEmpty()) {
                $next = $pending->filter(fn ($mat) => ! $mat->parent_id || isset($idMap[$mat->parent_id]) || ! $source->materials->contains('id', $mat->parent_id));
                if ($next->isEmpty()) {
                    break;
                }

                foreach ($next as $mat) {
                    $copy = RabItemMaterial::createQuietly([
                        'rab_item_id' => $item->id,
                        'material_id' => $mat->material_id,
                        'parent_id' => $mat->parent_id ? ($idMap[$mat->parent_id] ?? null) : null,
                        'volume' => $mat->volume,
                        'unit_id' => $mat->unit_id,
                        'unit_price' => $mat->unit_price,
                        'total_price' => $mat->total_price,
                        'notes' => $mat->notes,
                        'input_by' => Auth::id(),
                    ]);
                    $idMap[$mat->id] = $copy->id;
                }

                $pending = $pending->diffKeys($next);
            }

            if (! empty($idMap)) {
                $item->is_composite = true;
                $item->saveQuietly();
            }

            MaterialRealization::recalculateAllForProject($item->rabNode->project_id);
        });

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

            return $parentCode.'.'.($letters[$childCount % count($letters)]);
        }

        if ($level === 3) {
            $letters = range('A', 'Z');
            $childCount = RabNode::where('parent_id', $parentId)->count();

            return $letters[$childCount % count($letters)].'.1';
        }

        return (string) rand(1, 99);
    }
}
