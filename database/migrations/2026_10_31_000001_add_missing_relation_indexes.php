<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SQLite (dan beberapa driver lain) tidak otomatis membuat index untuk kolom foreign key.
 * Migration ini menambahkan index untuk kolom relasi yang sering dipakai pada join/whereHas,
 * hanya bila belum ada index yang diawali kolom tersebut (aman untuk MySQL yang sudah
 * membuat index FK otomatis).
 */
return new class extends Migration
{
    /**
     * @var array<string, array<int, array<int, string>>>
     */
    private array $indexes = [
        'rab_items' => [['unit_id']],
        'rab_item_materials' => [['material_id'], ['parent_id']],
        'materials' => [['default_unit_id'], ['category']],
        'purchase_orders' => [['project_id', 'status'], ['supplier_id']],
        'purchase_order_items' => [['purchase_order_id', 'material_id'], ['material_id'], ['rab_item_id']],
        'delivery_orders' => [['project_id', 'do_number'], ['purchase_order_id'], ['supplier_id']],
        'delivery_order_items' => [['delivery_order_id'], ['purchase_order_id', 'material_id'], ['material_id']],
        'delivery_order_purchase_order' => [['purchase_order_id']],
        'invoices' => [['project_id', 'payment_status'], ['purchase_order_id'], ['supplier_id']],
        'material_realizations' => [['material_id']],
        'material_variance_validations' => [['material_realization_id'], ['delivery_order_id'], ['invoice_id']],
        'alerts' => [['project_id', 'status'], ['reference_type', 'reference_id']],
        'cost_realizations' => [['rab_item_id']],
        'purchase_payments' => [['project_id'], ['supplier_id']],
        'purchase_payment_items' => [['purchase_payment_id'], ['invoice_id']],
        'purchase_returns' => [['project_id'], ['supplier_id'], ['delivery_order_id']],
        'purchase_return_items' => [['purchase_return_id'], ['material_id']],
        'project_workers' => [['worker_id']],
        'mandor_borongans' => [['project_id']],
        'mandor_borongan_panjars' => [['mandor_borongan_id']],
        'project_equipment' => [['project_id'], ['equipment_master_id']],
        'equipment_master' => [['equipment_category_id']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $existing = collect(Schema::getIndexes($table))->pluck('columns');

            foreach ($indexes as $columns) {
                if (! Schema::hasColumns($table, $columns)) {
                    continue;
                }

                $covered = $existing->contains(
                    fn (array $indexColumns) => array_slice($indexColumns, 0, count($columns)) === $columns
                );

                if (! $covered) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $this->indexName($table, $columns)));
                    $existing->push($columns);
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $columns) {
                $name = $this->indexName($table, $columns);
                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
                }
            }
        }
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function indexName(string $table, array $columns): string
    {
        return 'idx_'.substr(md5($table.'.'.implode('_', $columns)), 0, 8).'_'.implode('_', $columns);
    }
};
