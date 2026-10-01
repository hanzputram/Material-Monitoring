<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Project;
use Illuminate\Http\Request;

class AlertController extends Controller
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

        $query = Alert::where('project_id', $project->id);

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $alerts = $query->orderByDesc('created_at')->paginate(20);

        $counts = [
            'total' => Alert::where('project_id', $project->id)->count(),
            'unread' => Alert::where('project_id', $project->id)->where('status', 'unread')->count(),
            'critical' => Alert::where('project_id', $project->id)->where('severity', 'critical')->count(),
            'warning' => Alert::where('project_id', $project->id)->where('severity', 'warning')->count(),
        ];

        return view('alerts.index', compact('project', 'projects', 'alerts', 'counts'));
    }

    public function markAsRead(Alert $alert)
    {
        $alert->update(['status' => 'read']);

        return redirect()->back()->with('success', 'Alert ditandai sudah dibaca.');
    }

    public function markAsResolved(Alert $alert)
    {
        $alert->update(['status' => 'resolved']);

        return redirect()->back()->with('success', 'Alert ditandai telah diselesaikan.');
    }
}
