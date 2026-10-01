<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Project;
use App\Models\PurchasePayment;
use App\Models\PurchasePaymentItem;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PurchasePaymentController extends Controller
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
     * Display a listing of purchase payments.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = Auth::user();
        if (! $user?->canReadPayment()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk melihat modul Pembayaran Pembelian.');
        }

        [$project, $projects] = $this->getActiveProject($request);
        if (! $project) {
            return redirect()->route('projects.create')
                ->with('info', 'Belum ada proyek aktif. Silakan buat proyek baru terlebih dahulu.');
        }

        $suppliers = Supplier::orderBy('name')->get();

        $payments = PurchasePayment::with(['supplier', 'creator', 'items.invoice'])
            ->where('project_id', $project->id)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        // Ambil faktur yang belum lunas atau baru dibayar sebagian pada proyek ini
        $unpaidInvoices = Invoice::with(['supplier', 'purchaseOrder'])
            ->where('project_id', $project->id)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->orderBy('invoice_date')
            ->get();

        $allProjectInvoices = Invoice::where('project_id', $project->id)->get();
        $totalInvoicedNet = (float) $allProjectInvoices->sum(function ($inv) {
            return $inv->net_amount;
        });
        $totalPaidInvoices = (float) $allProjectInvoices->sum(function ($inv) {
            return (float) $inv->paid_amount;
        });

        $stats = [
            'total_payments_amount' => (float) $payments->sum('total_amount'),
            'total_invoiced_net' => $totalInvoicedNet,
            'total_paid_invoices' => $totalPaidInvoices,
            'outstanding_amount' => max(0, $totalInvoicedNet - $totalPaidInvoices),
            'unpaid_count' => $unpaidInvoices->count(),
            'total_payments_count' => $payments->count(),
        ];

        return view('finance.payments.index', compact(
            'project',
            'projects',
            'suppliers',
            'payments',
            'unpaidInvoices',
            'stats'
        ));
    }

    /**
     * Store a newly created purchase payment.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user?->canWritePayment()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mencatat Pembayaran Pembelian.');
        }

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'payment_number' => 'required|string|max:100',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.invoice_id' => 'required|exists:invoices,id',
            'items.*.amount_paid' => 'required|numeric|min:1',
        ], [
            'supplier_id.required' => 'Pilih supplier/rekanan tujuan pembayaran.',
            'payment_number.required' => 'Nomor bukti pembayaran / voucher bank wajib diisi.',
            'items.required' => 'Pilih setidaknya satu faktur yang dibayar.',
            'items.*.amount_paid.min' => 'Nominal alokasi bayar harus lebih dari 0.',
        ]);

        $totalAmount = 0;
        foreach ($validated['items'] as $itemData) {
            $totalAmount += (float) $itemData['amount_paid'];
        }

        if ($totalAmount <= 0) {
            return back()->withInput()->with('error', 'Total pembayaran tidak boleh Rp 0.');
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('payments', 'public');
        }

        DB::transaction(function () use ($validated, $totalAmount, $attachmentPath, $user) {
            $payment = PurchasePayment::create([
                'project_id' => $validated['project_id'],
                'supplier_id' => $validated['supplier_id'],
                'payment_number' => $validated['payment_number'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'bank_name' => $validated['bank_name'] ?? null,
                'total_amount' => $totalAmount,
                'attachment_path' => $attachmentPath,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach ($validated['items'] as $itemData) {
                PurchasePaymentItem::create([
                    'purchase_payment_id' => $payment->id,
                    'invoice_id' => $itemData['invoice_id'],
                    'amount_paid' => $itemData['amount_paid'],
                ]);

                $invoice = Invoice::find($itemData['invoice_id']);
                if ($invoice) {
                    $invoice->recalculatePaymentStatus();
                }
            }
        });

        return redirect()->route('finance.payments.index', ['project_id' => $validated['project_id']])
            ->with('success', "Pembayaran Pembelian ({$validated['payment_number']}) sebesar Rp ".number_format($totalAmount, 0, ',', '.').' berhasil dicatat.');
    }

    /**
     * Remove the specified purchase payment.
     */
    public function destroy(PurchasePayment $purchasePayment): RedirectResponse
    {
        $user = Auth::user();
        if (! $user?->canWritePayment()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk menghapus Pembayaran Pembelian.');
        }

        $projectId = $purchasePayment->project_id;
        $paymentNumber = $purchasePayment->payment_number;

        DB::transaction(function () use ($purchasePayment) {
            // Ambil ID semua invoice yang terkait sebelum dihapus
            $affectedInvoiceIds = $purchasePayment->items()->pluck('invoice_id')->unique()->toArray();

            // Hapus items pembayaran
            $purchasePayment->items()->delete();

            // Hapus berkas lampiran
            if ($purchasePayment->attachment_path && Storage::disk('public')->exists($purchasePayment->attachment_path)) {
                Storage::disk('public')->delete($purchasePayment->attachment_path);
            }

            // Hapus pembayaran
            $purchasePayment->delete();

            // Rekalkulasi status pelunasan faktur yang terpengaruh
            foreach ($affectedInvoiceIds as $invId) {
                $invoice = Invoice::find($invId);
                if ($invoice) {
                    $invoice->recalculatePaymentStatus();
                }
            }
        });

        return redirect()->route('finance.payments.index', ['project_id' => $projectId])
            ->with('success', "Pembayaran Pembelian ({$paymentNumber}) berhasil dihapus dan status faktur telah diperbarui.");
    }
}
