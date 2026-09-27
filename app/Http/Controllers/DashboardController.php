<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\MaterialRealization;
use App\Models\MaterialVarianceValidation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        if (!$currentProject) {
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
            $chartCategories[] = $node->code . ' ' . (strlen($node->name) > 20 ? substr($node->name, 0, 18) . '...' : $node->name);
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
                'sections' => []
            ];

            foreach ($root->children as $sub) {
                // Direct materials under sub-category
                $subDirectMats = [];
                foreach ($sub->rabItems as $item) {
                    foreach ($item->materials as $im) {
                        if (!isset($subDirectMats[$im->material_id])) {
                            $rel = $realizations->firstWhere('material_id', $im->material_id);
                            if ($rel) {
                                $subDirectMats[$im->material_id] = $rel;
                                $allMappedMaterialIds[] = $im->material_id;
                            }
                        }
                    }
                }

                if (!empty($subDirectMats)) {
                    $categoryGroup['sections'][] = [
                        'title' => $sub->code . ' ' . $sub->name,
                        'subtitle' => null,
                        'materials' => array_values($subDirectMats)
                    ];
                }

                // Materials under sub-sub-categories
                foreach ($sub->children as $subSub) {
                    $subSubMats = [];
                    foreach ($subSub->rabItems as $item) {
                        foreach ($item->materials as $im) {
                            if (!isset($subSubMats[$im->material_id])) {
                                $rel = $realizations->firstWhere('material_id', $im->material_id);
                                if ($rel) {
                                    $subSubMats[$im->material_id] = $rel;
                                    $allMappedMaterialIds[] = $im->material_id;
                                }
                            }
                        }
                    }

                    if (!empty($subSubMats)) {
                        $categoryGroup['sections'][] = [
                            'title' => $sub->code . ' ' . $sub->name,
                            'subtitle' => $subSub->code . ' ' . $subSub->name,
                            'materials' => array_values($subSubMats)
                        ];
                    }
                }
            }

            if (!empty($categoryGroup['sections'])) {
                $treeRealizations[] = $categoryGroup;
            }
        }

        // Catch any realizations not explicitly under the mapped nodes
        $unmapped = $realizations->whereNotIn('material_id', $allMappedMaterialIds);
        if ($unmapped->isNotEmpty()) {
            $treeRealizations[] = [
                'category' => (object)[
                    'code' => 'UMUM',
                    'name' => 'MATERIAL UMUM & LAIN-LAIN'
                ],
                'sections' => [
                    [
                        'title' => 'Daftar Material Tambahan',
                        'subtitle' => null,
                        'materials' => $unmapped->values()->all()
                    ]
                ]
            ];
        }

        return view('dashboard.index', compact(
            'projects',
            'currentProject',
            'stats',
            'realizations',
            'treeRealizations',
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
        $deliveryOrderItems = \App\Models\DeliveryOrderItem::where('material_id', $realization->material_id)
            ->whereHas('deliveryOrder', function ($q) use ($currentProject) {
                $q->where('project_id', $currentProject->id);
            })
            ->with(['deliveryOrder.supplier', 'deliveryOrder.receiver', 'unit'])
            ->orderByDesc('id')
            ->get();

        // 2. Invoices related to this material or through validations
        $invoices = \App\Models\Invoice::where('project_id', $currentProject->id)
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

        // 3. Dual Approval Variance Validations
        $validations = \App\Models\MaterialVarianceValidation::where('material_realization_id', $realization->id)
            ->with(['deliveryOrder.supplier', 'invoice.supplier', 'pengawasUser', 'purchasingUser'])
            ->get();

        // 4. RAB Tree Allocations (Where in the project's RAB is this material budgeted)
        $rabAllocations = \App\Models\RabItemMaterial::where('material_id', $realization->material_id)
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
