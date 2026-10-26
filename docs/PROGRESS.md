# Project Progress & Memory: Material-Monitoring

## Status Terakhir (2026-10-26)
- **Status Sesi**: Audit dan perbaikan menyeluruh bug modul dan relasi (`debug-existing`) selesai terverifikasi.
- **Baseline Awal**: Commit `c82c68b`, 59 tests passed, 278 assertions.
- **Hasil Akhir Test Suite**: `php artisan test --compact` -> **65 tests passed, 300 assertions** (0 failed, 0 regressions).
- **Hasil Akhir Asset Build**: `npm run build` -> Vite v8 build sukses (2.19s).
- **Code Style**: `vendor/bin/pint --format agent` -> 100% passed.

## Perintah Penting
- **Dev**: `composer run dev`
- **Test**: `php artisan test --compact`
- **Build**: `npm run build`
- **Lint / Format**: `vendor/bin/pint --format agent`

## Ringkasan Siklus Audit & Perbaikan Bug (1 Bug = 1 Siklus Commit)

1. **Siklus 1 - Relasi Eloquent Antar Modul yang Hilang** (`1b3fc5c`)
   - **Masalah**: `Project` tidak memiliki relasi `mandorBorongans()`, `purchaseDownPayments()`, `purchasePayments()`, dan `purchaseReturns()`. `PurchaseOrder` tidak memiliki relasi `downPayments()` dan `returns()`. `PurchaseOrderItem` tidak memiliki relasi `deliveryOrderItems()`.
   - **Pencegahan**: Menghindari `BadMethodCallException` saat query relasi proyek dan rantai pengadaan.
   - **Tes**: `SystemAuditAndRelationshipsTest::test_project_and_purchase_order_relationships_exist`.

2. **Siklus 2 - Bug Rekalkulasi Status Pembayaran Faktur (DP 100%)** (`1f3a4fb`)
   - **Masalah**: Pada `Invoice::recalculatePaymentStatus()`, faktur yang 100% ditutup uang muka (`net_amount == 0`) salah dibalikkan menjadi `unpaid` karena kondisi awal mewajibkan `net_amount > 0` dan hanya membandingkan `paid_amount`.
   - **Solusi**: Faktur ber-DP penuh tetap berstatus `paid`, faktur yang baru tertutup DP sebagian menjadi `partial`.
   - **Tes**: `SystemAuditAndRelationshipsTest::test_invoice_recalculate_payment_status_when_fully_paid_by_down_payment`.

3. **Siklus 3 - Eliminasi Multiplier Dummy Biaya Aktual & Realisasi Riil** (`f1ad405`)
   - **Masalah**: `CostComparisonController`, `DashboardController`, dan template `cost/index.blade.php` menggunakan hardcoded demo multiplier `* 0.92`, `* 0.08`, dan `* 0.95`. Pengeluaran belanja riil dari PO tidak pernah diagregasikan ke kategori RAB.
   - **Solusi**: Ditambahkan `RabItem::getActualCostAttribute()` (mengagregasikan PO riil), `RabNode::getActualCostTotalAttribute()`, sinkronisasi ke tabel `cost_realizations`, dan template Blade kini menghitung selisih dan status over/under secara riil.
   - **Tes**: `SystemAuditAndRelationshipsTest::test_cost_realization_calculates_actual_po_expenditures_accurately`.

4. **Siklus 4 - Sinkronisasi Baseline Realisasi Material Saat Hapus Item/Kategori RAB** (`534583f`)
   - **Masalah**: Menghapus item atau kategori RAB memicu DB foreign key cascade yang tidak menembak Eloquent event pada `RabItemMaterial`, sehingga `MaterialRealization::planned_qty` tertinggal dengan nilai kuota lama (ghost quota).
   - **Solusi**: `RabBuilderController::destroyNode`, `destroyItem`, dan `destroyItemMaterial` memanggil `MaterialRealization::recalculateAllForProject($projectId)`, serta hook `RabItem::deleted` dan `RabNode::deleted` menjamin sinkronisasi otomatis.
   - **Tes**: `SystemAuditAndRelationshipsTest::test_rab_item_and_node_deletion_recalculates_material_realization_baselines`.

5. **Siklus 5 - Keselarasan Hak Akses Pengguna pada Root Pengadaan** (`64d2f97`)
   - **Masalah**: `User::hasModulePermission('procurement')` tidak memasukkan `$this->canReadInvoice()`, sehingga staf keuangan dengan hak akses faktur mendapatkan HTTP 403 saat mengakses route root `/procurement` yang seharusnya me-redirect ke daftar faktur.
   - **Solusi**: Menambahkan `canReadInvoice()` ke pengecekan modul `procurement` di `User.php`.
   - **Tes**: `SystemAuditAndRelationshipsTest::test_user_with_invoice_permission_can_access_procurement_root_redirect`.

6. **Siklus 6 - Integritas Transaksi Saat Hapus Data Rekanan/Supplier** (`ecaa621`)
   - **Masalah**: `SupplierController::destroy` hanya memvalidasi DO, PO, dan Invoice, tetapi melewatkan Uang Muka (`downPayments`), Pembayaran (`payments`), dan Retur (`returns`), berisiko memicu crash foreign key atau kehilangan data histori pembayaran.
   - **Solusi**: Memperluas proteksi penghapusan dengan menghitung transaksi aktif pada seluruh jenis relasi finansial/logistik rekanan dengan pesan error informatif.
   - **Tes**: `SystemAuditAndRelationshipsTest::test_supplier_cannot_be_deleted_if_it_has_down_payments_payments_or_returns`.

## Log Commit Sesi
- `1b3fc5c`: `fix(relations): lengkapi relasi eloquent hilang pada Project, PurchaseOrder, dan PurchaseOrderItem`
- `1f3a4fb`: `fix(invoices): perbaiki rekalkulasi status faktur yang dilunasi penuh oleh uang muka`
- `f1ad405`: `fix(cost): hitung realisasi biaya aktual riil dari PO tanpa multiplier dummy`
- `534583f`: `fix(rab): sinkronkan ulang realisasi material proyek saat menghapus item atau kategori RAB`
- `64d2f97`: `fix(auth): izinkan pengguna berhak akses faktur membuka route redirect procurement`
- `ecaa621`: `fix(suppliers): cegah hapus supplier yang masih memiliki transaksi DP, pembayaran, atau retur`
