<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\MaterialRealization;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        if (!$project) {
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

        abort(403, 'Anda tidak memiliki hak akses untuk membuka modul Pengadaan, Surat Jalan, maupun Faktur.');
    }

    // ============================================================
    // 1. SUBMODUL PURCHASE ORDER (PO)
    // ============================================================

    public function poIndex(Request $request)
    {
        if (!Auth::user()->canReadPo()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk melihat modul Purchase Order.');
        }

        [$project, $projects] = $this->getActiveProject($request);
        if (!$project) {
            return redirect()->route('projects.create')->with('info', 'Belum ada proyek aktif. Silakan buat proyek baru terlebih dahulu.');
        }

        $suppliers = Supplier::orderBy('name')->get();
        $materials = Material::where('is_active', true)->orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        $purchaseOrders = PurchaseOrder::with(['supplier', 'creator', 'items.material.defaultUnit', 'deliveryOrders', 'invoices'])
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
            'purchaseOrders',
            'doCount',
            'invCount'
        ));
    }

    public function storePo(Request $request)
    {
        if (!Auth::user()->canWritePo()) {
            abort(403, 'Akses Ditolak: Anda hanya memiliki izin Lihat Saja (Read-Only) dan tidak dapat membuat Purchase Order.');
        }

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'po_number' => 'required|string|unique:purchase_orders,po_number',
            'po_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
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
                'qty_ordered' => $item['qty_ordered'],
                'unit_id' => $item['unit_id'],
                'unit_price' => $item['unit_price'],
            ]);
        }

        return redirect()->route('procurement.po.index', ['project_id' => $po->project_id])
            ->with('success', "Purchase Order {$po->po_number} berhasil dibuat!");
    }

    // ============================================================
    // 2. SUBMODUL SURAT JALAN (DO LAPANGAN)
    // ============================================================

    public function doIndex(Request $request)
    {
        if (!Auth::user()->canReadDo()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk melihat modul Surat Jalan (DO).');
        }

        [$project, $projects] = $this->getActiveProject($request);
        if (!$project) {
            return redirect()->route('projects.create')->with('info', 'Belum ada proyek aktif. Silakan buat proyek baru terlebih dahulu.');
        }

        $suppliers = Supplier::orderBy('name')->get();
        $materials = Material::where('is_active', true)->orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        $deliveryOrders = DeliveryOrder::with(['supplier', 'receiver', 'items.material.defaultUnit', 'purchaseOrder'])
            ->where('project_id', $project->id)
            ->orderByDesc('do_date')
            ->get();

        $purchaseOrders = PurchaseOrder::where('project_id', $project->id)->orderByDesc('po_date')->get();

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
        if (!Auth::user()->canWriteDo()) {
            abort(403, 'Akses Ditolak: Anda hanya memiliki izin Lihat Saja (Read-Only) dan tidak dapat menginput Surat Jalan (DO).');
        }

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'do_number' => 'required|string',
            'do_date' => 'required|date',
            'attachment' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // Mandatory physical proof
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.qty_received' => 'required|numeric|min:0.0001',
            'items.*.unit_id' => 'required|exists:units,id',
        ]);

        $filePath = $request->file('attachment')->store('delivery_orders', 'public');

        $do = DeliveryOrder::create([
            'project_id' => $validated['project_id'],
            'purchase_order_id' => $validated['purchase_order_id'] ?? null,
            'supplier_id' => $validated['supplier_id'],
            'do_number' => $validated['do_number'],
            'do_date' => $validated['do_date'],
            'attachment_path' => $filePath,
            'received_by' => Auth::id(),
            'status' => 'validated',
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            DeliveryOrderItem::create([
                'delivery_order_id' => $do->id,
                'material_id' => $item['material_id'],
                'qty_received' => $item['qty_received'],
                'unit_id' => $item['unit_id'],
            ]);
        }

        // Recalculate material realization & variance
        $do->syncRealizations();

        return redirect()->route('procurement.do.index', ['project_id' => $do->project_id])
            ->with('success', "Surat Jalan (DO) {$do->do_number} dan bukti fisik berhasil dicatat!");
    }

    // ============================================================
    // 3. SUBMODUL FAKTUR TAGIHAN (INVOICE)
    // ============================================================

    public function invoiceIndex(Request $request)
    {
        if (!Auth::user()->canReadInvoice()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk melihat modul Faktur Tagihan (Invoice).');
        }

        [$project, $projects] = $this->getActiveProject($request);
        if (!$project) {
            return redirect()->route('projects.create')->with('info', 'Belum ada proyek aktif. Silakan buat proyek baru terlebih dahulu.');
        }

        $suppliers = Supplier::orderBy('name')->get();
        $invoices = Invoice::with(['supplier', 'validator', 'purchaseOrder'])
            ->where('project_id', $project->id)
            ->orderByDesc('invoice_date')
            ->get();

        $purchaseOrders = PurchaseOrder::where('project_id', $project->id)->orderByDesc('po_date')->get();

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
            'poCount',
            'doCount'
        ));
    }

    public function storeInvoice(Request $request)
    {
        if (!Auth::user()->canWriteInvoice()) {
            abort(403, 'Akses Ditolak: Anda hanya memiliki izin Lihat Saja (Read-Only) dan tidak dapat menginput Faktur Tagihan (Invoice).');
        }

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'required|string',
            'invoice_date' => 'required|date',
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
            'amount' => $validated['amount'],
            'attachment_path' => $filePath,
            'validated_by' => Auth::id(),
            'status' => 'validated',
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('procurement.invoices.index', ['project_id' => $invoice->project_id])
            ->with('success', "Faktur Tagihan (Invoice) {$invoice->invoice_number} dan berkas lampiran berhasil dicatat!");
    }
}
