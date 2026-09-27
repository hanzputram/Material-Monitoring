@extends('layouts.app')

@section('title', 'RAB Tree Builder & BOM — ' . $project->name)
@section('page_title', 'RAB Tree Builder & Breakdown Material')
@section('page_subtitle', 'Penyusunan Rencana Anggaran Biaya Berjenjang 5 Level & Bill of Material (BOM)')

@section('content')
<div class="space-y-6" x-data="{
    showNodeModal: false,
    showItemModal: false,
    showBomModal: false,
    showCloneBomModal: false,
    nodeModal: { parent_id: null, level: 1, levelName: 'Kategori Utama (Level 1)' },
    itemModal: { rab_node_id: null, nodeName: '' },
    bomModal: { rab_item_id: null, itemName: '', defaultUnitId: null },
    cloneModal: { target_item_id: null, itemName: '' }
}">

    <!-- Top Action Bar -->
    <div class="card-clean p-4 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="badge-clean bg-blue-100 text-blue-800 text-xs">{{ $project->prototype_type ?: 'Standar' }}</span>
                <span class="text-xs font-semibold text-slate-500">Total Anggaran Proyek:</span>
            </div>
            <div class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight font-mono">
                Rp {{ number_format($project->total_rab, 2, ',', '.') }}
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Add Root Category -->
            <button type="button" @click="nodeModal = { parent_id: null, level: 1, levelName: 'Kategori Utama (Level 1)' }; showNodeModal = true" 
                    class="inline-flex items-center justify-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 transition-all flex-1 sm:flex-initial text-center">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Kategori (Level 1)</span>
            </button>
            <a href="{{ route('rab.import.form', ['project_id' => $project->id]) }}" 
               class="inline-flex items-center justify-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 text-xs font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl shadow-sm transition-all flex-1 sm:flex-initial text-center">
                <svg class="w-4 h-4 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/></svg>
                <span>Impor Excel</span>
            </a>
            <a href="{{ route('rab.export', $project->id) }}" 
               class="inline-flex items-center justify-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl transition-all">
                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Unduh</span>
            </a>
        </div>
    </div>

    <!-- HIERARCHICAL TREE CONTAINER -->
    <div class="space-y-4">
        @forelse($rootNodes as $l1)
            <div class="card-clean overflow-hidden border border-slate-200 shadow-sm" x-data="{ open: true }">
                <!-- LEVEL 1: KATEGORI (Romawi) -->
                <div class="p-3 sm:p-4 bg-slate-100/80 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                    <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                        <button type="button" @click="open = !open" class="p-1 text-slate-500 hover:text-slate-800 transition-transform flex-shrink-0" :class="open ? 'rotate-90' : ''">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                        <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 text-xs font-black bg-blue-600 text-white rounded-lg font-mono flex-shrink-0">{{ $l1->code }}</span>
                        <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 tracking-tight truncate">{{ $l1->name }}</h3>
                    </div>

                    <div class="flex items-center justify-between sm:justify-end gap-3 sm:gap-4 border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-200/60">
                        <div class="text-left sm:text-right">
                            <span class="text-[9px] sm:text-[10px] uppercase font-bold text-slate-400 block leading-none">Subtotal Level 1</span>
                            <span class="text-xs sm:text-sm font-extrabold font-mono text-slate-900">Rp {{ number_format($l1->subtotal_cache, 2, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 border-l border-slate-200 pl-3">
                            <!-- Add Sub Kategori -->
                            <button type="button" @click="nodeModal = { parent_id: {{ $l1->id }}, level: 2, levelName: 'Sub Kategori (Level 2) di bawah {{ $l1->code }}' }; showNodeModal = true" 
                                    class="p-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-100/50 rounded-lg flex items-center gap-1" title="Tambah Sub Kategori">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span class="hidden sm:inline">Sub Kategori</span>
                            </button>
                            <!-- Add Item Directly -->
                            <button type="button" @click="itemModal = { rab_node_id: {{ $l1->id }}, nodeName: '{{ $l1->code }} - {{ $l1->name }}' }; showItemModal = true" 
                                    class="p-1.5 text-xs font-semibold text-emerald-600 hover:bg-emerald-100/50 rounded-lg flex items-center gap-1" title="Tambah Item Pekerjaan Langsung">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                <span class="hidden sm:inline">Item</span>
                            </button>
                            <form action="{{ route('rab.nodes.destroy', $l1->id) }}" method="POST" onsubmit="return confirm('Hapus kategori {{ $l1->name }} beserta seluruh isinya?')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg" title="Hapus Kategori">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- LEVEL 1 CONTENT COLLAPSIBLE -->
                <div x-show="open" class="divide-y divide-slate-100">
                    <!-- Direct Items Under Level 1 (if any) -->
                    @if($l1->rabItems->count() > 0)
                        <div class="bg-white p-2.5 sm:p-4">
                            @include('rab._items_table', ['items' => $l1->rabItems])
                        </div>
                    @endif

                    <!-- LEVEL 2: SUB KATEGORI -->
                    @foreach($l1->children as $l2)
                        <div class="bg-white" x-data="{ openL2: true }">
                            <div class="p-3 pl-3 sm:pl-8 bg-slate-50 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-3">
                                <div class="flex items-center gap-2 min-w-0">
                                    <button type="button" @click="openL2 = !openL2" class="p-1 text-slate-400 hover:text-slate-700 transition-transform flex-shrink-0" :class="openL2 ? 'rotate-90' : ''">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                    <span class="px-1.5 py-0.5 text-[11px] font-bold bg-indigo-50 text-indigo-700 rounded border border-indigo-200 font-mono flex-shrink-0">{{ $l2->code }}</span>
                                    <h4 class="text-xs font-bold text-slate-800 truncate">{{ $l2->name }}</h4>
                                </div>
                                <div class="flex items-center justify-between sm:justify-end gap-3">
                                    <span class="text-xs font-bold font-mono text-slate-800">Rp {{ number_format($l2->subtotal_cache, 2, ',', '.') }}</span>
                                    <div class="flex items-center gap-1 border-l border-slate-200 pl-2">
                                        <button type="button" @click="nodeModal = { parent_id: {{ $l2->id }}, level: 3, levelName: 'Sub-Sub Kategori (Level 3) di bawah {{ $l2->code }}' }; showNodeModal = true" 
                                                class="px-2 py-1 text-[11px] font-semibold text-blue-600 hover:bg-blue-50 rounded">
                                            + Sub-Sub
                                        </button>
                                        <button type="button" @click="itemModal = { rab_node_id: {{ $l2->id }}, nodeName: '{{ $l2->code }} - {{ $l2->name }}' }; showItemModal = true" 
                                                class="px-2 py-1 text-[11px] font-semibold text-emerald-600 hover:bg-emerald-50 rounded">
                                            + Item
                                        </button>
                                        <form action="{{ route('rab.nodes.destroy', $l2->id) }}" method="POST" onsubmit="return confirm('Hapus sub kategori ini?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 rounded">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div x-show="openL2" class="p-2 sm:p-4 pl-3 sm:pl-10 space-y-4">
                                <!-- Direct Items Under Level 2 -->
                                @if($l2->rabItems->count() > 0)
                                    @include('rab._items_table', ['items' => $l2->rabItems])
                                @endif

                                <!-- LEVEL 3: SUB-SUB KATEGORI -->
                                @foreach($l2->children as $l3)
                                    <div class="border border-slate-200 rounded-xl overflow-hidden" x-data="{ openL3: true }">
                                        <div class="p-2.5 sm:p-3 bg-slate-50/80 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-3">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <button type="button" @click="openL3 = !openL3" class="p-1 text-slate-400 hover:text-slate-700 transition-transform flex-shrink-0" :class="openL3 ? 'rotate-90' : ''">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                </button>
                                                <span class="px-1.5 py-0.5 text-[10px] font-bold bg-amber-50 text-amber-800 rounded border border-amber-200 font-mono flex-shrink-0">{{ $l3->code }}</span>
                                                <h5 class="text-xs font-bold text-slate-800 truncate">{{ $l3->name }}</h5>
                                            </div>
                                            <div class="flex items-center justify-between sm:justify-end gap-3">
                                                <span class="text-xs font-bold font-mono text-slate-800">Rp {{ number_format($l3->subtotal_cache, 2, ',', '.') }}</span>
                                                <div class="flex items-center gap-1 border-l border-slate-200 pl-2">
                                                    <button type="button" @click="itemModal = { rab_node_id: {{ $l3->id }}, nodeName: '{{ $l3->code }} - {{ $l3->name }}' }; showItemModal = true" 
                                                            class="px-2 py-0.5 text-[11px] font-semibold text-emerald-600 hover:bg-emerald-50 rounded">
                                                        + Item
                                                    </button>
                                                    <form action="{{ route('rab.nodes.destroy', $l3->id) }}" method="POST" onsubmit="return confirm('Hapus sub-sub kategori ini?')" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 rounded">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <div x-show="openL3" class="p-2 sm:p-3">
                                            @include('rab._items_table', ['items' => $l3->rabItems])
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="card-clean p-12 text-center">
                <div class="w-16 h-16 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="text-base font-bold text-slate-800 mb-1">RAB Proyek Ini Masih Kosong</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto mb-6">Mulai buat struktur kategori RAB secara manual atau impor langsung dari file Excel Bill of Quantity (BQ).</p>
                <div class="flex items-center justify-center gap-3">
                    <button type="button" @click="nodeModal = { parent_id: null, level: 1, levelName: 'Kategori Utama (Level 1)' }; showNodeModal = true" 
                            class="px-4 py-2.5 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md">
                        Buat Kategori Pertama
                    </button>
                    <a href="{{ route('rab.import.form', ['project_id' => $project->id]) }}" 
                       class="px-4 py-2.5 text-xs font-bold bg-white text-slate-700 border border-slate-300 rounded-xl hover:bg-slate-50">
                        Impor Template Excel
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- MODAL: ADD / EDIT NODE (KATEGORI / SUB / SUB-SUB) -->
    <div x-show="showNodeModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showNodeModal = false" aria-hidden="true"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center relative z-10 pointer-events-none">
            <div class="relative w-full max-w-md p-6 my-8 text-left bg-white rounded-2xl shadow-2xl border border-slate-200 pointer-events-auto transform transition-all"
                 @click.stop>
                <form action="{{ route('rab.nodes.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $project->id }}">
                    <input type="hidden" name="parent_id" :value="nodeModal.parent_id">
                    <input type="hidden" name="level" :value="nodeModal.level">

                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <h3 class="text-sm font-bold text-slate-900" x-text="'Tambah ' + nodeModal.levelName"></h3>
                        <button type="button" @click="showNodeModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Kode Kategori (Opsional / Otomatis)</label>
                            <input type="text" name="code" placeholder="Mis. I, I.A, A.1 (kosongkan untuk auto)" 
                                   class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <span class="text-[10px] text-slate-400 mt-1 block">Jika dikosongkan, sistem membuat kode otomatis sesuai urutan level.</span>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Uraian / Nama Kategori <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" required placeholder="Mis. PEKERJAAN STRUKTUR LANTAI 1" 
                                   class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="showNodeModal = false" class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Batal</button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm">Simpan Kategori</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: ADD ITEM PEKERJAAN (LEVEL 4) -->
    <div x-show="showItemModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showItemModal = false" aria-hidden="true"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center relative z-10 pointer-events-none">
            <div class="relative w-full max-w-lg p-6 my-8 text-left bg-white rounded-2xl shadow-2xl border border-slate-200 pointer-events-auto transform transition-all"
                 @click.stop
                 x-data="{ vol: 1, price: 0, get total() { return (this.vol * this.price).toFixed(2); } }">
                <form action="{{ route('rab.items.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="rab_node_id" :value="itemModal.rab_node_id">

                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Tambah Item Pekerjaan (Level 4)</h3>
                            <span class="text-[11px] text-slate-500 font-medium" x-text="'Di bawah: ' + itemModal.nodeName"></span>
                        </div>
                        <button type="button" @click="showItemModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                            <div class="col-span-1">
                                <label class="block font-semibold text-slate-700 mb-1">No. Item</label>
                                <input type="text" name="item_no" placeholder="1.0" 
                                       class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none font-mono">
                            </div>
                            <div class="sm:col-span-3">
                                <label class="block font-semibold text-slate-700 mb-1">Uraian Pekerjaan <span class="text-rose-500">*</span></label>
                                <input type="text" name="name" required placeholder="Mis. Pemasangan Keramik 25x20" 
                                       class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="font-semibold text-slate-700">Volume <span class="text-rose-500">*</span></label>
                                    <button type="button" @click="$dispatch('open-calc')" class="text-[10px] text-blue-600 font-bold hover:underline">Kalkulator</button>
                                </div>
                                <input type="number" step="any" name="volume" x-model="vol" required 
                                       class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Satuan <span class="text-rose-500">*</span></label>
                                <select name="unit_id" required class="w-full select-clean p-2.5 bg-white text-slate-800">
                                    @foreach($units as $u)
                                        <option value="{{ $u->id }}">{{ $u->code == $u->name ? $u->code : $u->name . ' (' . $u->code . ')' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Harga Satuan (Rp) <span class="text-rose-500">*</span></label>
                                <input type="number" step="any" name="unit_price" x-model="price" required 
                                       class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none font-mono">
                            </div>
                        </div>

                        <!-- Computed Preview -->
                        <div class="p-3 bg-blue-50/60 border border-blue-100 rounded-xl flex items-center justify-between">
                            <span class="text-xs font-semibold text-blue-700">Estimasi Jumlah Harga:</span>
                            <span class="text-sm font-extrabold font-mono text-blue-900" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(total)"></span>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="showItemModal = false" class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Batal</button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg shadow-sm">Simpan Item Pekerjaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: ADD LEVEL 5 BREAKDOWN MATERIAL (BOM) -->
    <div x-show="showBomModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showBomModal = false" aria-hidden="true"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center relative z-10 pointer-events-none">
            <div class="relative w-full max-w-lg p-6 my-8 text-left bg-white rounded-2xl shadow-2xl border border-slate-200 pointer-events-auto transform transition-all"
                 @click.stop
                 x-data="{ bomVol: 1, bomPrice: 0, get bomTotal() { return (this.bomVol * this.bomPrice).toFixed(2); } }">
                <form :action="'{{ url('/rab/items') }}/' + bomModal.rab_item_id + '/material'" method="POST">
                    @csrf
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div>
                            <span class="badge-clean bg-purple-100 text-purple-800 text-[10px] mb-1">Level 5 — Bill of Material</span>
                            <h3 class="text-sm font-bold text-slate-900">Breakdown Material Dasar</h3>
                            <span class="text-xs text-slate-500 font-medium" x-text="'Untuk Item: ' + bomModal.itemName"></span>
                        </div>
                        <button type="button" @click="showBomModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Pilih Material Dasar (Katalog Terpusat) <span class="text-rose-500">*</span></label>
                            <select name="material_id" required 
                                    @change="
                                        let opt = $event.target.selectedOptions[0];
                                        bomPrice = opt.getAttribute('data-price') || 0;
                                    "
                                    class="w-full select-clean p-2.5 bg-white text-slate-800">
                                <option value="">-- Pilih Material Dasar --</option>
                                @foreach($materials as $m)
                                    <option value="{{ $m->id }}" data-price="{{ $m->standard_price }}">
                                        {{ $m->name }} ({{ $m->code }}) — Ref: Rp {{ number_format($m->standard_price, 0, ',', '.') }}/{{ $m->defaultUnit?->code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Volume Kebutuhan <span class="text-rose-500">*</span></label>
                                <input type="number" step="any" name="volume" x-model="bomVol" required 
                                       class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Satuan <span class="text-rose-500">*</span></label>
                                <select name="unit_id" required class="w-full select-clean p-2.5 bg-white text-slate-800">
                                    @foreach($units as $u)
                                        <option value="{{ $u->id }}">{{ $u->code }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Harga Satuan (Rp) <span class="text-rose-500">*</span></label>
                                <input type="number" step="any" name="unit_price" x-model="bomPrice" required 
                                       class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none font-mono">
                            </div>
                        </div>

                        <div class="p-3 bg-purple-50 border border-purple-100 rounded-xl flex items-center justify-between">
                            <span class="text-xs font-semibold text-purple-800">Subtotal Material:</span>
                            <span class="text-sm font-extrabold font-mono text-purple-900" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(bomTotal)"></span>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="showBomModal = false" class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Batal</button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold bg-purple-600 hover:bg-purple-700 text-white rounded-lg shadow-sm">Tambahkan ke BOM</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: CLONE BOM DARI ITEM LAIN -->
    <div x-show="showCloneBomModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showCloneBomModal = false" aria-hidden="true"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center relative z-10 pointer-events-none">
            <div class="relative w-full max-w-md p-6 my-8 text-left bg-white rounded-2xl shadow-2xl border border-slate-200 pointer-events-auto transform transition-all"
                 @click.stop>
                <form :action="'{{ url('/rab/items') }}/' + cloneModal.target_item_id + '/clone-bom'" method="POST">
                    @csrf
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Duplikasi Template BOM</h3>
                            <span class="text-xs text-slate-500 font-medium" x-text="'Target: ' + cloneModal.itemName"></span>
                        </div>
                        <button type="button" @click="showCloneBomModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Pilih Sumber BOM yang Sudah Pernah Dibuat:</label>
                            <select name="source_item_id" required class="w-full select-clean p-2.5 bg-white text-slate-800">
                                <option value="">-- Pilih Item Sumber --</option>
                                @foreach($itemsWithBom as $src)
                                    <option value="{{ $src->id }}">
                                        {{ $src->rabNode->project->name }} &rarr; {{ $src->name }} ({{ $src->materials->count() }} material)
                                    </option>
                                @endforeach
                            </select>
                            <span class="text-[10px] text-slate-400 mt-1 block">Seluruh rincian material dasar dari item sumber akan disalin ke item target.</span>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="showCloneBomModal = false" class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Batal</button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm">Salin BOM Sekarang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
