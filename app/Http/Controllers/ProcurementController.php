<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\MaterialRealization;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\RabItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\PurchaseOrderPdfService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcurementController extends Controller
{
    /**
     * Resolves the active project or redirects if no project exists.
     */
    protected function getActiveProject(Request $request)
    {
        $projects = Project::orderBy('name')->get();
        if ($projects->isEmpty()) {
            return [null, null];
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
     * General overview / router to redirect to the user's permitted section.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if ($user->canReadPo()) {
            return redirect()->route('procurement.po.index', $request->query());
        } elseif ($user->canReadDo()) {
            return redirect()->route('procurement.do.index', $request->query());
        } elseif ($user->canReadInvoice()) {
            return redirect()->route('procurement.invoices.index', $request->query());
        }

        abort(403, 'Anda tidak memiliki hak akses untuk membuka modul Pesanan Pembelian, Penerimaan Pembelian, maupun Faktur.');
    }

    // ============================================================
    // 1. SUBMODUL PURCHASE ORDER (PO)
    // ============================================================

    public function poIndex(Request $request)
    {
        if (! Auth::user()->canReadPo()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk melihat modul Pesanan Pembelian.');
        }

        [$project, $projects] = $this->getActiveProject($request);
        if (! $project) {
            return redirect()->route('projects.create')->with('info', 'Belum ada proyek aktif. Silakan buat proyek baru terlebih dahulu.');
        }

        $suppliers = Supplier::orderBy('name')->get();
        $materials = Material::with('defaultUnit')->where('is_active', true)->orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        $rabItems = RabItem::whereHas('rabNode', function ($q) use ($project) {
            $q->where('project_id', $project->id);
        })
            ->with(['rabNode.parent', 'unit'])
            ->get()
            ->sortBy(function ($item) {
                return ($item->rabNode?->code ?? '').'-'.str_pad((string) ($item->item_no ?? $item->sort_order), 5, '0', STR_PAD_LEFT);
            })
            ->values();

        $purchaseOrders = PurchaseOrder::with([
            'supplier',
            'creator',
            'items.material.defaultUnit',
            'items.rabItem.rabNode',
            'items.unit',
            'deliveryOrders',
            'invoices',
        ])
            ->where('project_id', $project->id)
            ->orderByDesc('po_date')
            ->get();

        // Count for badges if user has permission
        $doCount = Auth::user()->canReadDo()
            ? DeliveryOrder::where('project_id', $project->id)->count()
            : 0;
        $invCount = Auth::user()->canReadInvoice()
            ? Invoice::where('project_id', $project->id)->count()
            : 0;

        return view('procurement.po', compact(
            'project',
            'projects',
            'suppliers',
            'materials',
            'units',
            'rabItems',
            'purchaseOrders',
            'doCount',
            'invCount'
        ));
    }

    public function storePo(Request $request)
    {
        if (! Auth::user()->canWritePo()) {
            abort(403, 'Akses Ditolak: Anda hanya memiliki izin Lihat Saja (Read-Only) dan tidak dapat membuat Pesanan Pembelian.');
        }

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'po_number' => 'required|string|unique:purchase_orders,po_number',
            'po_date' => 'required|date',
            'delivery_order_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.rab_item_id' => 'nullable|exists:rab_items,id',
            'items.*.qty_ordered' => 'required|numeric|min:0.0001',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Item RAB yang dialokasikan wajib berasal dari proyek PO ini
        $rabItemIds = collect($validated['items'])->pluck('rab_item_id')->filter()->unique();
        if ($rabItemIds->isNotEmpty()) {
            $validRabItemCount = RabItem::whereIn('id', $rabItemIds)
                ->whereHas('rabNode', fn ($q) => $q->where('project_id', $validated['project_id']))
                ->count();
            if ($validRabItemCount !== $rabItemIds->count()) {
                return back()->withInput()->with('error', 'Alokasi item RAB tidak valid: item RAB harus berasal dari proyek yang sama dengan PO.');
            }
        }

        $po = DB::transaction(function () use ($validated) {
            $po = PurchaseOrder::create([
                'project_id' => $validated['project_id'],
                'supplier_id' => $validated['supplier_id'],
                'po_number' => $validated['po_number'],
                'po_date' => $validated['po_date'],
                'delivery_order_number' => isset($validated['delivery_order_number']) ? trim($validated['delivery_order_number']) : null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'sent',
                'created_by' => Auth::id(),
            ]);

            $po->items()->createMany(collect($validated['items'])->map(fn ($item) => [
                'material_id' => $item['material_id'],
                'rab_item_id' => ! empty($item['rab_item_id']) ? $item['rab_item_id'] : null,
                'qty_ordered' => $item['qty_ordered'],
                'unit_id' => $item['unit_id'],
                'unit_price' => $item['unit_price'],
            ])->all());

            return $po;
        });

        return redirect()->route('procurement.po.index', ['project_id' => $po->project_id])
            ->with('success', "Pesanan Pembelian (PO) {$po->po_number} berhasil dibuat!");
    }

    /**
     * Catat atau perbarui nomor Surat Jalan (DO/SJ) supplier yang dijadwalkan untuk PO tertentu
     */
    public function updatePoDoNumber(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (! Auth::user()->canWritePo()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengedit Pesanan Pembelian.');
        }

        $validated = $request->validate([
            'delivery_order_number' => 'nullable|string|max:100',
        ]);

        $newDoNumber = $validated['delivery_order_number'] ? trim($validated['delivery_order_number']) : null;

        if ($newDoNumber) {
            $alreadyUsed = DeliveryOrder::where('project_id', $purchaseOrder->project_id)
                ->where('do_number', $newDoNumber)
                ->exists();
            if ($alreadyUsed) {
                return back()->with('error', "Nomor Surat Jalan \"{$newDoNumber}\" sudah pernah dicatat / diterima sebelumnya di proyek ini. Harap gunakan nomor Surat Jalan yang baru.");
            }
        }

        $purchaseOrder->update([
            'delivery_order_number' => $newDoNumber,
        ]);

        $msg = $purchaseOrder->delivery_order_number
            ? "Nomor DO/SJ untuk PO {$purchaseOrder->po_number} berhasil diperbarui: {$purchaseOrder->delivery_order_number}. Petugas lapangan kini dapat memilih nomor ini saat barang tiba."
            : "Nomor DO/SJ untuk PO {$purchaseOrder->po_number} telah dikosongkan.";

        return back()->with('success', $msg);
    }

    /**
     * Unduh atau pratinjau dokumen Purchase Order resmi format PDF
     */
    public function downloadPoPdf(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderPdfService $pdfService)
    {
        if (! Auth::user()->canReadPo()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengunduh Pesanan Pembelian.');
        }

        $pdf = $pdfService->generate($purchaseOrder);

        $cleanPoNumber = preg_replace('/[^A-Za-z0-9\-_]/', '_', $purchaseOrder->po_number);
        $filename = "PO_{$cleanPoNumber}.pdf";

        if ($request->has('stream') || $request->has('preview')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    // ============================================================
    // 2. SUBMODUL SURAT JALAN (DO LAPANGAN)
    // ============================================================

    public function doIndex(Request $request)
    {
        if (! Auth::user()->canReadDo()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk melihat modul Penerimaan Pembelian (DO).');
        }

        [$project, $projects] = $this->getActiveProject($request);
        if (! $project) {
            return redirect()->route('projects.create')->with('info', 'Belum ada proyek aktif. Silakan buat proyek baru terlebih dahulu.');
        }

        $suppliers = Supplier::orderBy('name')->get();
        $materials = Material::with('defaultUnit')->where('is_active', true)->orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        $deliveryOrders = DeliveryOrder::with([
            'supplier',
            'receiver',
            'items.material.defaultUnit',
            'items.purchaseOrder',
            'purchaseOrder',
            'purchaseOrders.supplier',
        ])
            ->where('project_id', $project->id)
            ->orderByDesc('do_date')
            ->get();

        $purchaseOrders = PurchaseOrder::where('project_id', $project->id)
            ->with(['supplier', 'items.material.defaultUnit', 'items.unit'])
            ->orderByDesc('po_date')
            ->get();

        // Qty diterima per PO & material dalam satu query (bukan satu query per item)
        $receivedMap = PurchaseOrder::receivedQuantitiesFor($purchaseOrders->modelKeys());

        // Badge counters
        $poCount = Auth::user()->canReadPo()
            ? $purchaseOrders->count()
            : 0;
        $invCount = Auth::user()->canReadInvoice()
            ? Invoice::where('project_id', $project->id)->count()
            : 0;
        $pendingPoCount = $purchaseOrders
            ->filter(fn ($p) => $p->status !== 'cancelled' && $p->getFulfillmentDetails($receivedMap[$p->id] ?? [], [])['has_pending'])
            ->count();

        // Kumpulkan nomor DO yang sudah pernah diterima di proyek ini agar TIDAK dapat diinput ulang
        $existingDoNumbers = $deliveryOrders
            ->pluck('do_number')
            ->map(fn ($n) => trim($n))
            ->unique()
            ->toArray();

        // Hanya tampilkan jadwal DO yang nomornya belum pernah diinput sebagai DO dan masih memiliki sisa barang
        $scheduledDeliveries = $purchaseOrders
            ->filter(fn ($po) => ! empty($po->delivery_order_number) && ! in_array(trim($po->delivery_order_number), $existingDoNumbers))
            ->map(function ($po) use ($receivedMap) {
                $itemsWithRemaining = $po->items->map(function ($it) use ($po, $receivedMap) {
                    $receivedQty = (float) ($receivedMap[$po->id][$it->material_id] ?? 0);
                    $remaining = max(0, (float) $it->qty_ordered - $receivedQty);

                    return [
                        'material_id' => $it->material_id,
                        'material_name' => $it->material?->name,
                        'material_code' => $it->material?->code,
                        'unit_id' => $it->unit_id ?? $it->material?->default_unit_id,
                        'unit_code' => $it->unit?->code,
                        'qty_ordered' => (float) $it->qty_ordered,
                        'qty_received' => $receivedQty,
                        'qty_remaining' => $remaining,
                    ];
                })->filter(fn ($it) => $it['qty_remaining'] > 0)->values();

                return [
                    'po_id' => $po->id,
                    'po_number' => $po->po_number,
                    'do_number' => $po->delivery_order_number,
                    'supplier_id' => $po->supplier_id,
                    'supplier_name' => $po->supplier?->name ?? 'Supplier',
                    'items' => $itemsWithRemaining->map(fn ($it) => [
                        'material_id' => $it['material_id'],
                        'material_name' => $it['material_name'],
                        'material_code' => $it['material_code'],
                        'unit_id' => $it['unit_id'],
                        'unit_code' => $it['unit_code'],
                        'qty_ordered' => $it['qty_remaining'],
                    ])->values(),
                ];
            })
            ->filter(fn ($sd) => count($sd['items']) > 0)
            ->values();

        return view('procurement.do', compact(
            'project',
            'projects',
            'suppliers',
            'materials',
            'units',
            'deliveryOrders',
            'purchaseOrders',
            'scheduledDeliveries',
            'poCount',
            'invCount',
            'pendingPoCount'
        ));
    }

    public function storeDo(Request $request)
    {
        if (! Auth::user()->canWriteDo()) {
            abort(403, 'Akses Ditolak: Anda hanya memiliki izin Lihat Saja (Read-Only) dan tidak dapat menginput Penerimaan Pembelian (DO).');
        }

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'purchase_order_ids' => 'nullable|array',
            'purchase_order_ids.*' => 'exists:purchase_orders,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'do_number' => 'required|string',
            'do_date' => 'required|date',
            'attachment' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // Mandatory physical proof
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'items.*.qty_received' => 'required|numeric|min:0.0001',
            'items.*.unit_id' => 'required|exists:units,id',
        ]);

        // Cek apakah nomor Surat Jalan (DO) ini sudah pernah diinput sebelumnya di proyek ini
        $existingDo = DeliveryOrder::where('project_id', $validated['project_id'])
            ->where('do_number', trim($validated['do_number']))
            ->first();

        if ($existingDo) {
            return back()
                ->withInput()
                ->with('error', "Nomor Surat Jalan \"{$validated['do_number']}\" sudah pernah dicatat / diterima sebelumnya di proyek ini (tgl ".Carbon::parse($existingDo->do_date)->format('d/m/Y').') dan tidak dapat diinput ulang.');
        }

        $filePath = $request->file('attachment')->store('delivery_orders', 'public');

        // Consolidate all linked PO IDs
        $poIds = collect($validated['purchase_order_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter();

        if (! empty($validated['purchase_order_id'])) {
            $poIds->push((int) $validated['purchase_order_id']);
        }

        foreach ($validated['items'] as $item) {
            if (! empty($item['purchase_order_id'])) {
                $poIds->push((int) $item['purchase_order_id']);
            }
        }
        $poIds = $poIds->unique()->values()->all();

        $primaryPoId = ! empty($poIds) ? $poIds[0] : ($validated['purchase_order_id'] ?? null);

        $do = DB::transaction(function () use ($validated, $filePath, $poIds, $primaryPoId) {
            $do = DeliveryOrder::create([
                'project_id' => $validated['project_id'],
                'purchase_order_id' => $primaryPoId,
                'supplier_id' => $validated['supplier_id'],
                'do_number' => trim($validated['do_number']),
                'do_date' => $validated['do_date'],
                'attachment_path' => $filePath,
                'received_by' => Auth::id(),
                'status' => 'validated',
                'notes' => $validated['notes'] ?? null,
            ]);

            if (! empty($poIds)) {
                $do->purchaseOrders()->sync($poIds);
            }

            foreach ($validated['items'] as $item) {
                $itemPoId = array_key_exists('purchase_order_id', $item)
                    ? (! empty($item['purchase_order_id']) ? (int) $item['purchase_order_id'] : null)
                    : $primaryPoId;

                DeliveryOrderItem::create([
                    'delivery_order_id' => $do->id,
                    'purchase_order_id' => $itemPoId,
                    'material_id' => $item['material_id'],
                    'qty_received' => $item['qty_received'],
                    'unit_id' => $item['unit_id'],
                ]);
            }

            // Recalculate material realization & variance (sekali per material unik)
            $do->load('items');
            foreach ($do->items->pluck('material_id')->unique() as $materialId) {
                MaterialRealization::recalculateForProjectMaterial($do->project_id, $materialId);
            }

            return $do;
        });

        return redirect()->route('procurement.do.index', ['project_id' => $do->project_id])
            ->with('success', "Penerimaan Pembelian (DO) {$do->do_number} dan bukti fisik berhasil dicatat!");
    }

    public function destroyDo(Request $request, DeliveryOrder $deliveryOrder)
    {
        if (! Auth::user()->canWriteDo()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk menghapus Penerimaan Pembelian (DO).');
        }

        $projectId = $deliveryOrder->project_id;
        $doNumber = $deliveryOrder->do_number;

        // Delete variance validations linked to this DO
        $deliveryOrder->varianceValidations()->delete();

        // Delete items
        $deliveryOrder->items()->delete();

        // Delete attachment if stored
        if ($deliveryOrder->attachment_path && Storage::disk('public')->exists($deliveryOrder->attachment_path)) {
            Storage::disk('public')->delete($deliveryOrder->attachment_path);
        }

        // Delete DO
        $deliveryOrder->delete();

        // Recalculate realizations
        MaterialRealization::recalculateAllForProject($projectId);

        return redirect()->route('procurement.do.index', ['project_id' => $projectId])
            ->with('success', "Penerimaan Pembelian (DO) {$doNumber} berhasil dihapus dan kuota realisasi telah diperbarui.");
    }

    // ============================================================
    // 3. SUBMODUL FAKTUR TAGIHAN (INVOICE)
    // ============================================================

    public function invoiceIndex(Request $request)
    {
        if (! Auth::user()->canReadInvoice()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk melihat modul Faktur Tagihan (Invoice).');
        }

        [$project, $projects] = $this->getActiveProject($request);
        if (! $project) {
            return redirect()->route('projects.create')->with('info', 'Belum ada proyek aktif. Silakan buat proyek baru terlebih dahulu.');
        }

        $suppliers = Supplier::orderBy('name')->get();
        $invoices = Invoice::with(['supplier', 'validator', 'purchaseOrder', 'payments'])
            ->where('project_id', $project->id)
            ->orderByDesc('invoice_date')
            ->get();

        $purchaseOrders = PurchaseOrder::with('supplier')->where('project_id', $project->id)->orderByDesc('po_date')->get();

        // Badge counters
        $poCount = Auth::user()->canReadPo()
            ? $purchaseOrders->count()
            : 0;
        $doCount = Auth::user()->canReadDo()
            ? DeliveryOrder::where('project_id', $project->id)->count()
            : 0;

        return view('procurement.invoices', compact(
            'project',
            'projects',
            'suppliers',
            'invoices',
            'purchaseOrders',
            'poCount',
            'doCount'
        ));
    }

    public function storeInvoice(Request $request)
    {
        if (! Auth::user()->canWriteInvoice()) {
            abort(403, 'Akses Ditolak: Anda hanya memiliki izin Lihat Saja (Read-Only) dan tidak dapat menginput Faktur Tagihan (Invoice).');
        }

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'required|string',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
            'attachment' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // Mandatory proof
            'notes' => 'nullable|string',
        ]);

        $filePath = $request->file('attachment')->store('invoices', 'public');

        $invoice = Invoice::create([
            'project_id' => $validated['project_id'],
            'purchase_order_id' => $validated['purchase_order_id'] ?? null,
            'supplier_id' => $validated['supplier_id'],
            'invoice_number' => $validated['invoice_number'],
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'] ?? null,
            'amount' => $validated['amount'],
            'paid_amount' => 0,
            'payment_status' => 'unpaid',
            'attachment_path' => $filePath,
            'validated_by' => Auth::id(),
            'status' => 'validated',
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('procurement.invoices.index', ['project_id' => $invoice->project_id])
            ->with('success', "Faktur Pembelian (Invoice) {$invoice->invoice_number} dan berkas lampiran berhasil dicatat!");
    }

    public function destroyInvoice(Invoice $invoice)
    {
        if (! Auth::user()->canWriteInvoice()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk menghapus Faktur Tagihan (Invoice).');
        }

        if ($invoice->paymentItems()->exists() || $invoice->paid_amount > 0) {
            return back()->with('error', "Faktur {$invoice->invoice_number} tidak dapat dihapus karena sudah memiliki catatan pembayaran.");
        }

        $projectId = $invoice->project_id;
        $invoiceNumber = $invoice->invoice_number;

        if ($invoice->attachment_path && Storage::disk('public')->exists($invoice->attachment_path)) {
            Storage::disk('public')->delete($invoice->attachment_path);
        }

        $invoice->delete();

        return redirect()->route('procurement.invoices.index', ['project_id' => $projectId])
            ->with('success', "Faktur Pembelian {$invoiceNumber} berhasil dihapus.");
    }

    /**
     * Dashboard Monitoring PO Menggantung (Barang Belum Diterima Sepenuhnya)
     */
    public function outstandingPoIndex(Request $request)
    {
        [$project, $projects] = $this->getActiveProject($request);
        if (! $project) {
            return redirect()->route('projects.create')->with('info', 'Belum ada proyek aktif. Silakan buat proyek baru terlebih dahulu.');
        }

        $supplierFilter = $request->get('supplier_id');
        $statusFilter = $request->get('status', 'all'); // 'all', 'partial', 'unfulfilled', 'needs_do'
        $search = $request->get('search');

        $query = PurchaseOrder::where('project_id', $project->id)
            ->where('status', '!=', 'cancelled')
            ->with(['supplier', 'creator', 'items.material.defaultUnit', 'items.unit', 'deliveryOrders', 'directDeliveryOrders'])
            ->orderByDesc('po_date');

        if ($supplierFilter) {
            $query->where('supplier_id', $supplierFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                    ->orWhere('delivery_order_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('items.material', fn ($mq) => $mq->where('name', 'like', "%{$search}%"));
            });
        }

        // Enrich with fulfillment details (batch: 2 query untuk seluruh PO)
        $enrichedPos = PurchaseOrder::attachFulfillment($query->get());

        // Filter only outstanding POs (has pending items)
        $outstandingPos = $enrichedPos->filter(function ($po) use ($statusFilter) {
            if (! $po->fulfillment['has_pending']) {
                return false;
            }

            if ($statusFilter === 'partial') {
                return $po->fulfillment['is_partial'];
            } elseif ($statusFilter === 'unfulfilled') {
                return ! $po->fulfillment['has_received'];
            } elseif ($statusFilter === 'needs_do') {
                return $po->fulfillment['needs_new_do_number'];
            }

            return true;
        })->values();

        // Summary KPIs
        $totalPendingPos = $enrichedPos->filter(fn ($p) => $p->fulfillment['has_pending'])->count();
        $totalPartialPos = $enrichedPos->filter(fn ($p) => $p->fulfillment['is_partial'])->count();
        $totalNeedsDo = $enrichedPos->filter(fn ($p) => $p->fulfillment['needs_new_do_number'])->count();

        $totalPendingItemsCount = 0;
        $totalPendingValue = 0;
        foreach ($outstandingPos as $p) {
            foreach ($p->fulfillment['items'] as $item) {
                if ($item['qty_remaining'] > 0) {
                    $totalPendingItemsCount++;
                    $poItem = $p->items->firstWhere('id', $item['item_id']);
                    $price = $poItem ? (float) $poItem->unit_price : 0;
                    $totalPendingValue += ($item['qty_remaining'] * $price);
                }
            }
        }

        $suppliers = Supplier::whereHas('purchaseOrders', fn ($q) => $q->where('project_id', $project->id))->orderBy('name')->get();

        // Badge counters for navigation
        $poCount = Auth::user()->canReadPo() ? PurchaseOrder::where('project_id', $project->id)->count() : 0;
        $invCount = Auth::user()->canReadInvoice() ? Invoice::where('project_id', $project->id)->count() : 0;
        $pendingPoCount = $totalPendingPos;

        return view('procurement.outstanding_po', compact(
            'project',
            'projects',
            'outstandingPos',
            'totalPendingPos',
            'totalPartialPos',
            'totalNeedsDo',
            'totalPendingItemsCount',
            'totalPendingValue',
            'suppliers',
            'supplierFilter',
            'statusFilter',
            'search',
            'poCount',
            'invCount',
            'pendingPoCount'
        ));
    }
}
