<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\MaterialRealization;
use App\Models\Project;
use App\Models\PurchaseDownPayment;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\RabItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\PurchaseOrderPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $materials = Material::where('is_active', true)->orderBy('name')->get();
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
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.rab_item_id' => 'nullable|exists:rab_items,id',
            'items.*.qty_ordered' => 'required|numeric|min:0.0001',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $po = PurchaseOrder::create([
            'project_id' => $validated['project_id'],
            'supplier_id' => $validated['supplier_id'],
            'po_number' => $validated['po_number'],
            'po_date' => $validated['po_date'],
            'notes' => $validated['notes'],
            'status' => 'sent',
            'created_by' => Auth::id(),
        ]);

        foreach ($validated['items'] as $item) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'material_id' => $item['material_id'],
                'rab_item_id' => ! empty($item['rab_item_id']) ? $item['rab_item_id'] : null,
                'qty_ordered' => $item['qty_ordered'],
                'unit_id' => $item['unit_id'],
                'unit_price' => $item['unit_price'],
            ]);
        }

        return redirect()->route('procurement.po.index', ['project_id' => $po->project_id])
            ->with('success', "Pesanan Pembelian (PO) {$po->po_number} berhasil dibuat!");
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
        $materials = Material::where('is_active', true)->orderBy('name')->get();
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
            ->with(['supplier', 'items.material.defaultUnit'])
            ->orderByDesc('po_date')
            ->get();

        // Badge counters
        $poCount = Auth::user()->canReadPo()
            ? PurchaseOrder::where('project_id', $project->id)->count()
            : 0;
        $invCount = Auth::user()->canReadInvoice()
            ? Invoice::where('project_id', $project->id)->count()
            : 0;

        return view('procurement.do', compact(
            'project',
            'projects',
            'suppliers',
            'materials',
            'units',
            'deliveryOrders',
            'purchaseOrders',
            'poCount',
            'invCount'
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

        $do = DeliveryOrder::create([
            'project_id' => $validated['project_id'],
            'purchase_order_id' => $primaryPoId,
            'supplier_id' => $validated['supplier_id'],
            'do_number' => $validated['do_number'],
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
            $itemPoId = ! empty($item['purchase_order_id'])
                ? (int) $item['purchase_order_id']
                : null;

            DeliveryOrderItem::create([
                'delivery_order_id' => $do->id,
                'purchase_order_id' => $itemPoId,
                'material_id' => $item['material_id'],
                'qty_received' => $item['qty_received'],
                'unit_id' => $item['unit_id'],
            ]);
        }

        // Recalculate material realization & variance
        $do->syncRealizations();

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
        $invoices = Invoice::with(['supplier', 'validator', 'purchaseOrder', 'downPayment', 'payments'])
            ->where('project_id', $project->id)
            ->orderByDesc('invoice_date')
            ->get();

        $purchaseOrders = PurchaseOrder::where('project_id', $project->id)->orderByDesc('po_date')->get();

        // Uang muka yang masih tersedia untuk diaplikasikan ke faktur
        $availableDownPayments = PurchaseDownPayment::where('project_id', $project->id)
            ->where('status', 'paid')
            ->get();

        // Badge counters
        $poCount = Auth::user()->canReadPo()
            ? PurchaseOrder::where('project_id', $project->id)->count()
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
            'availableDownPayments',
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
            'down_payment_id' => 'nullable|exists:purchase_down_payments,id',
            'attachment' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // Mandatory proof
            'notes' => 'nullable|string',
        ]);

        $filePath = $request->file('attachment')->store('invoices', 'public');

        $dp = null;
        $dpAmount = 0;
        if (! empty($validated['down_payment_id'])) {
            $dp = PurchaseDownPayment::find($validated['down_payment_id']);
            if ($dp) {
                $dpAmount = min((float) $validated['amount'], (float) $dp->amount);
                $dp->status = 'applied';
                $dp->save();
            }
        }

        $netAmount = max(0, (float) $validated['amount'] - $dpAmount);
        $paymentStatus = ($netAmount <= 0) ? 'paid' : 'unpaid';

        $invoice = Invoice::create([
            'project_id' => $validated['project_id'],
            'purchase_order_id' => $validated['purchase_order_id'] ?? null,
            'supplier_id' => $validated['supplier_id'],
            'invoice_number' => $validated['invoice_number'],
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'] ?? null,
            'amount' => $validated['amount'],
            'down_payment_id' => $dp?->id,
            'down_payment_amount' => $dpAmount,
            'paid_amount' => 0,
            'payment_status' => $paymentStatus,
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

        // Jika memiliki DP yang terikat, kembalikan status DP menjadi 'paid' (belum diaplikasikan)
        if ($invoice->down_payment_id && $invoice->downPayment) {
            $invoice->downPayment->status = 'paid';
            $invoice->downPayment->save();
        }

        if ($invoice->attachment_path && Storage::disk('public')->exists($invoice->attachment_path)) {
            Storage::disk('public')->delete($invoice->attachment_path);
        }

        $invoice->delete();

        return redirect()->route('procurement.invoices.index', ['project_id' => $projectId])
            ->with('success', "Faktur Pembelian {$invoiceNumber} berhasil dihapus.");
    }
}
