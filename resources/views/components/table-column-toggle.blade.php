{{-- Reusable Table Column Visibility Dropdown Component --}}
@props([
    'storageKey' => 'default_table_cols',
])

<div class="relative inline-block text-left" x-data="{
    open: false,
    search: '',
    filteredColumns() {
        if (!this.search.trim()) return this.cols;
        const q = this.search.toLowerCase();
        return this.cols.filter(c => c.label.toLowerCase().includes(q));
    }
}" @click.outside="open = false">
    <!-- Gear Settings Button -->
    <button type="button" 
            @click="open = !open" 
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 rounded-xl shadow-xs transition-colors cursor-pointer"
            :class="open ? 'ring-2 ring-blue-500 border-blue-500 text-blue-700' : ''"
            title="Kustomisasi Kolom Tabel">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <svg class="w-3 h-3 text-slate-400 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <!-- Dropdown Panel -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute right-0 mt-1.5 w-60 bg-white rounded-xl shadow-xl border border-slate-200 z-50 overflow-hidden"
         x-cloak>
        <!-- Search Box -->
        <div class="p-2 border-b border-slate-100 bg-slate-50/50">
            <div class="relative">
                <input type="text" 
                       x-model="search" 
                       placeholder="Search/Select..." 
                       class="w-full text-xs pl-7 pr-3 py-1.5 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 font-medium">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <!-- Columns Checklist -->
        <div class="max-h-60 overflow-y-auto p-1.5 divide-y divide-slate-50">
            <template x-for="col in filteredColumns()" :key="col.key">
                <label class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 text-xs font-medium text-slate-700 cursor-pointer select-none transition-colors">
                    <input type="checkbox" 
                           :checked="col.visible" 
                           @change="toggleCol(col.key)" 
                           class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-3.5 h-3.5">
                    <span x-text="col.label" class="flex-1"></span>
                </label>
            </template>
            <div x-show="filteredColumns().length === 0" class="p-3 text-center text-xs text-slate-400 italic">
                Kolom tidak ditemukan
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="p-2 border-t border-slate-100 bg-slate-50/80 flex items-center justify-between text-[11px]">
            <button type="button" @click="setAllCols(true)" class="font-bold text-blue-600 hover:text-blue-800 cursor-pointer">
                Pilih Semua
            </button>
            <button type="button" @click="resetCols()" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                Reset Default
            </button>
        </div>
    </div>
</div>
