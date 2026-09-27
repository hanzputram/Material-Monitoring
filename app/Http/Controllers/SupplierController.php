<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = Supplier::withCount(['purchaseOrders', 'deliveryOrders', 'invoices'])
            ->withSum('invoices', 'amount');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderBy('name')->paginate(12)->withQueryString();

        $stats = [
            'total_suppliers' => Supplier::count(),
            'active_suppliers' => Supplier::has('deliveryOrders')->orHas('purchaseOrders')->count(),
            'total_dos' => DeliveryOrder::count(),
            'total_invoices_amount' => Invoice::sum('amount'),
        ];

        return view('suppliers.index', compact('suppliers', 'stats', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:suppliers,name',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Nama supplier/rekanan wajib diisi.',
            'name.unique' => 'Nama supplier ini sudah terdaftar.',
        ]);

        $supplier = Supplier::create($validated);

        return redirect()->route('suppliers.index')
            ->with('success', "Supplier \"{$supplier->name}\" berhasil ditambahkan ke database Master.");
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:suppliers,name,' . $supplier->id,
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Nama supplier/rekanan wajib diisi.',
            'name.unique' => 'Nama supplier ini sudah digunakan oleh supplier lain.',
        ]);

        $supplier->update($validated);

        return redirect()->route('suppliers.index')
            ->with('success', "Data supplier \"{$supplier->name}\" berhasil diperbarui.");
    }

    public function destroy(Supplier $supplier)
    {
        $linkedDos = $supplier->deliveryOrders()->count();
        $linkedPos = $supplier->purchaseOrders()->count();
        $linkedInvoices = $supplier->invoices()->count();

        if ($linkedDos > 0 || $linkedPos > 0 || $linkedInvoices > 0) {
            return redirect()->route('suppliers.index')
                ->with('error', "Supplier \"{$supplier->name}\" tidak dapat dihapus karena memiliki riwayat transaksi aktif ({$linkedDos} DO, {$linkedPos} PO, {$linkedInvoices} Invoice).");
        }

        $name = $supplier->name;
        $supplier->delete();

        return redirect()->route('suppliers.index')
            ->with('success', "Supplier \"{$name}\" berhasil dihapus.");
    }
}
