<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\MaterialRealization;
use App\Models\MaterialVarianceValidation;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VarianceValidationController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::orderBy('name')->get();
        if ($projects->isEmpty()) {
            return redirect()->route('projects.create')->with('info', 'Belum ada proyek aktif. Silakan buat proyek baru terlebih dahulu.');
        }

        $projectId = $request->query('project_id') ?? session('active_project_id');
        $project = Project::find($projectId) ?? $projects->first();
        session(['active_project_id' => $project->id]);

        $realizations = MaterialRealization::with(['material.defaultUnit', 'varianceValidations.pengawas', 'varianceValidations.purchasing', 'varianceValidations.deliveryOrder', 'varianceValidations.invoice'])
            ->where('project_id', $project->id)
            ->get();

        $validations = MaterialVarianceValidation::whereHas('realization', function ($q) use ($project) {
            $q->where('project_id', $project->id);
        })
            ->with(['realization.material.defaultUnit', 'deliveryOrder', 'invoice', 'pengawas', 'purchasing'])
            ->orderByDesc('created_at')
            ->get();

        $availableDos = DeliveryOrder::with('supplier')->where('project_id', $project->id)->orderByDesc('do_date')->get();
        $availableInvoices = Invoice::with('supplier')->where('project_id', $project->id)->orderByDesc('invoice_date')->get();

        return view('variance.index', compact(
            'project',
            'projects',
            'realizations',
            'validations',
            'availableDos',
            'availableInvoices'
        ));
    }

    public function createValidation(Request $request)
    {
        $request->validate([
            'material_realization_id' => 'required|exists:material_realizations,id',
        ]);

        $validation = MaterialVarianceValidation::firstOrCreate(
            ['material_realization_id' => $request->material_realization_id],
            ['status' => 'pending']
        );

        return redirect()->back()->with('success', 'Form validasi variance ganda telah disiapkan.');
    }

    public function validatePengawas(Request $request, MaterialVarianceValidation $validation)
    {
        $request->validate([
            'delivery_order_id' => 'required|exists:delivery_orders,id',
            'notes' => 'nullable|string',
        ]);

        // DO yang dipakai validasi harus milik proyek yang sama dengan realisasi material
        $projectId = $validation->realization?->project_id;
        if (! DeliveryOrder::whereKey($request->delivery_order_id)->where('project_id', $projectId)->exists()) {
            return redirect()->back()->with('error', 'Surat Jalan (DO) yang dipilih bukan milik proyek realisasi material ini.');
        }

        $validation->validateByPengawas(
            (int) Auth::id(),
            $request->notes,
            (int) $request->delivery_order_id
        );

        return redirect()->back()->with('success', 'Validasi Pengawas Lapangan (DO fisik) berhasil disimpan!');
    }

    public function validatePurchasing(Request $request, MaterialVarianceValidation $validation)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'notes' => 'nullable|string',
        ]);

        // Faktur yang dipakai validasi harus milik proyek yang sama dengan realisasi material
        $projectId = $validation->realization?->project_id;
        if (! Invoice::whereKey($request->invoice_id)->where('project_id', $projectId)->exists()) {
            return redirect()->back()->with('error', 'Faktur (Invoice) yang dipilih bukan milik proyek realisasi material ini.');
        }

        $validation->validateByPurchasing(
            (int) Auth::id(),
            $request->notes,
            (int) $request->invoice_id
        );

        return redirect()->back()->with('success', 'Validasi Purchasing (Faktur & Invoice) berhasil disimpan!');
    }
}
