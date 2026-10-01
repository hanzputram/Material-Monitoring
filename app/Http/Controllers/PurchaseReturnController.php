<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\Material;
use App\Models\MaterialRealization;
use App\Models\Project;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PurchaseReturnController extends Controller
{
    /**
     * Resolves the active project or returns null if no project exists.
     *
     * @return array{0: ?Project, 1: Collection}
     */
    protected function getActiveProject(Request $request): array
    {
        $projects = Project::orderBy('name')->get();
        if ($projects->isEmpty()) {
            return [null, $projects];
        }

        $projectId = $request->query('project_id') ?? session('active_project_id');
        $project = $projectId ? Project::find($projectId) : $projects->first();
        if (! $project) {
            $project = $projects->first();
        }

        session(['active_project_id' => $project->id]);

        return [$project, $projects];
    }

    /**
     * Display a listing of purchase returns.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = Auth::user();
        if (! $user?->canReadReturn()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk melihat modul Retur Pembelian.');
        }

        [$project, $projects] = $this->getActiveProject($request);
        if (! $project) {
            return redirect()->route('projects.create')
                ->with('info', 'Belum ada proyek aktif. Silakan buat proyek baru terlebih dahulu.');
        }

        $suppliers = Supplier::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        $materials = Material::where('is_active', true)->orderBy('name')->get();
        $deliveryOrders = DeliveryOrder::where('project_id', $project->id)->orderByDesc('do_date')->get();

        $returns = PurchaseReturn::with([
            'supplier',
            'deliveryOrder',
            'creator',
            'items.material.defaultUnit',
            'items.unit',
        ])
            ->where('project_id', $project->id)
            ->orderByDesc('return_date')
            ->orderByDesc('id')
            ->get();

        $totalReturnAmount = (float) $returns->sum(function ($r) {
            return $r->total_amount;
        });

        $stats = [
            'total_returns_count' => $returns->count(),
            'total_returns_amount' => $totalReturnAmount,
            'compensation_potong_tagihan' => $returns->where('compensation_type', 'potong_tagihan')->count(),
            'compensation_ganti_barang' => $returns->where('compensation_type', 'ganti_barang')->count(),
            'compensation_pengembalian_dana' => $returns->where('compensation_type', 'pengembalian_dana')->count(),
        ];

        return view('procurement.returns.index', compact(
            'project',
            'projects',
            'suppliers',
            'units',
            'materials',
            'deliveryOrders',
            'returns',
            'stats'
        ));
    }

    /**
     * Store a newly created purchase return.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user?->canWriteReturn()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mencatat Retur Pembelian.');
        }

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'delivery_order_id' => 'nullable|exists:delivery_orders,id',
            'return_number' => 'required|string|max:100',
            'return_date' => 'required|date',
            'compensation_type' => 'required|in:potong_tagihan,ganti_barang,pengembalian_dana',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.qty_returned' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.reason' => 'nullable|string|max:255',
        ], [
            'supplier_id.required' => 'Pilih supplier penerima retur barang.',
            'return_number.required' => 'Nomor berkas / nota retur wajib diisi.',
            'items.required' => 'Masukkan rincian item barang yang diretur.',
            'items.*.qty_returned.min' => 'Jumlah barang yang diretur harus lebih dari 0.',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('returns', 'public');
        }

        DB::transaction(function () use ($validated, $attachmentPath, $user) {
            $purchaseReturn = PurchaseReturn::create([
                'project_id' => $validated['project_id'],
                'supplier_id' => $validated['supplier_id'],
                'delivery_order_id' => $validated['delivery_order_id'] ?? null,
                'return_number' => $validated['return_number'],
                'return_date' => $validated['return_date'],
                'compensation_type' => $validated['compensation_type'],
                'status' => 'completed',
                'attachment_path' => $attachmentPath,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach ($validated['items'] as $itemData) {
                $qty = (float) $itemData['qty_returned'];
                $price = (float) ($itemData['unit_price'] ?? 0);
                $totalPrice = $qty * $price;

                PurchaseReturnItem::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'material_id' => $itemData['material_id'],
                    'unit_id' => $itemData['unit_id'],
                    'qty_returned' => $qty,
                    'unit_price' => $price,
                    'total_price' => $totalPrice,
                    'reason' => $itemData['reason'] ?? 'Cacat/Rusak',
                ]);
            }

            // Sinkronkan realisasi material proyek agar kuota terpakai dikurangi barang retur
            $purchaseReturn->syncRealizations();
        });

        return redirect()->route('procurement.returns.index', ['project_id' => $validated['project_id']])
            ->with('success', "Retur Pembelian ({$validated['return_number']}) berhasil dicatat dan realisasi lapangan diperbarui.");
    }

    /**
     * Remove the specified purchase return.
     */
    public function destroy(PurchaseReturn $purchaseReturn): RedirectResponse
    {
        $user = Auth::user();
        if (! $user?->canWriteReturn()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk menghapus Retur Pembelian.');
        }

        $projectId = $purchaseReturn->project_id;
        $returnNumber = $purchaseReturn->return_number;

        DB::transaction(function () use ($purchaseReturn, $projectId) {
            $affectedMaterialIds = $purchaseReturn->items()->pluck('material_id')->unique()->toArray();

            $purchaseReturn->items()->delete();

            if ($purchaseReturn->attachment_path && Storage::disk('public')->exists($purchaseReturn->attachment_path)) {
                Storage::disk('public')->delete($purchaseReturn->attachment_path);
            }

            $purchaseReturn->delete();

            foreach ($affectedMaterialIds as $materialId) {
                MaterialRealization::recalculateForProjectMaterial($projectId, $materialId);
            }
        });

        return redirect()->route('procurement.returns.index', ['project_id' => $projectId])
            ->with('success', "Retur Pembelian ({$returnNumber}) berhasil dihapus dan kuota realisasi diperbarui.");
    }
}
