<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\PurchaseDownPayment;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PurchaseDownPaymentController extends Controller
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
     * Display a listing of purchase down payments.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = Auth::user();
        if (! $user?->canReadDownPayment()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk melihat modul Uang Muka Pembelian.');
        }

        [$project, $projects] = $this->getActiveProject($request);
        if (! $project) {
            return redirect()->route('projects.create')
                ->with('info', 'Belum ada proyek aktif. Silakan buat proyek baru terlebih dahulu.');
        }

        $suppliers = Supplier::orderBy('name')->get();
        $purchaseOrders = PurchaseOrder::where('project_id', $project->id)
            ->orderByDesc('po_date')
            ->get();

        $downPayments = PurchaseDownPayment::with(['supplier', 'purchaseOrder', 'creator', 'invoices'])
            ->where('project_id', $project->id)
            ->orderByDesc('dp_date')
            ->orderByDesc('id')
            ->get();

        $stats = [
            'total_dp_amount' => (float) $downPayments->sum('amount'),
            'applied_dp_amount' => (float) $downPayments->where('status', 'applied')->sum('amount'),
            'available_dp_amount' => (float) $downPayments->where('status', 'paid')->sum('amount'),
            'total_count' => $downPayments->count(),
        ];

        return view('finance.down_payments.index', compact(
            'project',
            'projects',
            'suppliers',
            'purchaseOrders',
            'downPayments',
            'stats'
        ));
    }

    /**
     * Store a newly created purchase down payment.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user?->canWriteDownPayment()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mencatat Uang Muka Pembelian.');
        }

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'dp_number' => 'required|string|max:100',
            'dp_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'notes' => 'nullable|string|max:1000',
        ], [
            'supplier_id.required' => 'Pilih supplier/toko penerima uang muka.',
            'dp_number.required' => 'Nomor bukti DP / kuitansi wajib diisi.',
            'amount.min' => 'Nominal uang muka harus lebih dari 0.',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('down_payments', 'public');
        }

        $downPayment = PurchaseDownPayment::create([
            'project_id' => $validated['project_id'],
            'supplier_id' => $validated['supplier_id'],
            'purchase_order_id' => $validated['purchase_order_id'] ?? null,
            'dp_number' => $validated['dp_number'],
            'dp_date' => $validated['dp_date'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'bank_name' => $validated['bank_name'] ?? null,
            'status' => 'paid',
            'attachment_path' => $attachmentPath,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $user->id,
        ]);

        return redirect()->route('finance.down-payments.index', ['project_id' => $downPayment->project_id])
            ->with('success', "Uang Muka Pembelian ({$downPayment->dp_number}) sebesar Rp ".number_format($downPayment->amount, 0, ',', '.').' berhasil dicatat.');
    }

    /**
     * Remove the specified purchase down payment.
     */
    public function destroy(PurchaseDownPayment $purchaseDownPayment): RedirectResponse
    {
        $user = Auth::user();
        if (! $user?->canWriteDownPayment()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk menghapus Uang Muka Pembelian.');
        }

        if ($purchaseDownPayment->invoices()->exists() || $purchaseDownPayment->status === 'applied') {
            return back()->with('error', "Uang Muka {$purchaseDownPayment->dp_number} tidak dapat dihapus karena telah teraplikasi atau terikat pada Faktur Pembelian.");
        }

        $projectId = $purchaseDownPayment->project_id;
        $dpNumber = $purchaseDownPayment->dp_number;

        if ($purchaseDownPayment->attachment_path && Storage::disk('public')->exists($purchaseDownPayment->attachment_path)) {
            Storage::disk('public')->delete($purchaseDownPayment->attachment_path);
        }

        $purchaseDownPayment->delete();

        return redirect()->route('finance.down-payments.index', ['project_id' => $projectId])
            ->with('success', "Uang Muka Pembelian ({$dpNumber}) berhasil dihapus.");
    }
}
