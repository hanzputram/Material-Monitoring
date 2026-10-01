<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FinanceSummaryController extends Controller
{
    /**
     * Cross-project supplier purchasing & payment summary.
     */
    public function supplierSummary(Request $request): View
    {
        $user = Auth::user();
        if (! $user?->canReadInvoice() && ! $user?->canReadPayment() && ! $user?->hasModulePermission('finance') && ! $user?->isSuperAdmin()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk melihat Ringkasan Keuangan Pembelian Toko.');
        }

        $allProjects = Project::orderBy('name')->get();
        $selectedProjectId = $request->query('project_id'); // null or 'all' means cross-project
        $search = $request->query('search');
        $debtStatus = $request->query('status'); // 'unpaid', 'paid', or null

        $suppliersQuery = Supplier::with([
            'purchaseOrders' => function ($q) use ($selectedProjectId) {
                if ($selectedProjectId && $selectedProjectId !== 'all') {
                    $q->where('project_id', $selectedProjectId);
                }
                $q->with(['project', 'items']);
            },
            'deliveryOrders' => function ($q) use ($selectedProjectId) {
                if ($selectedProjectId && $selectedProjectId !== 'all') {
                    $q->where('project_id', $selectedProjectId);
                }
                $q->with('project');
            },
            'invoices' => function ($q) use ($selectedProjectId) {
                if ($selectedProjectId && $selectedProjectId !== 'all') {
                    $q->where('project_id', $selectedProjectId);
                }
                $q->with('project');
            },
            'downPayments' => function ($q) use ($selectedProjectId) {
                if ($selectedProjectId && $selectedProjectId !== 'all') {
                    $q->where('project_id', $selectedProjectId);
                }
                $q->with('project');
            },
            'payments' => function ($q) use ($selectedProjectId) {
                if ($selectedProjectId && $selectedProjectId !== 'all') {
                    $q->where('project_id', $selectedProjectId);
                }
                $q->with('project');
            },
            'returns' => function ($q) use ($selectedProjectId) {
                if ($selectedProjectId && $selectedProjectId !== 'all') {
                    $q->where('project_id', $selectedProjectId);
                }
                $q->with(['project', 'items']);
            },
        ]);

        if ($search) {
            $suppliersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $rawSuppliers = $suppliersQuery->orderBy('name')->get();

        // Olah metrik komprehensif per supplier
        $supplierSummaries = $rawSuppliers->map(function ($supplier) {
            $pos = $supplier->purchaseOrders;
            $dos = $supplier->deliveryOrders;
            $invoices = $supplier->invoices;
            $dps = $supplier->downPayments;
            $payments = $supplier->payments;
            $returns = $supplier->returns;

            $totalPoAmount = (float) $pos->sum(function ($po) {
                return $po->total_amount;
            });

            $totalInvoiceGross = (float) $invoices->sum('amount');
            $totalInvoiceNet = (float) $invoices->sum(function ($inv) {
                return $inv->net_amount;
            });
            $totalDpAmount = (float) $dps->sum('amount');
            $totalPaidAmount = (float) $invoices->sum('paid_amount');
            $outstandingBalance = max(0, $totalInvoiceNet - $totalPaidAmount);

            $totalReturnAmount = (float) $returns->sum(function ($ret) {
                return $ret->total_amount;
            });

            // Kumpulkan daftar proyek unik yang terlibat
            $involvedProjectIds = collect()
                ->merge($pos->pluck('project_id'))
                ->merge($dos->pluck('project_id'))
                ->merge($invoices->pluck('project_id'))
                ->merge($dps->pluck('project_id'))
                ->merge($payments->pluck('project_id'))
                ->unique()
                ->filter();

            // Breakdown rincian per proyek untuk supplier ini
            $projectBreakdowns = $involvedProjectIds->map(function ($projId) use ($pos, $dos, $invoices, $dps, $returns) {
                $projPos = $pos->where('project_id', $projId);
                $projDos = $dos->where('project_id', $projId);
                $projInvoices = $invoices->where('project_id', $projId);
                $projDps = $dps->where('project_id', $projId);
                $projReturns = $returns->where('project_id', $projId);

                $projectName = $projPos->first()?->project?->name
                    ?? $projDos->first()?->project?->name
                    ?? $projInvoices->first()?->project?->name
                    ?? ('Proyek #'.$projId);

                $projPoTotal = (float) $projPos->sum(function ($po) {
                    return $po->total_amount;
                });
                $projInvNet = (float) $projInvoices->sum(function ($inv) {
                    return $inv->net_amount;
                });
                $projPaid = (float) $projInvoices->sum('paid_amount');
                $projOutstanding = max(0, $projInvNet - $projPaid);

                return [
                    'project_id' => $projId,
                    'project_name' => $projectName,
                    'po_count' => $projPos->count(),
                    'po_total' => $projPoTotal,
                    'do_count' => $projDos->count(),
                    'invoice_count' => $projInvoices->count(),
                    'invoice_total' => $projInvNet,
                    'dp_total' => (float) $projDps->sum('amount'),
                    'paid_total' => $projPaid,
                    'outstanding' => $projOutstanding,
                    'return_count' => $projReturns->count(),
                ];
            })->values();

            return [
                'supplier' => $supplier,
                'po_count' => $pos->count(),
                'total_po_amount' => $totalPoAmount,
                'do_count' => $dos->count(),
                'invoice_count' => $invoices->count(),
                'total_invoice_gross' => $totalInvoiceGross,
                'total_invoice_net' => $totalInvoiceNet,
                'total_dp_amount' => $totalDpAmount,
                'total_paid_amount' => $totalPaidAmount,
                'outstanding_balance' => $outstandingBalance,
                'total_return_amount' => $totalReturnAmount,
                'projects_count' => $involvedProjectIds->count(),
                'project_breakdowns' => $projectBreakdowns,
                'is_settled' => ($outstandingBalance <= 0 && $invoices->isNotEmpty()),
                'has_debt' => ($outstandingBalance > 0),
            ];
        });

        // Filter status hutang jika diminta
        if ($debtStatus === 'unpaid') {
            $supplierSummaries = $supplierSummaries->filter(function ($item) {
                return $item['has_debt'];
            });
        } elseif ($debtStatus === 'paid') {
            $supplierSummaries = $supplierSummaries->filter(function ($item) {
                return $item['is_settled'];
            });
        }

        // Global Cross-Project Stats
        $globalStats = [
            'total_suppliers_active' => $supplierSummaries->count(),
            'grand_po_amount' => (float) $supplierSummaries->sum('total_po_amount'),
            'grand_invoice_amount' => (float) $supplierSummaries->sum('total_invoice_net'),
            'grand_paid_amount' => (float) $supplierSummaries->sum('total_paid_amount'),
            'grand_outstanding' => (float) $supplierSummaries->sum('outstanding_balance'),
            'grand_dp_amount' => (float) $supplierSummaries->sum('total_dp_amount'),
        ];

        return view('finance.supplier_summary', compact(
            'supplierSummaries',
            'globalStats',
            'allProjects',
            'selectedProjectId',
            'search',
            'debtStatus'
        ));
    }
}
