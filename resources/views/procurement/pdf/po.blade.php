<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order - {{ $po->po_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 14mm 14mm 14mm 14mm;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.35;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
        }

        /* Utility */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .italic { font-style: italic; }

        /* KOP SURAT / HEADER */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .kop-table td {
            vertical-align: top;
        }

        .company-title {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
            margin: 0 0 2px 0;
        }

        .company-sub {
            font-size: 7.5pt;
            font-weight: bold;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 0 4px 0;
        }

        .company-contact {
            font-size: 7.5pt;
            color: #475569;
            line-height: 1.3;
        }

        .po-title-box {
            text-align: right;
        }

        .po-main-title {
            font-size: 14pt;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 1px;
            margin: 0 0 2px 0;
        }

        .po-meta-table {
            margin-left: auto;
            border-collapse: collapse;
            font-size: 8.5pt;
        }

        .po-meta-table td {
            padding: 1.5px 0 1.5px 8px;
        }

        /* Garis Pemisah Kop */
        .separator-double {
            border-top: 2px solid #0f172a;
            border-bottom: 0.5px solid #0f172a;
            height: 2px;
            margin-bottom: 12px;
        }

        /* KOTAK INFORMASI PIHAK */
        .parties-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .parties-table > tbody > tr > td {
            width: 50%;
            vertical-align: top;
        }

        .info-card {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            min-height: 88px;
        }

        .info-card-left {
            margin-right: 5px;
        }

        .info-card-right {
            margin-left: 5px;
        }

        .info-card-title {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #334155;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }

        .info-data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
        }

        .info-data-table td {
            padding: 1.5px 0;
            vertical-align: top;
        }

        .info-data-table td.label {
            width: 32%;
            color: #64748b;
        }

        .info-data-table td.value {
            color: #0f172a;
        }

        /* TABEL ITEM MATERIAL */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .items-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            border-top: 1.5px solid #334155;
            border-bottom: 1.5px solid #334155;
            padding: 6px 6px;
            letter-spacing: 0.3px;
        }

        .items-table td {
            padding: 5.5px 6px;
            border-bottom: 0.5px solid #e2e8f0;
            font-size: 8.5pt;
            color: #1e293b;
        }

        .items-table tr.total-row td {
            background-color: #f8fafc;
            border-top: 1.5px solid #334155;
            border-bottom: 2px solid #334155;
            font-weight: bold;
            color: #0f172a;
            padding: 6px 6px;
            font-size: 9pt;
        }

        /* TERBILANG & CATATAN */
        .terbilang-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 6px 10px;
            font-size: 8pt;
            margin-bottom: 8px;
            color: #1e293b;
        }

        .notes-box {
            border: 1px dashed #cbd5e1;
            padding: 6px 10px;
            font-size: 8pt;
            margin-bottom: 10px;
            color: #334155;
            background-color: #ffffff;
        }

        /* SYARAT & KETENTUAN */
        .terms-section {
            margin-bottom: 14px;
            padding-top: 2px;
        }

        .terms-title {
            font-size: 7.5pt;
            font-weight: bold;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .terms-list {
            margin: 0;
            padding-left: 14px;
            font-size: 7.5pt;
            color: #475569;
            line-height: 1.35;
        }

        .terms-list li {
            margin-bottom: 1.5px;
        }

        /* TANDA TANGAN */
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .signatures-table td {
            width: 25%;
            vertical-align: top;
            text-align: center;
            font-size: 8pt;
        }

        .sig-role {
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .sig-sub {
            font-size: 7pt;
            color: #64748b;
        }

        .sig-space {
            height: 48px;
        }

        .sig-name {
            font-weight: bold;
            color: #0f172a;
            border-bottom: 1px solid #94a3b8;
            display: inline-block;
            min-width: 120px;
            padding-bottom: 2px;
        }

        .sig-date {
            font-size: 7pt;
            color: #64748b;
            margin-top: 3px;
        }

        /* FOOTER DOKUMEN */
        .doc-footer {
            margin-top: 14px;
            padding-top: 6px;
            border-top: 0.5px solid #cbd5e1;
            font-size: 7pt;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- KOP RESMI PERUSAHAAN & HEADER PO -->
    <table class="kop-table">
        <tr>
            <td style="width: 58%;">
                <div class="company-title">PT WIRA KONSTRUKSI UTAMA</div>
                <div class="company-sub">Divisi Pengadaan & Logistik Proyek (Procurement)</div>
                <div class="company-contact">
                    Jl. Jenderal Sudirman Kav. 52-53, Jakarta Selatan 12190<br>
                    Telp: (021) 5299-8800 • Email: procurement@konstruksi.id
                </div>
            </td>
            <td style="width: 42%;" class="po-title-box">
                <div class="po-main-title">PURCHASE ORDER</div>
                <div style="font-size: 8pt; font-weight: bold; color: #475569; margin-bottom: 4px;">SURAT PESANAN PEMBELIAN</div>
                <table class="po-meta-table">
                    <tr>
                        <td class="text-right font-bold" style="color: #64748b;">No. PO:</td>
                        <td class="text-left font-bold" style="color: #0f172a; font-family: monospace; font-size: 9pt;">{{ $po->po_number }}</td>
                    </tr>
                    <tr>
                        <td class="text-right font-bold" style="color: #64748b;">Tanggal:</td>
                        <td class="text-left" style="color: #0f172a;">{{ \Carbon\Carbon::parse($po->po_date)->translatedFormat('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-right font-bold" style="color: #64748b;">Status:</td>
                        <td class="text-left font-bold" style="color: #0f172a; text-transform: uppercase;">Resmi Diterbitkan</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="separator-double"></div>

    <!-- KOTAK IDENTITAS VENDOR & PENGIRIMAN -->
    <table class="parties-table">
        <tr>
            <td>
                <div class="info-card info-card-left">
                    <div class="info-card-title">Pemasok / Rekanan (Vendor)</div>
                    <table class="info-data-table">
                        <tr>
                            <td class="label font-bold">Nama Rekanan</td>
                            <td class="value font-bold">{{ $supplier->name }}</td>
                        </tr>
                        <tr>
                            <td class="label">UP / Kontak</td>
                            <td class="value">{{ $supplier->contact_person ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Telepon</td>
                            <td class="value">{{ $supplier->phone ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Alamat</td>
                            <td class="value">{{ $supplier->address ?: 'Sesuai database rekanan supplier' }}</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td>
                <div class="info-card info-card-right">
                    <div class="info-card-title">Informasi Proyek & Pengiriman</div>
                    <table class="info-data-table">
                        <tr>
                            <td class="label font-bold">Nama Proyek</td>
                            <td class="value font-bold">{{ $project->name }}</td>
                        </tr>
                        <tr>
                            <td class="label">Tujuan Kirim</td>
                            <td class="value">Gudang / Site Proyek (T.A. {{ $project->budget_year }})</td>
                        </tr>
                        <tr>
                            <td class="label">Dibuat Oleh</td>
                            <td class="value">{{ $creator?->name ?? 'Purchasing Officer' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Penerima Site</td>
                            <td class="value">Pengawas Lapangan Proyek</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- TABEL RINCIAN ITEM MATERIAL -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 4%;">No</th>
                <th class="text-center" style="width: 14%;">Kode</th>
                <th class="text-left" style="width: 38%;">Uraian Material & Spesifikasi</th>
                <th class="text-right" style="width: 12%;">Volume</th>
                <th class="text-center" style="width: 7%;">Satuan</th>
                <th class="text-right" style="width: 12%;">Harga (Rp)</th>
                <th class="text-right" style="width: 13%;">Total (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $idx => $it)
                @php
                    $lineTotal = (float)$it->qty_ordered * (float)$it->unit_price;
                @endphp
                <tr>
                    <td class="text-center" style="color: #64748b;">{{ $idx + 1 }}</td>
                    <td class="text-center" style="font-family: monospace; font-size: 8pt; color: #334155;">{{ $it->material?->code ?? '-' }}</td>
                    <td class="text-left">
                        <div class="font-bold" style="color: #0f172a;">{{ $it->material?->name ?? 'Material' }}</div>
                        @if($it->material?->specification)
                            <div style="font-size: 7.5pt; color: #64748b; margin-top: 1px;">Spek: {{ $it->material->specification }}</div>
                        @endif
                        @if($it->rabItem)
                            <div style="font-size: 7.5pt; color: #1d4ed8; margin-top: 2px; font-weight: bold;">
                                Pekerjaan (RAB): [{{ $it->rabItem->item_no ?? '-' }}] {{ $it->rabItem->name }}
                                @if($it->rabItem->rabNode)
                                    <span style="font-weight: normal; color: #64748b;">({{ $it->rabItem->rabNode->code }} - {{ $it->rabItem->rabNode->name }})</span>
                                @endif
                            </div>
                        @endif
                    </td>
                    <td class="text-right font-bold" style="color: #0f172a;">{{ format_qty($it->qty_ordered) }}</td>
                    <td class="text-center" style="color: #475569;">{{ $it->unit?->code ?? ($it->material?->defaultUnit?->code ?? '-') }}</td>
                    <td class="text-right" style="color: #334155;">{{ number_format($it->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right font-bold" style="color: #0f172a;">{{ number_format($lineTotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach

            <!-- TOTAL SUMMARY ROW -->
            <tr class="total-row">
                <td colspan="6" class="text-right uppercase">Total Nilai Pembelian (PO):</td>
                <td class="text-right">Rp {{ number_format($totalAmount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- TERBILANG -->
    <div class="terbilang-box">
        <span class="font-bold" style="color: #334155;">Terbilang:</span>
        <span class="italic font-bold" style="color: #0f172a;">{{ $terbilang }}</span>
    </div>

    <!-- CATATAN KHUSUS (JIKA ADA) -->
    @if(!empty($po->notes))
        <div class="notes-box">
            <span class="font-bold" style="color: #0f172a;">Catatan Khusus Pengadaan:</span>
            <div style="margin-top: 2px;">{{ $po->notes }}</div>
        </div>
    @endif

    <!-- SYARAT & KETENTUAN PENGADAAN (RESMI & FORMAL) -->
    <div class="terms-section">
        <div class="terms-title">Syarat & Ketentuan Standar Pengadaan:</div>
        <ol class="terms-list">
            <li>Pengiriman material ke lokasi proyek wajib menyertakan Surat Jalan (Delivery Order) fisik resmi rangkap 3 yang mencantumkan Nomor PO ini.</li>
            <li>Material yang dikirim wajib diperiksa fisik dan diverifikasi oleh Pengawas Lapangan. Barang yang rusak, cacat, atau tidak sesuai spesifikasi berhak ditolak.</li>
            <li>Penagihan (Faktur/Invoice) wajib melampirkan salinan Purchase Order ini serta Surat Jalan asli yang telah ditandatangani Pengawas Lapangan.</li>
            <li>Perubahan harga atau volume tanpa persetujuan tertulis dari manajemen proyek dinyatakan batal dan tidak berlaku.</li>
        </ol>
    </div>

    <!-- BLOK PENGESAHAN / TANDA TANGAN (4 PIHAK) -->
    <table class="signatures-table">
        <tr>
            <td>
                <div class="sig-role">Dibuat Oleh,</div>
                <div class="sig-sub">Purchasing Officer</div>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $creator?->name ?? 'Purchasing Staff' }}</div>
                <div class="sig-date">Tgl: {{ \Carbon\Carbon::parse($po->po_date)->format('d/m/Y') }}</div>
            </td>
            <td>
                <div class="sig-role">Diperiksa Oleh,</div>
                <div class="sig-sub">Pengawas Lapangan</div>
                <div class="sig-space"></div>
                <div class="sig-name">( ........................................ )</div>
                <div class="sig-date">Tgl: ....................................</div>
            </td>
            <td>
                <div class="sig-role">Disetujui Oleh,</div>
                <div class="sig-sub">Project Manager</div>
                <div class="sig-space"></div>
                <div class="sig-name">( ........................................ )</div>
                <div class="sig-date">Tgl: ....................................</div>
            </td>
            <td>
                <div class="sig-role">Diterima & Dikonfirmasi,</div>
                <div class="sig-sub">Pihak Rekanan / Vendor</div>
                <div class="sig-space"></div>
                <div class="sig-name">( {{ $supplier->contact_person ?: '........................................' }} )</div>
                <div class="sig-date">Tgl: ....................................</div>
            </td>
        </tr>
    </table>

    <!-- FOOTER INFORMASI DOKUMEN -->
    <div class="doc-footer">
        Dokumen Purchase Order ini sah dan diterbitkan secara digital melalui Sistem Material Monitoring & RAB Konstruksi.<br>
        Dicetak pada: {{ \Carbon\Carbon::now()->translatedFormat('d F Y, H:i') }} WIB • Arsip Dokumen Pengadaan Resmi
    </div>

</body>
</html>
