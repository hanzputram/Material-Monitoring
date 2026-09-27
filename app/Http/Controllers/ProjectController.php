<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::withCount(['rabNodes', 'materialRealizations', 'deliveryOrders'])
            ->orderByDesc('created_at')
            ->get();

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        return view('projects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prototype_type' => 'nullable|string|max:255',
            'floor_count' => 'required|integer|min:1',
            'budget_year' => 'required|string|max:10',
            'location_kds' => 'nullable|string|max:255',
            'foundation_type' => 'nullable|string|max:255',
            'alert_over_threshold_pct' => 'required|numeric|min:0|max:100',
            'alert_under_threshold_pct' => 'required|numeric|min:0|max:100',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['status'] = 'active';

        $project = Project::create($validated);
        session(['active_project_id' => $project->id]);

        return redirect()->route('rab.builder', ['project_id' => $project->id])
            ->with('success', "Proyek '{$project->name}' berhasil dibuat! Silakan susun RAB atau impor file Excel.");
    }

    public function edit(Project $project)
    {
        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prototype_type' => 'nullable|string|max:255',
            'floor_count' => 'required|integer|min:1',
            'budget_year' => 'required|string|max:10',
            'location_kds' => 'nullable|string|max:255',
            'foundation_type' => 'nullable|string|max:255',
            'alert_over_threshold_pct' => 'required|numeric|min:0|max:100',
            'alert_under_threshold_pct' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:draft,active,closed',
        ]);

        $project->update($validated);

        return redirect()->route('dashboard', ['project_id' => $project->id])
            ->with('success', "Data proyek '{$project->name}' berhasil diperbarui!");
    }

    public function destroy(Project $project)
    {
        $name = $project->name;
        $project->delete();

        // Reset session if deleted project was active
        if (session('active_project_id') == $project->id) {
            $first = Project::first();
            session(['active_project_id' => $first?->id]);
        }

        return redirect()->route('dashboard')
            ->with('success', "Proyek '{$name}' berhasil dihapus.");
    }
}
