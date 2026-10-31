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

        $alerts = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        // Satu query agregat untuk seluruh angka ringkasan
        $summary = Alert::where('project_id', $project->id)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'unread' THEN 1 ELSE 0 END) as unread")
            ->selectRaw("SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical")
            ->selectRaw("SUM(CASE WHEN severity = 'warning' THEN 1 ELSE 0 END) as warning")
            ->toBase()
            ->first();

        $counts = [
            'total' => (int) ($summary->total ?? 0),
            'unread' => (int) ($summary->unread ?? 0),
            'critical' => (int) ($summary->critical ?? 0),
            'warning' => (int) ($summary->warning ?? 0),
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
