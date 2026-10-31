<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\RabItem;
use App\Models\RabNode;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Menyesuaikan tampilan monitoring dengan struktur RAB yang benar-benar diinput:
 * RAB bisa lengkap (level 1-5), hanya item (level 4), hanya material (level 5),
 * atau hanya kategori (level 1/2/3).
 *
 * Pohon RAB yang diberikan harus sudah eager-load RabNode::treeRelations() dengan relasi item:
 * materials, unit, costRealization, purchaseOrderItems.purchaseOrder.
 */
class RabMonitoringService
{
    /**
     * Data grafik anggaran vs realisasi. Bila RAB hanya punya satu kategori, grafik otomatis
     * turun ke sub kategori / item pekerjaan agar tetap informatif (bukan satu batang saja).
     *
     * @param  Collection<int, RabNode>  $rootNodes
     * @return array{label: string, categories: array<int, string>, budgets: array<int, float>, actuals: array<int, float>}
     */
    public function chartSeries(Collection $rootNodes, int $maxBars = 12): array
    {
        $nodes = $rootNodes;
        $label = 'Kategori';

        // Lewati kategori tunggal yang hanya berisi sub kategori
        while ($nodes->count() === 1 && $nodes->first()->rabItems->isEmpty() && $nodes->first()->children->isNotEmpty()) {
            $nodes = $nodes->first()->children;
            $label = 'Sub Kategori';
        }

        $groups = collect();
        if ($nodes->count() === 1) {
            // Hanya satu kategori: tampilkan rincian isinya (sub kategori + item pekerjaan)
            $only = $nodes->first();
            foreach ($only->children as $child) {
                $groups->push($this->nodeGroup($child));
            }
            foreach ($only->rabItems as $item) {
                $groups->push([
                    'label' => trim(($item->item_no ? $item->item_no.' ' : '').$item->name),
                    'budget' => (float) $item->total_price,
                    'actual' => round((float) $item->actual_cost, 2),
                ]);
            }
            $label = $only->children->isEmpty() ? 'Item Pekerjaan' : 'Sub Kategori & Item Pekerjaan';
        } else {
            $groups = $nodes->map(fn (RabNode $node) => $this->nodeGroup($node))->values();
        }

        // Terlalu banyak batang: tampilkan yang anggarannya terbesar, sisanya digabung "Lainnya"
        if ($groups->count() > $maxBars) {
            $keep = $groups->sortByDesc('budget')->take($maxBars - 1)->keys();
            $rest = $groups->except($keep->all());
            $groups = $groups->only($keep->all())->values()->push([
                'label' => 'Lainnya ('.$rest->count().')',
                'budget' => (float) $rest->sum('budget'),
                'actual' => (float) $rest->sum('actual'),
            ]);
        }

        return [
            'label' => $label,
            'categories' => $groups->map(fn ($g) => Str::limit($g['label'], 24, '...'))->values()->all(),
            'budgets' => $groups->pluck('budget')->map(fn ($v) => (float) $v)->values()->all(),
            'actuals' => $groups->pluck('actual')->map(fn ($v) => (float) $v)->values()->all(),
        ];
    }

    /**
     * Monitoring item pekerjaan (level 4) yang tidak memiliki rincian material (BOM level 5).
     * Realisasi fisik diambil dari PO yang dialokasikan ke item RAB tersebut dan Surat Jalan (DO)
     * yang menerima PO itu; realisasi biaya dari nilai PO teralokasi.
     *
     * @param  Collection<int, RabNode>  $rootNodes
     * @return Collection<int, array<string, mixed>>
     */
    public function itemMonitoring(Collection $rootNodes): Collection
    {
        $entries = [];
        foreach ($rootNodes as $root) {
            $this->collectItemsWithoutBom($root, $root, $entries);
        }

        if (empty($entries)) {
            return collect();
        }

        $allocations = collect($entries)
            ->flatMap(fn ($entry) => $entry['item']->purchaseOrderItems)
            ->filter(fn (PurchaseOrderItem $poi) => $poi->purchaseOrder && $poi->purchaseOrder->status !== 'cancelled');
        $receivedByPoItem = $this->receivedQtyPerPoItem($allocations->pluck('purchase_order_id')->unique()->values()->all());

        return collect($entries)->map(function (array $entry) use ($receivedByPoItem) {
            /** @var RabItem $item */
            $item = $entry['item'];
            $root = $entry['root'];
            $node = $entry['node'];

            $allocs = $item->purchaseOrderItems
                ->filter(fn (PurchaseOrderItem $poi) => $poi->purchaseOrder && $poi->purchaseOrder->status !== 'cancelled');
            $sameUnit = $allocs->filter(fn (PurchaseOrderItem $poi) => (int) $poi->unit_id === (int) $item->unit_id);

            $planned = (float) $item->volume;
            $ordered = (float) $sameUnit->sum(fn ($poi) => (float) $poi->qty_ordered);
            $received = (float) $sameUnit->sum(fn ($poi) => $receivedByPoItem[$poi->id] ?? 0);
            $budget = (float) $item->total_price;
            $orderedCost = (float) $allocs->sum(fn ($poi) => (float) $poi->qty_ordered * (float) $poi->unit_price);

            if ($received > $planned + 0.00005) {
                $evalType = 'over';
            } elseif ($received <= 0) {
                $evalType = 'pending';
            } elseif ($received < $planned - 0.00005) {
                $evalType = 'partial';
            } else {
                $evalType = 'normal';
            }

            return [
                'id' => $item->id,
                'item_no' => (string) $item->item_no,
                'name' => $item->name,
                'unit' => $item->unit?->code ?? '-',
                'category_code' => $root->code,
                'category_name' => $root->name,
                'section_title' => $node->id !== $root->id ? trim($node->code.' '.$node->name) : '',
                'planned_qty' => $planned,
                'ordered_qty' => $ordered,
                'received_qty' => $received,
                'variance_qty' => $received - $planned,
                'pct' => $planned > 0 ? round(($received / $planned) * 100, 1) : 0.0,
                'budget' => $budget,
                'ordered_cost' => $orderedCost,
                'cost_pct' => $budget > 0 ? round(($orderedCost / $budget) * 100, 1) : 0.0,
                'po_count' => $allocs->pluck('purchase_order_id')->unique()->count(),
                'unit_mismatch' => $allocs->count() > $sameUnit->count(),
                'eval_type' => $evalType,
            ];
        })->values();
    }

    /**
     * @return array{label: string, budget: float, actual: float}
     */
    private function nodeGroup(RabNode $node): array
    {
        return [
            'label' => trim($node->code.' '.$node->name),
            'budget' => (float) $node->subtotal_cache,
            'actual' => round((float) $node->actual_cost_total, 2),
        ];
    }

    /**
     * @param  array<int, array{item: RabItem, root: RabNode, node: RabNode}>  $entries
     */
    private function collectItemsWithoutBom(RabNode $node, RabNode $root, array &$entries): void
    {
        foreach ($node->rabItems as $item) {
            if ($item->materials->isEmpty()) {
                $entries[] = ['item' => $item, 'root' => $root, 'node' => $node];
            }
        }

        foreach ($node->children as $child) {
            $this->collectItemsWithoutBom($child, $root, $entries);
        }
    }

    /**
     * Qty diterima (DO) per baris PO. DO mencatat PO + material, sehingga bila satu PO memuat
     * material yang sama di beberapa baris, penerimaan dibagi berurutan sesuai qty pesanan.
     *
     * @param  array<int, int>  $purchaseOrderIds
     * @return array<int, float>
     */
    private function receivedQtyPerPoItem(array $purchaseOrderIds): array
    {
        if (empty($purchaseOrderIds)) {
            return [];
        }

        $received = PurchaseOrder::receivedQuantitiesFor($purchaseOrderIds);
        $result = [];

        PurchaseOrderItem::whereIn('purchase_order_id', $purchaseOrderIds)
            ->orderBy('id')
            ->get(['id', 'purchase_order_id', 'material_id', 'qty_ordered'])
            ->groupBy(fn ($poi) => $poi->purchase_order_id.'|'.$poi->material_id)
            ->each(function (Collection $lines) use ($received, &$result) {
                $first = $lines->first();
                $remaining = (float) ($received[$first->purchase_order_id][$first->material_id] ?? 0);
                $lastIndex = $lines->count() - 1;

                foreach ($lines->values() as $index => $poi) {
                    $take = $index === $lastIndex ? $remaining : min($remaining, (float) $poi->qty_ordered);
                    $result[$poi->id] = max(0.0, $take);
                    $remaining -= $take;
                }
            });

        return $result;
    }
}
