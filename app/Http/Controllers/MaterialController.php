<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MaterialController extends Controller
{
    /**
     * Tampilkan katalog Master Item & Material beserta Master Satuan
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $category = $request->query('category');
        $status = $request->query('status');
        $activeTab = $request->query('tab', 'materials');

        $query = Material::with('defaultUnit')
            ->withCount(['rabItemMaterials', 'purchaseOrderItems', 'deliveryOrderItems']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('specification', 'like', "%{$search}%");
            });
        }

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $materials = $query->orderBy('name')->paginate(15)->withQueryString();

        $units = Unit::withCount('materials')->orderBy('code')->get();

        $categories = [
            'bahan_dasar' => 'Bahan Dasar / Mentah',
            'bahan_jadi' => 'Bahan Jadi / Komposit',
            'alat_bantu' => 'Alat Bantu & Perlengkapan',
            'finishing' => 'Finishing & Cat',
            'sanitasi' => 'Sanitasi & Plumbing',
            'elektrikal' => 'Elektrikal & ME',
            'lainnya' => 'Lain-lain',
        ];

        $stats = [
            'total_materials' => Material::count(),
            'active_materials' => Material::where('is_active', true)->count(),
            'total_basic' => Material::where('category', 'bahan_dasar')->count(),
            'total_composite' => Material::where('category', 'bahan_jadi')->count(),
            'total_units' => Unit::count(),
        ];

        return view('materials.index', compact(
            'materials',
            'units',
            'categories',
            'stats',
            'search',
            'category',
            'status',
            'activeTab'
        ));
    }

    /**
     * Tambah Material Baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:materials,code',
            'category' => 'required|string|max:50',
            'default_unit_id' => 'required|exists:units,id',
            'standard_price' => 'required|numeric|min:0',
            'specification' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Nama material / item wajib diisi.',
            'code.unique' => 'Kode material ini sudah terdaftar.',
            'category.required' => 'Pilih kategori material.',
            'default_unit_id.required' => 'Pilih satuan standar material.',
            'standard_price.required' => 'Masukkan estimasi harga satuan standar.',
        ]);

        if (empty($validated['code'])) {
            $validated['code'] = $this->generateMaterialCode($validated['category']);
        }

        $validated['is_active'] = $request->has('is_active');

        $material = Material::create($validated);

        return redirect()->route('materials.index', ['tab' => 'materials'])
            ->with('success', "Material \"{$material->name}\" ({$material->code}) berhasil ditambahkan ke katalog master.");
    }

    /**
     * Update Data Material
     */
    public function update(Request $request, Material $material)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:materials,code,' . $material->id,
            'category' => 'required|string|max:50',
            'default_unit_id' => 'required|exists:units,id',
            'standard_price' => 'required|numeric|min:0',
            'specification' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Nama material / item wajib diisi.',
            'code.required' => 'Kode material wajib diisi.',
            'code.unique' => 'Kode material ini telah digunakan item lain.',
            'category.required' => 'Pilih kategori material.',
            'default_unit_id.required' => 'Pilih satuan standar material.',
            'standard_price.required' => 'Masukkan estimasi harga satuan standar.',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $material->update($validated);

        return redirect()->route('materials.index', ['tab' => 'materials'])
            ->with('success', "Data material \"{$material->name}\" berhasil diperbarui.");
    }

    /**
     * Toggle status aktif material
     */
    public function toggleStatus(Material $material)
    {
        $material->is_active = !$material->is_active;
        $material->save();

        $statusStr = $material->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()
            ->with('success', "Status material \"{$material->name}\" berhasil {$statusStr}.");
    }

    /**
     * Hapus Material (dengan pengecekan integritas referensi transaksi)
     */
    public function destroy(Material $material)
    {
        $rabUsage = $material->rabItemMaterials()->count();
        $poUsage = $material->purchaseOrderItems()->count();
        $doUsage = $material->deliveryOrderItems()->count();
        $totalUsage = $rabUsage + $poUsage + $doUsage;

        if ($totalUsage > 0) {
            return redirect()->route('materials.index', ['tab' => 'materials'])
                ->with('error', "Material \"{$material->name}\" tidak dapat dihapus permanen karena telah tercatat dalam riwayat ({$rabUsage} BOM RAB, {$poUsage} PO, {$doUsage} Surat Jalan DO). Anda dapat menonaktifkan statusnya agar tidak muncul pada pilihan input baru.");
        }

        $name = $material->name;
        $material->delete();

        return redirect()->route('materials.index', ['tab' => 'materials'])
            ->with('success', "Material \"{$name}\" berhasil dihapus dari database Master.");
    }

    /**
     * Tambah Satuan (Unit) Baru
     */
    public function storeUnit(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:units,code',
            'name' => 'required|string|max:100',
            'group' => 'required|string|max:50',
            'is_base_unit' => 'nullable|boolean',
        ], [
            'code.required' => 'Kode simbol satuan (misal: Kg, M3, Bh) wajib diisi.',
            'code.unique' => 'Kode satuan ini sudah terdaftar.',
            'name.required' => 'Nama lengkap satuan wajib diisi.',
            'group.required' => 'Pilih kelompok besaran satuan.',
        ]);

        $validated['is_base_unit'] = $request->has('is_base_unit');

        $unit = Unit::create($validated);

        return redirect()->route('materials.index', ['tab' => 'units'])
            ->with('success', "Satuan \"{$unit->name} ({$unit->code})\" berhasil ditambahkan ke master satuan.");
    }

    /**
     * Update Satuan (Unit)
     */
    public function updateUnit(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:units,code,' . $unit->id,
            'name' => 'required|string|max:100',
            'group' => 'required|string|max:50',
            'is_base_unit' => 'nullable|boolean',
        ], [
            'code.required' => 'Kode simbol satuan wajib diisi.',
            'code.unique' => 'Kode satuan ini telah digunakan.',
            'name.required' => 'Nama lengkap satuan wajib diisi.',
        ]);

        $validated['is_base_unit'] = $request->has('is_base_unit');

        $unit->update($validated);

        return redirect()->route('materials.index', ['tab' => 'units'])
            ->with('success', "Satuan \"{$unit->code}\" berhasil diperbarui.");
    }

    /**
     * Hapus Satuan (Unit)
     */
    public function destroyUnit(Unit $unit)
    {
        $materialUsage = $unit->materials()->count();
        $rabUsage = $unit->rabItems()->count() + $unit->rabItemMaterials()->count();

        if ($materialUsage > 0 || $rabUsage > 0) {
            return redirect()->route('materials.index', ['tab' => 'units'])
                ->with('error', "Satuan \"{$unit->code}\" tidak dapat dihapus karena masih digunakan oleh {$materialUsage} material dan {$rabUsage} item pekerjaan.");
        }

        $code = $unit->code;
        $unit->delete();

        return redirect()->route('materials.index', ['tab' => 'units'])
            ->with('success', "Satuan \"{$code}\" berhasil dihapus.");
    }

    /**
     * Helper pembuat kode otomatis material
     */
    private function generateMaterialCode(string $category): string
    {
        $prefix = match ($category) {
            'bahan_dasar' => 'MAT-BSR',
            'bahan_jadi' => 'MAT-JDI',
            'alat_bantu' => 'MAT-ALT',
            'finishing' => 'MAT-FNS',
            'sanitasi' => 'MAT-SNT',
            'elektrikal' => 'MAT-ELK',
            default => 'MAT-GEN',
        };

        $count = Material::where('code', 'like', "{$prefix}-%")->count() + 1;
        $code = sprintf("%s-%03d", $prefix, $count);

        while (Material::where('code', $code)->exists()) {
            $count++;
            $code = sprintf("%s-%03d", $prefix, $count);
        }

        return $code;
    }
}
