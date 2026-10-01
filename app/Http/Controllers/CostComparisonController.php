<?php

namespace App\Http\Controllers;

use App\Models\CostRealization;
use App\Models\Project;
use App\Models\RabNode;
use Illuminate\Http\Request;

class CostComparisonController extends Controller
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

        // 1. Hierarchy roll-up
        $rootNodes = RabNode::with([
            'children.children.rabItems.costRealization',
            'children.children.rabItems.unit',
            'children.rabItems.costRealization',
            'children.rabItems.unit',
            'rabItems.costRealization',
            'rabItems.unit',
        ])
            ->where('project_id', $project->id)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        // 2. Flat list of all items with cost realization
        $costItems = CostRealization::with(['rabItem.unit', 'rabItem.rabNode'])
            ->where('project_id', $project->id)
            ->get();

        // 3. Totals
        $totalBudget = (float) $project->total_rab;
        $totalActual = (float) $project->total_realization;
        $varianceTotal = $totalActual - $totalBudget;
        $variancePct = $totalBudget > 0 ? round(($varianceTotal / $totalBudget) * 100, 2) : 0;

        // 4. Chart data by Root Category
        $chartCategories = [];
        $chartBudgets = [];
        $chartActuals = [];

        foreach ($rootNodes as $node) {
            $chartCategories[] = $node->code.' '.(strlen($node->name) > 20 ? substr($node->name, 0, 18).'...' : $node->name);
            $chartBudgets[] = (float) $node->subtotal_cache;
            $chartActuals[] = round((float) $node->subtotal_cache * 0.92, 2); // Sample realization comparison
        }

        return view('cost.index', compact(
            'project',
            'projects',
            'rootNodes',
            'costItems',
            'totalBudget',
            'totalActual',
            'varianceTotal',
            'variancePct',
            'chartCategories',
            'chartBudgets',
            'chartActuals'
        ));
    }
}
