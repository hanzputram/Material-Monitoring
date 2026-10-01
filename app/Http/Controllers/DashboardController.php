<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\DeliveryOrderItem;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\MaterialRealization;
use App\Models\MaterialVarianceValidation;
use App\Models\Project;
use App\Models\PurchaseOrderItem;
use App\Models\RabItemMaterial;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Resolve Active Project
        $projects = Project::orderBy('name')->get();
        if ($projects->isEmpty()) {
            return redirect()->route('projects.create')->with('info', 'Belum ada proyek aktif. Silakan buat proyek baru atau impor file Excel RAB terlebih dahulu.');
        }

        $projectId = $request->query('project_id') ?? session('active_project_id');
        $currentProject = $projectId ? Project::find($projectId) : $projects->first();
        if (! $currentProject) {
            $currentProject = $projects->first();
        }
        session(['active_project_id' => $currentProject->id]);

        // 2. Metrics & KPI
        $totalRab = (float) $currentProject->total_rab;
        $totalRealization = (float) $currentProject->total_realization;
        $costVariance = $totalRealization - $totalRab;
        $costVariancePct = $totalRab > 0 ? round(($costVariance / $totalRab) * 100, 2) : 0;

        $realizations = MaterialRealization::with(['material.defaultUnit', 'project'])
            ->where('project_id', $currentProject->id)
            ->get();

        $stats = [
            'total_rab' => $totalRab,
            'total_realization' => $totalRealization,
            'cost_variance' => $costVariance,
            'cost_variance_pct' => $costVariancePct,
            'materials_count' => $realizations->count(),
            'over_count' => $realizations->where('status', 'kelebihan')->count(),
            'under_count' => $realizations->where('status', 'kekurangan')->count(),
            'normal_count' => $realizations->where('status', 'normal')->count(),
            'alerts_unread' => Alert::where('project_id', $currentProject->id)->where('status', 'unread')->count(),
            'validations_pending' => MaterialVarianceValidation::whereHas('realization', function ($q) use ($currentProject) {
                $q->where('project_id', $currentProject->id);
            })->where('status', '!=', 'fully_validated')->count(),
        ];

        // 3. Category Cost Breakdown for Chart
        $chartCategories = [];
        $chartBudgets = [];
        $chartActuals = [];

        foreach ($currentProject->rootRabNodes as $node) {
            $chartCategories[] = $node->code.' '.(strlen($node->name) > 20 ? substr($node->name, 0, 18).'...' : $node->name);
            $chartBudgets[] = (float) $node->subtotal_cache;
            // Approximate actual based on invoices or proportional costs
            $chartActuals[] = (float) $node->subtotal_cache * 0.95; // Demo baseline
        }

        // 4. Recent Alerts
        $recentAlerts = Alert::where('project_id', $currentProject->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // 5. Recent Delivery Orders
        $recentDos = $currentProject->deliveryOrders()
            ->with(['supplier', 'receiver'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // 6. Hierarchical Categorization for Monitoring Cards (RAB Tree Layout)
        $allMappedMaterialIds = [];
        $treeRealizations = [];

        foreach ($currentProject->rootRabNodes as $root) {
            $categoryGroup = [
                'category' => $root,
                'sections' => [],
            ];

            foreach ($root->children as $sub) {
                // Direct materials under sub-category
                $subDirectMats = [];
                foreach ($sub->rabItems as $item) {
                    foreach ($item->materials as $im) {
                        if (! isset($subDirectMats[$im->material_id])) {
                            $rel = $realizations->firstWhere('material_id', $im->material_id);
                            if ($rel) {
                                $rel->rab_category_name = $root->name;
                                $rel->rab_category_code = $root->code;
                                $rel->rab_category_full = $root->code.'. '.$root->name;
                                $rel->rab_section_title = $sub->code.' '.$sub->name;
                                $rel->rab_section_subtitle = null;
                                $subDirectMats[$im->material_id] = $rel;
                                $allMappedMaterialIds[] = $im->material_id;
                            }
                        }
                    }
                }

                if (! empty($subDirectMats)) {
                    $categoryGroup['sections'][] = [
                        'title' => $sub->code.' '.$sub->name,
                        'subtitle' => null,
                        'materials' => array_values($subDirectMats),
                    ];
                }

                // Materials under sub-sub-categories
                foreach ($sub->children as $subSub) {
                    $subSubMats = [];
                    foreach ($subSub->rabItems as $item) {
                        foreach ($item->materials as $im) {
                            if (! isset($subSubMats[$im->material_id])) {
                                $rel = $realizations->firstWhere('material_id', $im->material_id);
                                if ($rel) {
                                    $rel->rab_category_name = $root->name;
                                    $rel->rab_category_code = $root->code;
                                    $rel->rab_category_full = $root->code.'. '.$root->name;
                                    $rel->rab_section_title = $sub->code.' '.$sub->name;
                                    $rel->rab_section_subtitle = $subSub->code.' '.$subSub->name;
                                    $subSubMats[$im->material_id] = $rel;
                                    $allMappedMaterialIds[] = $im->material_id;
                                }
                            }
                        }
                    }

                    if (! empty($subSubMats)) {
                        $categoryGroup['sections'][] = [
                            'title' => $sub->code.' '.$sub->name,
                            'subtitle' => $subSub->code.' '.$subSub->name,
                            'materials' => array_values($subSubMats),
                        ];
                    }
                }
            }

            if (! empty($categoryGroup['sections'])) {
                $treeRealizations[] = $categoryGroup;
            }
        }

        // 7. Non-RAB Purchases Recap (Pembelian & Pengadaan di Luar RAB / Unbudgeted)
        // Pengawasan material yang dipesan / diterima tetapi TIDAK ADA di struktur alokasi RAB Tree proyek
        $nonRabPoItems = PurchaseOrderItem::whereHas('purchaseOrder', function ($q) use ($currentProject) {
            $q->where('project_id', $currentProject->id);
        })
            ->whereNotIn('material_id', $allMappedMaterialIds)
            ->with(['material.defaultUnit', 'purchaseOrder.supplier', 'unit'])
            ->get();

        $nonRabDoItems = DeliveryOrderItem::whereHas('deliveryOrder', function ($q) use ($currentProject) {
            $q->where('project_id', $currentProject->id);
        })
            ->whereNotIn('material_id', $allMappedMaterialIds)
            ->with(['material.defaultUnit', 'deliveryOrder.supplier', 'deliveryOrder.receiver', 'unit'])
            ->get();

        $nonRabRealizations = $realizations->whereNotIn('material_id', $allMappedMaterialIds);

        $allNonRabMaterialIds = array_unique(array_merge(
            $nonRabPoItems->pluck('material_id')->toArray(),
            $nonRabDoItems->pluck('material_id')->toArray(),
            $nonRabRealizations->pluck('material_id')->toArray()
        ));

        $nonRabPurchases = [];
        $totalNonRabPoCost = 0;
        $totalNonRabDoQty = 0;

        foreach ($allNonRabMaterialIds as $matId) {
            $mPoItems = $nonRabPoItems->where('material_id', $matId);
            $mDoItems = $nonRabDoItems->where('material_id', $matId);
            $mRealization = $nonRabRealizations->firstWhere('material_id', $matId);

            $mat = $mPoItems->first()?->material
                ?? $mDoItems->first()?->material
                ?? $mRealization?->material
                ?? Material::with('defaultUnit')->find($matId);

            if (! $mat) {
                continue;
            }

            $poQty = (float) $mPoItems->sum('qty_ordered');
            $poCost = (float) $mPoItems->sum(fn ($it) => (float) $it->qty_ordered * (float) $it->unit_price);
            $doQty = (float) $mDoItems->sum('qty_received');

            $totalNonRabPoCost += $poCost;
            $totalNonRabDoQty += $doQty;

            $pos = $mPoItems->map(fn ($it) => $it->purchaseOrder)->filter()->unique('id');
            $dos = $mDoItems->map(fn ($it) => $it->deliveryOrder)->filter()->unique('id');

            // Fulfillment status
            if ($poQty > 0 && $doQty >= $poQty) {
                $statusType = 'completed';
                $statusLabel = 'Tiba Lengkap (100%)';
                $badgeCls = 'bg-emerald-100 text-emerald-800 border-emerald-200';
            } elseif ($doQty > 0 && $poQty > 0) {
                $pct = round(($doQty / $poQty) * 100, 1);
                $statusType = 'partial';
                $statusLabel = "Sebagian Masuk ({$pct}%)";
                $badgeCls = 'bg-blue-100 text-blue-800 border-blue-200';
            } elseif ($poQty > 0 && $doQty == 0) {
                $statusType = 'pending';
                $statusLabel = 'Menunggu Kirim (PO Sent)';
                $badgeCls = 'bg-amber-100 text-amber-800 border-amber-200';
            } else {
                $statusType = 'unplanned_do';
                $statusLabel = 'Fisik Tiba Tanpa PO';
                $badgeCls = 'bg-purple-100 text-purple-800 border-purple-200';
            }

            $nonRabPurchases[] = [
                'material_id' => $mat->id,
                'material_code' => $mat->code,
                'material_name' => $mat->name,
                'category' => $mat->category,
                'unit' => $mat->defaultUnit?->code ?? '-',
                'po_qty' => $poQty,
                'po_cost' => $poCost,
                'avg_unit_price' => $poQty > 0 ? ($poCost / $poQty) : (float) ($mat->standard_price ?? 0),
                'do_qty' => $doQty,
                'pos' => $pos->values()->all(),
                'dos' => $dos->values()->all(),
                'status_type' => $statusType,
                'status_label' => $statusLabel,
                'badge_cls' => $badgeCls,
                'realization' => $mRealization,
            ];
        }

        // Summary KPI stats for Non-RAB
        $stats['non_rab_total_cost'] = $totalNonRabPoCost;
        $stats['non_rab_items_count'] = count($nonRabPurchases);
        $stats['non_rab_po_count'] = $nonRabPoItems->pluck('purchase_order_id')->unique()->count();
        $stats['non_rab_do_count'] = $nonRabDoItems->pluck('delivery_order_id')->unique()->count();
        $stats['non_rab_do_received_qty'] = $totalNonRabDoQty;

        return view('dashboard.index', compact(
            'projects',
            'currentProject',
            'stats',
            'realizations',
            'treeRealizations',
            'nonRabPurchases',
            'chartCategories',
            'chartBudgets',
            'chartActuals',
            'recentAlerts',
            'recentDos'
        ));
    }

    public function showMaterialDetail(Request $request, $id)
    {
        $realization = MaterialRealization::with(['material.defaultUnit', 'project'])->findOrFail($id);
        $currentProject = $realization->project;
        $projects = Project::orderBy('name')->get();

        // 1. Delivery Order receipts for this material and project
        $deliveryOrderItems = DeliveryOrderItem::where('material_id', $realization->material_id)
            ->whereHas('deliveryOrder', function ($q) use ($currentProject) {
                $q->where('project_id', $currentProject->id);
            })
            ->with([
                'deliveryOrder.supplier',
                'deliveryOrder.receiver',
                'deliveryOrder.purchaseOrders',
                'deliveryOrder.purchaseOrder',
                'purchaseOrder',
                'unit',
            ])
            ->orderByDesc('id')
            ->get();

        // 2. Purchase Order items for this material and project
        $purchaseOrderItems = PurchaseOrderItem::where('material_id', $realization->material_id)
            ->whereHas('purchaseOrder', function ($q) use ($currentProject) {
                $q->where('project_id', $currentProject->id);
            })
            ->with(['purchaseOrder.supplier', 'purchaseOrder.creator', 'rabItem.rabNode', 'unit'])
            ->orderByDesc('id')
            ->get();

        // 3. Invoices related to this material or through validations
        $invoices = Invoice::where('project_id', $currentProject->id)
            ->where(function ($query) use ($realization) {
                $query->whereHas('purchaseOrder.items', function ($q) use ($realization) {
                    $q->where('material_id', $realization->material_id);
                })->orWhereHas('varianceValidations', function ($q) use ($realization) {
                    $q->where('material_realization_id', $realization->id);
                });
            })
            ->with(['supplier', 'validator', 'purchaseOrder'])
            ->orderByDesc('invoice_date')
            ->get();

        // 4. Dual Approval Variance Validations
        $validations = MaterialVarianceValidation::where('material_realization_id', $realization->id)
            ->with(['deliveryOrder.supplier', 'invoice.supplier', 'pengawasUser', 'purchasingUser'])
            ->get();

        // 5. RAB Tree Allocations (Where in the project's RAB is this material budgeted)
        $rabAllocations = RabItemMaterial::where('material_id', $realization->material_id)
            ->whereHas('rabItem.rabNode', function ($q) use ($currentProject) {
                $q->where('project_id', $currentProject->id);
            })
            ->with(['rabItem.rabNode.parent', 'unit', 'rabItem.unit'])
            ->get();

        return view('dashboard.material_detail', compact(
            'realization',
            'currentProject',
            'projects',
            'deliveryOrderItems',
            'purchaseOrderItems',
            'invoices',
            'validations',
            'rabAllocations'
        ));
    }

    public function switchProject(Request $request)
    {
        $request->validate(['project_id' => 'required|exists:projects,id']);
        session(['active_project_id' => $request->project_id]);

        return redirect()->back();
    }
}
