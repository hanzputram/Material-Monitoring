# Peta Fitur: Material-Monitoring

Semua fitur di bawah ini diekstrak dari kode sumber yang ada.
Status awal sebelum audit adalah `Implemented` (belum diverifikasi mendalam per alur dan relasinya).

| ID | Modul | Fitur | File Utama / Route | Penyimpanan / Relasi | Status |
|---|---|---|---|---|---|
| F-001 | Auth & Session | Login, Logout, dan Pengalihan Berdasarkan Hak Akses | `AuthController`, `login`, `logout` | `users`, sessions | Implemented |
| F-002 | Proyek & Switcher | CRUD Proyek & Switch Active Project di Session | `ProjectController`, `DashboardController@switchProject` | `projects`, `session('active_project_id')` | Implemented |
| F-003 | Dashboard & KPI | Ringkasan Proyek, Progress Fisik/Keuangan, Deviasi RAB | `DashboardController@index`, `CostComparisonController@index` | `projects`, `rab_items`, `material_realizations`, `cost_realizations` | Implemented |
| F-004 | Detail Material Monitoring | Detail Varian Kuantitas Realisasi Material Proyek | `DashboardController@showMaterialDetail` | `material_realizations`, `rab_item_materials`, `delivery_order_items` | Implemented |
| F-005 | Outstanding PO Monitoring | Monitoring PO yang belum selesai terkirim (outstanding) | `ProcurementController@outstandingPoIndex` | `purchase_orders`, `purchase_order_items`, `delivery_order_items` | Implemented |
| F-006 | Master Satuan | Tambah, Edit, Hapus Satuan Unit | `MaterialController@storeUnit`, `updateUnit`, `destroyUnit` | `units` | Implemented |
| F-007 | Master Material | CRUD Material, Konversi Satuan, Toggle Status Aktif | `MaterialController` | `materials`, `units`, `material_unit_conversions` | Implemented |
| F-008 | Master Rekanan / Supplier | CRUD Data Rekanan & Supplier | `SupplierController` | `suppliers` | Implemented |
| F-009 | Master Alat & Kategori | Master Peralatan, Kategori, Harga Sewa/Beli, Fast Bulk Store | `EquipmentController` | `equipment_masters`, `equipment_categories`, `project_equipment` | Implemented |
| F-010 | Master Mandor & Pekerja | Master Tenaga Kerja, Penugasan Pekerja ke Proyek, Fast Bulk Assign | `WorkerController` | `workers`, `project_workers` | Implemented |
| F-011 | Pekerjaan Borongan & Panjar | Kontrak Borongan Mandor, Pembayaran Panjar Bertahap | `WorkerController@storeBorongan`, `storePanjar`, `destroyPanjar` | `mandor_borongans`, `mandor_borongan_panjars` | Implemented |
| F-012 | K-RAB Tree Builder | Hirarki WBS Node RAB (Add, Edit, Reorder, Delete) | `RabBuilderController@storeNode`, `updateNode`, `destroyNode` | `rab_nodes` | Implemented |
| F-013 | K-RAB Item & BOM | Tambah Item Pekerjaan, Breakdown Material / BOM, Clone BOM | `RabBuilderController@storeItem`, `storeItemMaterial`, `cloneBom` | `rab_items`, `rab_item_materials` | Implemented |
| F-014 | RAB Import/Export Excel | Download Template V2, Parser Excel, Preview Staging, Commit RAB, Export | `RabImportExportController`, `RabExcelService`, `TemplateV2Parser` | `rab_nodes`, `rab_items`, `rab_item_materials` | Implemented |
| F-015 | PO Procurement | Buat PO, Tambah Item, Update DO Number, Cetak PDF Resmi | `ProcurementController@storePo`, `updatePoDoNumber`, `downloadPoPdf` | `purchase_orders`, `purchase_order_items`, `suppliers` | Implemented |
| F-016 | Delivery Order (DO) Lapangan | Catat Surat Jalan Lapangan, Multi-PO, Update Realisasi Material | `ProcurementController@storeDo`, `destroyDo` | `delivery_orders`, `delivery_order_items`, `material_realizations` | Implemented |
| F-017 | Invoice Tagihan | Catat Faktur Pembelian (PO/DO), Hapus Invoice | `ProcurementController@storeInvoice`, `destroyInvoice` | `invoices`, `purchase_orders`, `delivery_orders` | Implemented |
| F-018 | Retur Pembelian | Pencatatan Pengembalian Barang Cacat/Lebih ke Supplier | `PurchaseReturnController` | `purchase_returns`, `purchase_return_items` | Implemented |
| F-019 | Uang Muka Pembelian (DP) | Catat Down Payment untuk PO ke Supplier | `PurchaseDownPaymentController` | `purchase_down_payments`, `purchase_orders` | Implemented |
| F-020 | Pembayaran Tagihan Supplier | Bayar Faktur (Single/Multi Invoice) dengan Potongan DP | `PurchasePaymentController` | `purchase_payments`, `purchase_payment_items`, `invoices`, `purchase_down_payments` | Implemented |
| F-021 | Ringkasan Hutang & Toko | Rekapitulasi Tagihan dan Pembayaran Toko Lintas Proyek | `FinanceSummaryController@supplierSummary` | `suppliers`, `invoices`, `purchase_payments` | Implemented |
| F-022 | Variance Validation | Validasi Deviasi Realisasi (Dual Approval: Pengawas + Purchasing) | `VarianceValidationController` | `material_variance_validations`, `material_realizations` | Implemented |
| F-023 | Alert Center | Notifikasi Over-budget, Keterlambatan PO, Deviasi Material | `AlertController` | `alerts`, `projects` | Implemented |
| F-024 | Manajemen Pengguna & Hak Akses | CRUD User, Assign Roles & Permissions, Role Switcher | `UserController`, `RoleSimulationController` | `users`, `roles`, `permissions` | Implemented |
