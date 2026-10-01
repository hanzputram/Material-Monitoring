<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CostComparisonController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\FinanceSummaryController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PurchaseDownPaymentController;
use App\Http\Controllers\PurchasePaymentController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\RabBuilderController;
use App\Http\Controllers\RabImportExportController;
use App\Http\Controllers\RoleSimulationController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VarianceValidationController;
use App\Http\Controllers\WorkerController;
use Illuminate\Support\Facades\Route;

// ============================================================
// 1. PUBLIC & AUTHENTICATION ROUTES
// ============================================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ============================================================
// 2. PROTECTED ENTERPRISE APPLICATION (REQUIRES LOGIN)
// ============================================================
Route::middleware('auth')->group(function () {

    // 1. Dashboard, Detail Material & Project Switcher (Modul: dashboard)
    Route::get('/', [DashboardController::class, 'index'])->middleware('module:dashboard')->name('dashboard');
    Route::get('/monitoring/material/{realization}', [DashboardController::class, 'showMaterialDetail'])->middleware('module:dashboard')->name('monitoring.material.show');
    Route::post('/projects/switch', [DashboardController::class, 'switchProject'])->name('projects.switch');

    // 2. Manajemen Proyek & Wizard (Modul: projects)
    Route::resource('projects', ProjectController::class)->middleware('module:projects');

    // 3. Master Supplier & Rekanan (Modul: suppliers)
    Route::resource('suppliers', SupplierController::class)->middleware('module:suppliers');

    // 3.1 Master Item, Material & Satuan (Modul: materials)
    Route::post('materials/units', [MaterialController::class, 'storeUnit'])->name('materials.units.store');
    Route::put('materials/units/{unit}', [MaterialController::class, 'updateUnit'])->name('materials.units.update');
    Route::delete('materials/units/{unit}', [MaterialController::class, 'destroyUnit'])->name('materials.units.destroy');
    Route::post('materials/{material}/toggle-status', [MaterialController::class, 'toggleStatus'])->name('materials.toggle_status');
    Route::resource('materials', MaterialController::class);

    // 4. Pembuatan RAB (Modul: rab)
    Route::prefix('rab')->name('rab.')->middleware('module:rab')->group(function () {
        Route::get('/builder', [RabBuilderController::class, 'index'])->name('builder');
        Route::post('/nodes', [RabBuilderController::class, 'storeNode'])->name('nodes.store');
        Route::put('/nodes/{node}', [RabBuilderController::class, 'updateNode'])->name('nodes.update');
        Route::delete('/nodes/{node}', [RabBuilderController::class, 'destroyNode'])->name('nodes.destroy');

        Route::post('/items', [RabBuilderController::class, 'storeItem'])->name('items.store');
        Route::put('/items/{item}', [RabBuilderController::class, 'updateItem'])->name('items.update');
        Route::delete('/items/{item}', [RabBuilderController::class, 'destroyItem'])->name('items.destroy');

        Route::post('/items/{item}/material', [RabBuilderController::class, 'storeItemMaterial'])->name('items.material.store');
        Route::post('/materials/{material}/breakdown', [RabBuilderController::class, 'storeMaterialBreakdown'])->name('materials.breakdown');
        Route::delete('/materials/{material}', [RabBuilderController::class, 'destroyItemMaterial'])->name('materials.destroy');
        Route::post('/items/{item}/clone-bom', [RabBuilderController::class, 'cloneBom'])->name('items.clone_bom');

        // Excel Template, Preview, Import & Export
        Route::get('/template/download', [RabImportExportController::class, 'downloadTemplate'])->name('template.download');
        Route::get('/import', [RabImportExportController::class, 'showImportForm'])->name('import.form');
        Route::post('/import/preview', [RabImportExportController::class, 'previewImport'])->name('import.preview');
        Route::post('/import/commit', [RabImportExportController::class, 'commitImport'])->name('import.commit');
        Route::get('/export/{project}', [RabImportExportController::class, 'export'])->name('export');
    });

    // 5. Procurement Terpisah (Purchase Order, Surat Jalan DO, dan Faktur Invoice)
    Route::prefix('procurement')->name('procurement.')->group(function () {
        Route::get('/', [ProcurementController::class, 'index'])->middleware('module:procurement')->name('index');

        // Submodul 1: Purchase Order (PO)
        Route::get('/po', [ProcurementController::class, 'poIndex'])->middleware('module:po_read')->name('po.index');
        Route::post('/po', [ProcurementController::class, 'storePo'])->middleware('module:po_write')->name('po.store');
        Route::get('/po/{purchaseOrder}/pdf', [ProcurementController::class, 'downloadPoPdf'])->middleware('module:po_read')->name('po.pdf');

        // Submodul 2: Surat Jalan (DO Lapangan)
        Route::get('/do', [ProcurementController::class, 'doIndex'])->middleware('module:do_read')->name('do.index');
        Route::post('/do', [ProcurementController::class, 'storeDo'])->middleware('module:do_write')->name('do.store');
        Route::delete('/do/{deliveryOrder}', [ProcurementController::class, 'destroyDo'])->middleware('module:do_write')->name('do.destroy');

        // Submodul 3: Faktur Tagihan (Invoice)
        Route::get('/invoices', [ProcurementController::class, 'invoiceIndex'])->middleware('module:invoice_read')->name('invoices.index');
        Route::post('/invoices', [ProcurementController::class, 'storeInvoice'])->middleware('module:invoice_write')->name('invoice.store');
        Route::delete('/invoices/{invoice}', [ProcurementController::class, 'destroyInvoice'])->middleware('module:invoice_write')->name('invoices.destroy');

        // Submodul 4: Retur Pembelian (Purchase Return)
        Route::get('/returns', [PurchaseReturnController::class, 'index'])->middleware('module:return_read')->name('returns.index');
        Route::post('/returns', [PurchaseReturnController::class, 'store'])->middleware('module:return_write')->name('returns.store');
        Route::delete('/returns/{purchaseReturn}', [PurchaseReturnController::class, 'destroy'])->middleware('module:return_write')->name('returns.destroy');
    });

    // 5.1 Modul Keuangan & Pembayaran
    Route::prefix('finance')->name('finance.')->group(function () {
        // Uang Muka Pembelian (Purchase Down Payment)
        Route::get('/down-payments', [PurchaseDownPaymentController::class, 'index'])->middleware('module:down_payment_read')->name('down-payments.index');
        Route::post('/down-payments', [PurchaseDownPaymentController::class, 'store'])->middleware('module:down_payment_write')->name('down-payments.store');
        Route::delete('/down-payments/{purchaseDownPayment}', [PurchaseDownPaymentController::class, 'destroy'])->middleware('module:down_payment_write')->name('down-payments.destroy');

        // Faktur Pembelian (Alias ke Invoices)
        Route::get('/invoices', [ProcurementController::class, 'invoiceIndex'])->middleware('module:invoice_read')->name('invoices.index');
        Route::post('/invoices', [ProcurementController::class, 'storeInvoice'])->middleware('module:invoice_write')->name('invoices.store');
        Route::delete('/invoices/{invoice}', [ProcurementController::class, 'destroyInvoice'])->middleware('module:invoice_write')->name('invoices.destroy');

        // Pembayaran Pembelian (Purchase Payment / Settlement)
        Route::get('/payments', [PurchasePaymentController::class, 'index'])->middleware('module:payment_read')->name('payments.index');
        Route::post('/payments', [PurchasePaymentController::class, 'store'])->middleware('module:payment_write')->name('payments.store');
        Route::delete('/payments/{purchasePayment}', [PurchasePaymentController::class, 'destroy'])->middleware('module:payment_write')->name('payments.destroy');

        // Summary Pembelian dari Masing-Masing Toko Lintas Proyek
        Route::get('/supplier-summary', [FinanceSummaryController::class, 'supplierSummary'])->middleware('module:finance')->name('supplier-summary.index');
    });

    // 6. Material Realization & Dual Approval Variance Validation (Modul: variance)
    Route::prefix('variance')->name('variance.')->middleware('module:variance')->group(function () {
        Route::get('/', [VarianceValidationController::class, 'index'])->name('index');
        Route::post('/create', [VarianceValidationController::class, 'createValidation'])->name('create');
        Route::post('/{validation}/pengawas', [VarianceValidationController::class, 'validatePengawas'])->name('validate.pengawas');
        Route::post('/{validation}/purchasing', [VarianceValidationController::class, 'validatePurchasing'])->name('validate.purchasing');
    });

    // 7. Alert Center (Modul: alerts)
    Route::prefix('alerts')->name('alerts.')->middleware('module:alerts')->group(function () {
        Route::get('/', [AlertController::class, 'index'])->name('index');
        Route::post('/{alert}/read', [AlertController::class, 'markAsRead'])->name('mark_read');
        Route::post('/{alert}/resolve', [AlertController::class, 'markAsResolved'])->name('mark_resolved');
    });

    // 8. Equipment Master & Fast Bulk Input (Modul: equipment)
    Route::prefix('equipment')->name('equipment.')->middleware('module:equipment')->group(function () {
        Route::get('/', [EquipmentController::class, 'index'])->name('index');
        Route::post('/master', [EquipmentController::class, 'storeMaster'])->name('master.store');
        Route::put('/master/{equipmentMaster}', [EquipmentController::class, 'updateMaster'])->name('master.update');
        Route::delete('/master/{equipmentMaster}', [EquipmentController::class, 'destroyMaster'])->name('master.destroy');
        Route::post('/categories', [EquipmentController::class, 'storeCategory'])->name('categories.store');
        Route::post('/fast-bulk', [EquipmentController::class, 'fastBulkStore'])->name('fast_bulk');
        Route::put('/project/{equipment}', [EquipmentController::class, 'updateProjectEquipment'])->name('project.update');
        Route::delete('/project/{equipment}', [EquipmentController::class, 'destroyProjectEquipment'])->name('project.destroy');
    });

    // 8.1 Pekerja / Tukang Master & Alokasi Proyek (Modul: workers)
    Route::prefix('workers')->name('workers.')->middleware('module:workers')->group(function () {
        Route::get('/', [WorkerController::class, 'index'])->name('index');
        Route::post('/master', [WorkerController::class, 'storeMaster'])->name('master.store');
        Route::put('/master/{worker}', [WorkerController::class, 'updateMaster'])->name('master.update');
        Route::delete('/master/{worker}', [WorkerController::class, 'destroyMaster'])->name('master.destroy');
        Route::post('/assign', [WorkerController::class, 'assignSingle'])->name('assign');
        Route::post('/fast-bulk', [WorkerController::class, 'fastBulkAssign'])->name('fast_bulk');
        Route::put('/project/{projectWorker}', [WorkerController::class, 'updateProjectWorker'])->name('project.update');
        Route::delete('/project/{projectWorker}', [WorkerController::class, 'destroyProjectWorker'])->name('project.destroy');
    });

    // 9. Cost Realization (RAB vs Actual Comparison) (Modul: cost)
    Route::get('/cost', [CostComparisonController::class, 'index'])->middleware('module:cost')->name('cost.index');

    // 10. Manajemen Pengguna & Hak Akses (Modul: users)
    Route::resource('users', UserController::class)->middleware('module:users');

    // Opsional: Role switcher untuk testing jika masih dibutuhkan oleh superadmin
    Route::post('/role/switch', [RoleSimulationController::class, 'switchRole'])->name('role.switch');
});

// Fallback direct route untuk melayani file bukti fisik dari storage/app/public jika symlink terkendala
Route::get('/storage/{path}', function (string $path) {
    $cleanPath = str_replace(['..', "\0"], '', $path);
    $fullPath = storage_path('app/public/'.$cleanPath);

    if (! file_exists($fullPath) || is_dir($fullPath)) {
        abort(404, 'File lampiran atau bukti fisik tidak ditemukan.');
    }

    return response()->file($fullPath);
})->where('path', '.*')->name('storage.fallback');
