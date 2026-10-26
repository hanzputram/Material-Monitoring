# Project Progress & Memory: Material-Monitoring

## Status Terakhir (2026-10-26)
- **Status Sesi**: Memulai audit seluruh bug pada modul dan relasi aplikasi (`debug-existing`).
- **Baseline Git**: Branch `main`, commit awal `c82c68b`, working tree clean.
- **Baseline Test Suite**: `php artisan test --compact` -> 59 tests passed, 278 assertions (durasi ~28s).
- **Baseline Asset Build**: `npm run build` -> Vite v8 build sukses (4.4s).
- **Sedang Dikerjakan**: Audit menyeluruh pada seluruh modul, relasi Eloquent, route integrity, foreign key integrity, dan edge-case logic.

## Perintah Penting
- **Dev**: `composer run dev`
- **Test**: `php artisan test --compact`
- **Build**: `npm run build`
- **Lint / Format**: `vendor/bin/pint --format agent`

## Baseline Hasil Pengujian Awal
- `tests/Feature/ExampleTest.php`: Pass
- `tests/Feature/FinanceAndPurchasingModulesTest.php`: Pass
- `tests/Feature/RabSystemTest.php`: Pass
- `tests/Feature/WorkerManagementTest.php`: Pass
- `tests/Unit/ExampleTest.php`: Pass
- `tests/Unit/RabTemplateV2ParserTest.php`: Pass

## Bug Yang Ditemukan & Dalam Penelusuran
- (Akan diisi secara berurutan sesuai temuan audit)

## Keputusan / Catatan
- Format kode harus selalu lolos `vendor/bin/pint --format agent`.
- Seluruh bug yang ditemukan dibuatkan test karakterisasi/regresi terlebih dahulu sebelum perbaikan, dan diperbaiki satu per satu dengan commit terverifikasi.
