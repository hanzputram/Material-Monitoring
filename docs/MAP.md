# Map Aplikasi: Material-Monitoring & K-RAB Construction System

## 1. Stack Teknologi
- **Backend**: Laravel 12 (Framework v13.17 / PHP ^8.3), Spatie Laravel-Permission v8.3, Barryvdh Laravel-DomPDF v3.1, PhpOffice PhpSpreadsheet v5.10.
- **Frontend**: Blade Templates, Tailwind CSS v4 (@tailwindcss/vite), Alpine.js v3.17, ApexCharts v7.6, Lucide Icons (SVGs/Blade).
- **Bundler & Tooling**: Vite v8, Laravel Vite Plugin v3.1, Concurrently.
- **Database & Storage**: SQLite / MySQL (default `database/database.sqlite`), file storage lokal (`storage/app/public`).
- **Testing & QA**: PHPUnit v12, Laravel Pint v1.27.

## 2. Entry Point & Cara Menjalankan
- **Web Entry**: `public/index.php` -> `bootstrap/app.php` -> `routes/web.php`.
- **Command Entry**: `routes/console.php`.
- **Development Server**: `composer run dev` (atau `php artisan serve` + `npm run dev`).
- **Testing**: `php artisan test --compact` atau `vendor/bin/phpunit`.
- **Build Asset**: `npm run build`.

## 3. Modul Utama & Arsitektur
1. **Autentikasi & Otorisasi (`AuthController`, `CheckModulePermission`, `RoleSimulationController`, `UserController`)**:
   - Multi-role via Spatie Permission & custom module permission check (`module:{module_name}`).
   - Project switching via session (`active_project_id`).
2. **Dashboard & Monitoring (`DashboardController`, `CostComparisonController`, `AlertController`)**:
   - Agregasi deviasi volume, deviasi biaya (RAB vs Aktual), status PO menggantung, warning threshold, KPI grafik.
3. **Master Data (`ProjectController`, `SupplierController`, `MaterialController`, `EquipmentController`, `WorkerController`)**:
   - Master Proyek, Supplier, Satuan (Unit), Material, Alat (Equipment Master & Kategori), Tenaga Kerja / Mandor (Worker & ProjectWorker).
4. **K-RAB Builder & Excel Engine (`RabBuilderController`, `RabImportExportController`, `RabExcelService`, `TemplateV2Parser`, `UnitNormalizer`)**:
   - Struktur WBS pohon (Node level 1-3 -> Item Pekerjaan -> Material breakdown / BOM).
   - Ekspor & Impor Excel Template V2, parser otomatis, preview staging, commit ke database.
5. **Procurement Pipeline (`ProcurementController`, `PurchaseOrderPdfService`, `PurchaseReturnController`)**:
   - Purchase Order (PO): Draft, Approved, Issued, Completed, Cancelled. Cetak PDF resmi PO.
   - Delivery Order (DO Lapangan): Penerimaan barang multi-PO, update status barang masuk, sinkronisasi otomatis ke `MaterialRealization`.
   - Faktur / Invoices: Pencatatan tagihan supplier berdasarkan PO & DO.
   - Retur Pembelian (Purchase Return): Pengembalian barang cacat/lebih ke supplier.
6. **Finance & Pembayaran (`PurchaseDownPaymentController`, `PurchasePaymentController`, `FinanceSummaryController`)**:
   - Uang Muka Pembelian (Purchase Down Payment).
   - Pembayaran Tagihan Supplier (Purchase Payment / Settlement) potong DP.
   - Supplier Summary: Rekapitulasi transaksi, hutang, dan pembayaran per toko / supplier lintas proyek.
7. **Variance & Approval (`VarianceValidationController`)**:
   - Validasi selisih/varian jika realisasi melebihi toleransi RAB.
   - Dual approval: Pengawas Lapangan dan Purchasing.
8. **Pekerjaan Borongan & Tenaga Kerja (`WorkerController`)**:
   - Kontrak Mandor Borongan (Lump Sum), pencatatan panjar borongan bertahap, status penyelesaian.
   - Penugasan pekerja harian per proyek (`ProjectWorker`) dengan fast-bulk store.

## 4. Alur Data (Data Flow)
1. **RAB Planning**: Proyek dibuat -> Susun RAB (Node -> Item -> Material / BOM) -> baseline budget & volume terbentuk.
2. **Procurement (PO -> DO -> Invoice)**:
   - Buat PO ke Supplier dari referensi Item RAB & Material.
   - DO diterima di lapangan -> memvalidasi fisik barang -> otomatis mencatat atau memperbarui `MaterialRealization` & `DeliveryOrderItem`.
   - Invoice dicatat dari PO/DO yang valid.
3. **Finance (DP & Payment)**:
   - PO dapat dibayar DP (PurchaseDownPayment).
   - Invoice dilunasi melalui PurchasePayment dengan opsional pemotongan DP terkait.
4. **Monitoring & Variance**:
   - Akumulasi kuantitas realisasi dibandingkan dengan RAB. Jika melewati threshold proyek, buat `MaterialVarianceValidation` dan `Alert`.
   - Dashboard menghitung deviasi volume dan cost realization real-time.

## 5. Tempat Penyimpanan
- **Database**: Tabel relasional (`projects`, `rab_nodes`, `rab_items`, `rab_item_materials`, `materials`, `units`, `suppliers`, `purchase_orders`, `purchase_order_items`, `delivery_orders`, `delivery_order_items`, `invoices`, `purchase_down_payments`, `purchase_payments`, `purchase_payment_items`, `purchase_returns`, `purchase_return_items`, `material_realizations`, `material_variance_validations`, `equipment_masters`, `equipment_categories`, `project_equipment`, `workers`, `project_workers`, `mandor_borongans`, `mandor_borongan_panjars`, `alerts`, `cost_realizations`).
- **File Storage**: Dokumen PO PDF, foto bukti DO, nota invoice, slip pembayaran, dan attachment retur di `storage/app/public/` (diakses via `/storage/{path}`).
