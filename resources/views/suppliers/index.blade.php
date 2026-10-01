@extends('layouts.app')

@section('title', 'Master Supplier & Rekanan — K-RAB')
@section('page_title', 'Master Supplier')
@section('page_subtitle', 'Manajemen Rekanan Vendor, Toko Material & Logistik Lapangan')

@section('content')
<div class="space-y-6" x-data="{
    showModal: false,
    isEdit: false,
    modalTitle: 'Tambah Supplier Baru',
    formAction: '{{ route('suppliers.store') }}',
    formData: {
        name: '',
        contact_person: '',
        phone: '',
        address: ''
    },
    openAddModal() {
        this.isEdit = false;
        this.modalTitle = 'Tambah Supplier Baru';
        this.formAction = '{{ route('suppliers.store') }}';
        this.formData = { name: '', contact_person: '', phone: '', address: '' };
        this.showModal = true;
    },
    openEditModal(supplier) {
        this.isEdit = true;
        this.modalTitle = 'Edit Data Supplier';
        this.formAction = '/suppliers/' + supplier.id;
        this.formData = {
            name: supplier.name || '',
            contact_person: supplier.contact_person || '',
            phone: supplier.phone || '',
            address: supplier.address || ''
        };
        this.showModal = true;
    }
}">

    <!-- 1. KPI SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
        <!-- Card 1: Total Supplier -->
        <div class="card-clean p-4 sm:p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Rekanan Vendor</span>
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">
                {{ $stats['total_suppliers'] }}
            </div>
            <div class="mt-2 text-xs text-slate-500">
                <span>Terdaftar dalam sistem master</span>
            </div>
        </div>

        <!-- Card 2: Supplier Aktif -->
        <div class="card-clean p-4 sm:p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Vendor Aktif Transaksi</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">
                {{ $stats['active_suppliers'] }}
            </div>
            <div class="mt-2 text-xs text-emerald-700 font-semibold flex items-center gap-1.5">
                <span>Memiliki riwayat Pesanan / Penerimaan Pembelian</span>
            </div>
        </div>

        <!-- Card 3: Total DO Masuk -->
        <div class="card-clean p-4 sm:p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Penerimaan (DO)</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">
                {{ $stats['total_dos'] }} <span class="text-xs font-semibold text-slate-500">Penerimaan</span>
            </div>
            <div class="mt-2 text-xs text-slate-500">
                <span>Diterima fisik di lapangan</span>
            </div>
        </div>

        <!-- Card 4: Total Nilai Tagihan -->
        <div class="card-clean p-4 sm:p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Akumulasi Faktur Tagihan</span>
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">
                Rp {{ number_format($stats['total_invoices_amount'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="mt-2 text-xs text-slate-500">
                <span>Nilai invoice terverifikasi</span>
            </div>
        </div>
    </div>

    <!-- 2. DATA TABLE & TOOLBAR -->
    <div class="card-clean overflow-hidden">
        <!-- Toolbar Panel -->
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 bg-white">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20 font-bold flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900">Daftar Master Rekanan & Supplier</h3>
                    <p class="text-xs text-slate-500">Database lengkap vendor material, kontak person, dan riwayat transaksi proyek</p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3">
                <!-- Search Form -->
                <form action="{{ route('suppliers.index') }}" method="GET" class="relative w-full sm:w-auto">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama / kontak / alamat..." 
                           class="w-full sm:w-64 text-xs pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all">
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    @if($search)
                        <a href="{{ route('suppliers.index') }}" class="absolute right-2.5 top-2 text-xs text-slate-400 hover:text-slate-600">&times;</a>
                    @endif
                </form>

                <!-- Add Supplier Button -->
                <button type="button" @click="openAddModal()" 
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Supplier Baru
                </button>
            </div>
        </div>

        <!-- Supplier Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse table-clean">
                <thead>
                    <tr class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3 px-5">Nama Supplier / Rekanan</th>
                        <th class="py-3 px-5">Kontak Person (PIC)</th>
                        <th class="py-3 px-5">No. Telepon / WhatsApp</th>
                        <th class="py-3 px-5">Alamat / Lokasi</th>
                        <th class="py-3 px-5 text-center">Riwayat Transaksi</th>
                        <th class="py-3 px-5 text-right">Total Faktur</th>
                        <th class="py-3 px-5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($suppliers as $supplier)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <!-- Supplier Name & Badge -->
                            <td class="py-4 px-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 text-slate-700 flex items-center justify-center font-black text-sm flex-shrink-0 border border-slate-200/60 shadow-xs">
                                        {{ strtoupper(substr($supplier->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <span class="font-extrabold text-slate-900 block">{{ $supplier->name }}</span>
                                        <span class="text-[11px] text-slate-400 font-mono">ID: #SUP-{{ str_pad($supplier->id, 3, '0', STR_PAD_LEFT) }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Contact Person -->
                            <td class="py-4 px-5">
                                <div class="font-semibold text-slate-800 text-xs">
                                    {{ $supplier->contact_person ?: '— Belum diatur —' }}
                                </div>
                            </td>

                            <!-- Phone -->
                            <td class="py-4 px-5">
                                @if($supplier->phone)
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $supplier->phone) }}" target="_blank" 
                                       class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold bg-emerald-50 text-emerald-700 rounded-lg hover:bg-emerald-100 transition-colors border border-emerald-200/60">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        {{ $supplier->phone }}
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400 italic">—</span>
                                @endif
                            </td>

                            <!-- Address -->
                            <td class="py-4 px-5 max-w-xs">
                                <div class="text-xs text-slate-600 truncate" title="{{ $supplier->address }}">
                                    {{ $supplier->address ?: '—' }}
                                </div>
                            </td>

                            <!-- Transaction Counts -->
                            <td class="py-4 px-5 text-center">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-blue-50 text-blue-700 border border-blue-200" title="Penerimaan Pembelian (DO)">
                                        {{ $supplier->delivery_orders_count }} DO
                                    </span>
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-indigo-50 text-indigo-700 border border-indigo-200" title="Pesanan Pembelian (PO)">
                                        {{ $supplier->purchase_orders_count }} PO
                                    </span>
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-amber-50 text-amber-700 border border-amber-200" title="Invoices">
                                        {{ $supplier->invoices_count }} Inv
                                    </span>
                                </div>
                            </td>

                            <!-- Total Amount -->
                            <td class="py-4 px-5 text-right font-mono font-bold text-xs text-slate-900">
                                @if($supplier->invoices_sum_amount > 0)
                                    Rp {{ number_format($supplier->invoices_sum_amount, 0, ',', '.') }}
                                @else
                                    <span class="text-slate-400 font-normal">Rp 0</span>
                                @endif
                            </td>

                            <!-- Action Buttons -->
                            <td class="py-4 px-5 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" 
                                            @click="openEditModal({ id: {{ $supplier->id }}, name: '{{ addslashes($supplier->name) }}', contact_person: '{{ addslashes($supplier->contact_person ?? '') }}', phone: '{{ addslashes($supplier->phone ?? '') }}', address: '{{ addslashes($supplier->address ?? '') }}' })"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                            title="Edit Supplier">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>

                                    <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus supplier {{ addslashes($supplier->name) }}?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Hapus Supplier">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                                <span class="font-semibold text-slate-600 block">Belum Ada Rekanan Supplier</span>
                                <span class="text-xs text-slate-400 mt-1 block">Silakan tambahkan data supplier pertama melalui tombol di atas.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($suppliers->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>

    <!-- 3. MODAL ADD / EDIT SUPPLIER -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showModal = false" aria-hidden="true"></div>

        <!-- Modal Dialog Container -->
        <div class="flex min-h-full items-center justify-center p-4 text-center relative z-10 pointer-events-none">
            <div class="relative w-full max-w-lg p-6 my-8 text-left bg-white rounded-2xl shadow-2xl border border-slate-200 pointer-events-auto transform transition-all"
                 @click.stop>
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900" x-text="modalTitle"></h3>
                            <p class="text-xs text-slate-500">Kelola informasi identitas rekanan penyedia bahan bangunan</p>
                        </div>
                    </div>
                    <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold p-1">&times;</button>
                </div>

                <!-- Form -->
                <form :action="formAction" method="POST" class="mt-5 space-y-4">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <!-- Nama Supplier -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nama Supplier / Perusahaan <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" x-model="formData.name" required
                               placeholder="Contoh: PT Holcim Semen Indonesia, CV Baja Perkasa..." 
                               class="w-full text-sm p-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition-all">
                    </div>

                    <!-- Contact Person & Phone -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Kontak Person (PIC)
                            </label>
                            <input type="text" name="contact_person" x-model="formData.contact_person"
                                   placeholder="Contoh: Bapak Irwan / Bu Ratna" 
                                   class="w-full text-sm p-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                No. Telepon / WhatsApp
                            </label>
                            <input type="text" name="phone" x-model="formData.phone"
                                   placeholder="Contoh: 081234567890" 
                                   class="w-full text-sm p-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition-all">
                        </div>
                    </div>

                    <!-- Address -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Alamat Lengkap / Kantor Operasional
                        </label>
                        <textarea name="address" x-model="formData.address" rows="3"
                                  placeholder="Contoh: Kawasan Industri Cikarang Barat Blok C No. 12, Bekasi..." 
                                  class="w-full text-sm p-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition-all"></textarea>
                    </div>

                    <!-- Footer Actions -->
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button type="button" @click="showModal = false" 
                                class="px-4 py-2.5 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-colors">
                            Batal
                        </button>
                        <button type="submit" 
                                class="px-5 py-2.5 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 transition-all flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span x-text="isEdit ? 'Simpan Perubahan' : 'Simpan Supplier Baru'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
