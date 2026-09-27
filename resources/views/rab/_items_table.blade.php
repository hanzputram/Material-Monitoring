<div class="overflow-x-auto rounded-xl border border-slate-200">
    <table class="w-full text-left text-xs table-clean">
        <thead class="bg-slate-50/90 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
            <tr>
                <th class="py-2.5 px-4 w-16">No.</th>
                <th class="py-2.5 px-4">Uraian Pekerjaan</th>
                <th class="py-2.5 px-3 text-right">Volume</th>
                <th class="py-2.5 px-3 text-center">Satuan</th>
                <th class="py-2.5 px-4 text-right">Harga Satuan (Rp)</th>
                <th class="py-2.5 px-4 text-right">Jumlah Harga (Rp)</th>
                <th class="py-2.5 px-4 text-center">Aksi / Breakdown</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($items as $item)
                @php
                    $rootMaterials = $item->materials->whereNull('parent_id');
                @endphp
                <tr class="hover:bg-slate-50 transition-colors" x-data="{ showMaterials: true }">
                    <td class="py-3 px-4 font-mono font-bold text-slate-600">{{ $item->item_no }}</td>
                    <td class="py-3 px-4">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-800">{{ $item->name }}</span>
                            @if($item->is_composite || $rootMaterials->count() > 0)
                                <button type="button" @click="showMaterials = !showMaterials" 
                                        class="badge-clean bg-purple-100 text-purple-800 text-[10px] hover:bg-purple-200 transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    {{ $rootMaterials->count() }} BOM
                                </button>
                            @endif
                        </div>
                    </td>
                    <td class="py-3 px-3 text-right font-mono font-semibold text-slate-700">{{ format_qty($item->volume) }}</td>
                    <td class="py-3 px-3 text-center font-semibold text-slate-600">{{ $item->unit?->code }}</td>
                    <td class="py-3 px-4 text-right font-mono font-semibold text-slate-700">{{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td class="py-3 px-4 text-right font-mono font-extrabold text-blue-700">Rp {{ number_format($item->total_price, 2, ',', '.') }}</td>
                    <td class="py-3 px-4 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <!-- Add BOM Material Button -->
                            <button type="button" 
                                    @click="bomModal = { rab_item_id: {{ $item->id }}, itemName: '{{ addslashes($item->name) }}', defaultUnitId: {{ $item->unit_id }} }; showBomModal = true"
                                    class="px-2.5 py-1 text-[11px] font-bold bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-lg border border-purple-200 flex items-center gap-1"
                                    title="Tambah Breakdown Material Dasar">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>BOM</span>
                            </button>
                            <!-- Clone BOM Button -->
                            <button type="button" 
                                    @click="cloneModal = { target_item_id: {{ $item->id }}, itemName: '{{ addslashes($item->name) }}' }; showCloneBomModal = true"
                                    class="px-2 py-1 text-[11px] font-semibold bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg"
                                    title="Duplikasi BOM dari Item Lain">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                            <!-- Delete Item Button -->
                            <form action="{{ route('rab.items.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus item pekerjaan ini?')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 rounded" title="Hapus Item">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>

                <!-- LEVEL 5: MATERIAL BREAKDOWN (BOM) NESTED ROWS -->
                @if($rootMaterials->count() > 0)
                    @foreach($rootMaterials as $mat)
                        <tr class="bg-purple-50/40 text-[11px] text-slate-600 border-t border-dashed border-purple-100 hover:bg-purple-50/60 transition-colors">
                            <td class="py-2 px-4 text-center text-slate-400 font-mono">-</td>
                            <td class="py-2 px-4 pl-8">
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                    <span class="font-semibold text-purple-900">{{ $mat->material?->name }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono">({{ $mat->material?->code }})</span>
                                    @if($mat->breakdowns->count() > 0)
                                        <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-indigo-100 text-indigo-700 border border-indigo-200">
                                            {{ $mat->breakdowns->count() }} Rincian
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-2 px-3 text-right font-mono">{{ format_qty($mat->volume) }}</td>
                            <td class="py-2 px-3 text-center">{{ $mat->unit?->code }}</td>
                            <td class="py-2 px-4 text-right font-mono">{{ number_format($mat->unit_price, 2, ',', '.') }}</td>
                            <td class="py-2 px-4 text-right font-mono font-bold text-purple-900">Rp {{ number_format($mat->total_price, 2, ',', '.') }}</td>
                            <td class="py-2 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Tombol Breakdown untuk Purchasing / Estimator -->
                                    <button type="button" 
                                            @click="breakdownModal = { 
                                                material_id: {{ $mat->id }}, 
                                                parentName: '{{ addslashes($mat->material?->name ?? 'Material') }}', 
                                                parentCode: '{{ addslashes($mat->material?->code ?? '') }}',
                                                parentUnit: '{{ addslashes($mat->unit?->code ?? '') }}',
                                                parentVolume: '{{ format_qty($mat->volume) }}'
                                            }; showBreakdownModal = true"
                                            class="px-2 py-0.5 text-[10px] font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-md border border-indigo-200 flex items-center gap-1 transition-colors"
                                            title="Breakdown / Rincian Material Hasil Jadi">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                                        <span>Breakdown</span>
                                    </button>

                                    <!-- Delete BOM Material -->
                                    <form action="{{ route('rab.materials.destroy', $mat->id) }}" method="POST" onsubmit="return confirm('Hapus breakdown material ini beserta seluruh rinciannya?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 rounded transition-colors" title="Hapus Material">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- LEVEL 5.1: SUB-BREAKDOWN RINCIAN MATERIAL HASIL JADI -->
                        @if($mat->breakdowns->count() > 0)
                            @foreach($mat->breakdowns as $sub)
                                <tr class="bg-indigo-50/25 text-[11px] text-slate-600 border-t border-dotted border-indigo-100 hover:bg-indigo-50/50 transition-colors">
                                    <td class="py-1.5 px-4 text-center text-slate-300 font-mono text-[10px]">&bull;</td>
                                    <td class="py-1.5 px-4 pl-14">
                                        <div class="flex items-center gap-2">
                                            <span class="text-indigo-400 font-mono text-xs font-bold leading-none">↳</span>
                                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 flex-shrink-0"></span>
                                            <span class="font-medium text-slate-800">{{ $sub->material?->name }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono">({{ $sub->material?->code }})</span>
                                            @if($sub->notes)
                                                <span class="text-[10px] text-indigo-700 bg-indigo-50 border border-indigo-100 px-1.5 py-0.5 rounded italic">
                                                    {{ $sub->notes }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-1.5 px-3 text-right font-mono text-slate-700">{{ format_qty($sub->volume) }}</td>
                                    <td class="py-1.5 px-3 text-center text-slate-600">{{ $sub->unit?->code }}</td>
                                    <td class="py-1.5 px-4 text-right font-mono text-slate-600">{{ number_format($sub->unit_price, 2, ',', '.') }}</td>
                                    <td class="py-1.5 px-4 text-right font-mono font-bold text-indigo-900">Rp {{ number_format($sub->total_price, 2, ',', '.') }}</td>
                                    <td class="py-1.5 px-4 text-center">
                                        <form action="{{ route('rab.materials.destroy', $sub->id) }}" method="POST" onsubmit="return confirm('Hapus rincian breakdown ini?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 rounded transition-colors" title="Hapus Rincian">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                @endif
            @empty
                <tr>
                    <td colspan="7" class="py-4 text-center text-slate-400 text-xs">
                        Belum ada item pekerjaan. Klik "+ Item" untuk menambahkan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
