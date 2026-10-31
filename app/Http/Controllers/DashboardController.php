<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\DeliveryOrderItem;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\MaterialRealization;
use App\Models\MaterialVarianceValidation;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\RabItemMaterial;
use App\Models\RabNode;
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

        // Pohon RAB beserta item, BOM, dan data biaya aktual dimuat sekali (bukan lazy per node)
        $currentProject->load([
            'rootRabNodes' => fn ($q) => $q->with(RabNode::treeRelations([
                'materials.breakdowns',
                'costRealization',
                'purchaseOrderItems.purchaseOrder',
            ])),
        ]);

        $realizations = MaterialRealization::with(['material.defaultUnit', 'project'])
            ->where('project_id', $currentProject->id)
            ->get();
        $realizationsByMaterial = $realizations->keyBy('material_id');

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
            $chartActuals[] = round((float) $node->actual_cost_total, 2);
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

            // 6.a. Direct materials & items under Root Category (Level 1)
            $rootDirectMats = [];
            foreach ($root->rabItems as $item) {
                foreach ($item->materials as $im) {
                    $itemMats = collect([$im])->merge($im->breakdowns);
                    foreach ($itemMats as $m) {
                        if (! isset($rootDirectMats[$m->material_id])) {
                            $rel = $realizationsByMaterial->get($m->material_id);
                            if ($rel) {
                                $rel->rab_category_name = $root->name;
                                $rel->rab_category_code = $root->code;
                                $rel->rab_category_full = $root->code.'. '.$root->name;
                                $rel->rab_section_title = $root->code.' '.$root->name;
                                $rel->rab_section_subtitle = $item->name;
                                $rootDirectMats[$m->material_id] = $rel;
                            }
                            $allMappedMaterialIds[] = $m->material_id;
                        }
                    }
                }
            }

            if (! empty($rootDirectMats)) {
                $categoryGroup['sections'][] = [
                    'title' => $root->code.' '.$root->name,
                    'subtitle' => null,
                    'materials' => array_values($rootDirectMats),
                ];
            }

            // 6.b. Materials under Sub-Categories (Level 2)
            foreach ($root->children as $sub) {
                $subDirectMats = [];
                foreach ($sub->rabItems as $item) {
                    foreach ($item->materials as $im) {
                        $itemMats = collect([$im])->merge($im->breakdowns);
                        foreach ($itemMats as $m) {
                            if (! isset($subDirectMats[$m->material_id])) {
                                $rel = $realizationsByMaterial->get($m->material_id);
                                if ($rel) {
                                    $rel->rab_category_name = $root->name;
                                    $rel->rab_category_code = $root->code;
                                    $rel->rab_category_full = $root->code.'. '.$root->name;
                                    $rel->rab_section_title = $sub->code.' '.$sub->name;
                                    $rel->rab_section_subtitle = $item->name;
                                    $subDirectMats[$m->material_id] = $rel;
                                }
                                $allMappedMaterialIds[] = $m->material_id;
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

                // 6.c. Materials under Sub-Sub-Categories (Level 3)
                foreach ($sub->children as $subSub) {
                    $subSubMats = [];
                    foreach ($subSub->rabItems as $item) {
                        foreach ($item->materials as $im) {
                            $itemMats = collect([$im])->merge($im->breakdowns);
                            foreach ($itemMats as $m) {
                                if (! isset($subSubMats[$m->material_id])) {
                                    $rel = $realizationsByMaterial->get($m->material_id);
                                    if ($rel) {
                                        $rel->rab_category_name = $root->name;
                                        $rel->rab_category_code = $root->code;
                                        $rel->rab_category_full = $root->code.'. '.$root->name;
                                        $rel->rab_section_title = $sub->code.' '.$sub->name;
                                        $rel->rab_section_subtitle = $subSub->code.' '.$subSub->name;
                                        $subSubMats[$m->material_id] = $rel;
                                    }
                                    $allMappedMaterialIds[] = $m->material_id;
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

        // Comprehensive query: Seluruh material yang terdaftar dalam RAB proyek ini (termasuk leaf dan breakdown)
        $dbRabMaterialIds = RabItemMaterial::whereHas('rabItem.rabNode', function ($query) use ($currentProject) {
            $query->where('project_id', $currentProject->id);
        })->pluck('material_id')->toArray();

        $allMappedMaterialIds = array_values(array_unique(array_merge($allMappedMaterialIds, $dbRabMaterialIds)));

        // Lengkapi data kategori untuk realization yang ada dalam RAB proyek (batch, bukan query per material)
        $unmappedRealizations = $realizations->filter(
            fn ($rel) => empty($rel->rab_category_name) && in_array($rel->material_id, $allMappedMaterialIds)
        );

        if ($unmappedRealizations->isNotEmpty()) {
            $rimByMaterial = RabItemMaterial::with('rabItem')
                ->whereHas('rabItem.rabNode', fn ($q) => $q->where('project_id', $currentProject->id))
                ->whereIn('material_id', $unmappedRealizations->pluck('material_id')->unique()->values())
                ->orderBy('id')
                ->get()
                ->unique('material_id')
                ->keyBy('material_id');
            $projectNodes = RabNode::where('project_id', $currentProject->id)->get()->keyBy('id');

            foreach ($unmappedRealizations as $rel) {
                $rim = $rimByMaterial->get($rel->material_id);
                $node = $rim?->rabItem ? $projectNodes->get($rim->rabItem->rab_node_id) : null;
                if ($node) {
                    $rootNode = $node;
                    while ($rootNode->parent_id && $projectNodes->has($rootNode->parent_id)) {
                        $rootNode = $projectNodes->get($rootNode->parent_id);
                    }
                    $rel->rab_category_name = $rootNode->name;
                    $rel->rab_category_code = $rootNode->code;
                    $rel->rab_category_full = $rootNode->code.'. '.$rootNode->name;
                    $rel->rab_section_title = $node->code.' '.$node->name;
                    $rel->rab_section_subtitle = $rim->rabItem->name;
                }
            }
        }

        // 7. Non-RAB Purchases Recap (Pembelian & Pengadaan di Luar RAB / Unbudgeted)
        // Pengawasan material yang dipesan / diterima tetapi TIDAK ADA di struktur alokasi RAB Tree proyek
        // Catatan: Item khusus operasional kendaraan dipisahkan ke modul pemantauan armada tersendiri
        $nonRabPoItems = PurchaseOrderItem::whereHas('purchaseOrder', function ($q) use ($currentProject) {
            $q->where('project_id', $currentProject->id);
        })
            ->whereNotIn('material_id', $allMappedMaterialIds)
            ->whereDoesntHave('material', fn ($q) => $q->where('category', 'operasional_kendaraan'))
            ->with(['material.defaultUnit', 'purchaseOrder.supplier', 'unit'])
            ->get();

        $nonRabDoItems = DeliveryOrderItem::whereHas('deliveryOrder', function ($q) use ($currentProject) {
            $q->where('project_id', $currentProject->id);
        })
            ->whereNotIn('material_id', $allMappedMaterialIds)
            ->whereDoesntHave('material', fn ($q) => $q->where('category', 'operasional_kendaraan'))
            ->with(['material.defaultUnit', 'deliveryOrder.supplier', 'deliveryOrder.receiver', 'unit'])
            ->get();

        // Material Non-RAB HANYA yang benar-benar memiliki riwayat pengadaan fisik/PO (po_qty > 0 atau do_qty > 0)
        $allNonRabMaterialIds = array_values(array_unique(array_merge(
            $nonRabPoItems->pluck('material_id')->toArray(),
            $nonRabDoItems->pluck('material_id')->toArray()
        )));

        $nonRabPurchases = [];
        $totalNonRabPoCost = 0;
        $totalNonRabDoQty = 0;

        foreach ($allNonRabMaterialIds as $matId) {
            $mPoItems = $nonRabPoItems->where('material_id', $matId);
            $mDoItems = $nonRabDoItems->where('material_id', $matId);
            $mRealization = $realizationsByMaterial->get($matId);

            $poQty = (float) $mPoItems->sum('qty_ordered');
            $poCost = (float) $mPoItems->sum(fn ($it) => (float) $it->qty_ordered * (float) $it->unit_price);
            $doQty = (float) $mDoItems->sum('qty_received');

            // Skip item jika tidak ada kuantiti PO dan tidak ada kuantiti DO (tidak ada aktivitas pembelian)
            if ($poQty <= 0 && $doQty <= 0) {
                continue;
            }

            $mat = $mPoItems->first()?->material
                ?? $mDoItems->first()?->material
                ?? $mRealization?->material
                ?? Material::with('defaultUnit')->find($matId);

            if (! $mat) {
                continue;
            }

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
            } elseif ($doQty > 0 && $poQty == 0) {
                $statusType = 'unplanned_do';
                $statusLabel = 'Fisik Tiba Tanpa PO';
                $badgeCls = 'bg-purple-100 text-purple-800 border-purple-200';
            } else {
                continue;
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

        // 8. Monitoring Khusus: Operasional Kendaraan & Logistik Armada
        // Pemantauan konsumsi BBM Solar, pelumas mesin, servis berkala, dan sewa dump truck armada lapangan
        $vehicleOpsMaterials = Material::where('category', 'operasional_kendaraan')
            ->with('defaultUnit')
            ->orderBy('code')
            ->get();

        // PO & DO armada dimuat sekali untuk seluruh material, lalu dikelompokkan di memori
        $vehiclePoItems = PurchaseOrderItem::whereIn('material_id', $vehicleOpsMaterials->modelKeys())
            ->whereHas('purchaseOrder', fn ($q) => $q->where('project_id', $currentProject->id))
            ->with(['purchaseOrder.supplier', 'unit'])
            ->get()
            ->groupBy('material_id');
        $vehicleDoItems = DeliveryOrderItem::whereIn('material_id', $vehicleOpsMaterials->modelKeys())
            ->whereHas('deliveryOrder', fn ($q) => $q->where('project_id', $currentProject->id))
            ->with(['deliveryOrder.supplier', 'unit'])
            ->get()
            ->groupBy('material_id');

        $vehicleOpsMaterials = $vehicleOpsMaterials
            ->map(function ($mat) use ($vehiclePoItems, $vehicleDoItems, $realizationsByMaterial) {
                $poItems = $vehiclePoItems->get($mat->id, collect());

                $poQty = (float) $poItems->sum('qty_ordered');
                $poCost = (float) $poItems->sum(fn ($it) => (float) $it->qty_ordered * (float) $it->unit_price);

                $doItems = $vehicleDoItems->get($mat->id, collect());

                $doQty = (float) $doItems->sum('qty_received');

                // Sesuai requirement: Kuota 'Akan Diambil' disamakan dengan kuota 'PO'
                $akanDiambilQty = $poQty;
                $sisaDiambilQty = max(0, $akanDiambilQty - $doQty);
                $pctTaken = $akanDiambilQty > 0 ? round(($doQty / $akanDiambilQty) * 100, 1) : 0;

                $mat->po_qty = $poQty;
                $mat->po_cost = $poCost;
                $mat->akan_diambil_qty = $akanDiambilQty;
                $mat->do_qty = $doQty;
                $mat->sisa_diambil_qty = $sisaDiambilQty;
                $mat->pct_taken = $pctTaken;
                $mat->pos = $poItems->map(fn ($it) => $it->purchaseOrder)->filter()->unique('id')->values();
                $mat->dos = $doItems->map(fn ($it) => $it->deliveryOrder)->filter()->unique('id')->values();

                $mat->realization = $realizationsByMaterial->get($mat->id);

                return $mat;
            });

        // 9. PO Menggantung / Belum Diterima Sepenuhnya di Lapangan
        $pendingPos = PurchaseOrder::attachFulfillment(
            PurchaseOrder::where('project_id', $currentProject->id)
                ->where('status', '!=', 'cancelled')
                ->with(['supplier', 'creator', 'items.material.defaultUnit', 'items.unit', 'deliveryOrders'])
                ->get()
        )
            ->filter(fn ($po) => $po->fulfillment['has_pending'])
            ->values();

        $stats['pending_pos_count'] = $pendingPos->count();
        $stats['pending_pos_partial_count'] = $pendingPos->where('fulfillment.is_partial', true)->count();
        $stats['pending_pos_needs_do'] = $pendingPos->where('fulfillment.needs_new_do_number', true)->count();
        $pendingPoCount = $stats['pending_pos_count'];

        return view('dashboard.index', compact(
            'projects',
            'currentProject',
            'stats',
            'realizations',
            'treeRealizations',
            'nonRabPurchases',
            'vehicleOpsMaterials',
            'chartCategories',
            'chartBudgets',
            'chartActuals',
            'recentAlerts',
            'recentDos',
            'pendingPos',
            'pendingPoCount'
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
        $newProjectId = (int) $request->project_id;
        session(['active_project_id' => $newProjectId]);

        $previousUrl = url()->previous();
        $parsedUrl = $previousUrl ? parse_url($previousUrl) : [];

        // Hanya ikuti URL sebelumnya bila berasal dari host aplikasi ini (cegah open redirect via Referer)
        $isInternal = ! isset($parsedUrl['host']) || $parsedUrl['host'] === $request->getHost();

        if ($isInternal) {
            $path = $request->getSchemeAndHttpHost().($parsedUrl['path'] ?? '/');

            // 1. If previous URL was editing a specific project resource (e.g. /projects/2/edit)
            if (preg_match('#/projects/\d+(/edit)?$#', $parsedUrl['path'] ?? '')) {
                return redirect()->route('projects.edit', $newProjectId)->with('success', 'Proyek berhasil dialihkan.');
            }

            // 2. If previous URL contains a project_id query parameter, update it to the new project ID
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                if (isset($queryParams['project_id'])) {
                    $queryParams['project_id'] = $newProjectId;

                    return redirect($path.'?'.http_build_query($queryParams).(isset($parsedUrl['fragment']) ? '#'.$parsedUrl['fragment'] : ''))
                        ->with('success', 'Proyek berhasil dialihkan.');
                }
            }

            return redirect()->to($previousUrl)->with('success', 'Proyek berhasil dialihkan.');
        }

        return redirect()->route('dashboard')->with('success', 'Proyek berhasil dialihkan.');
    }
}
