<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectWorker;
use App\Models\Worker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WorkerController extends Controller
{
    /**
     * Daftar pilihan bidang keahlian tukang & pekerja
     *
     * @var array<int, string>
     */
    public const TRADE_OPTIONS = [
        'Mandor',
        'Tukang Batu',
        'Tukang Besi / Pembesian',
        'Tukang Kayu / Bekisting',
        'Tukang Cat',
        'Tukang Listrik / ME',
        'Tukang Plafon / Gypsum',
        'Tukang Las',
        'Tukang Keramik / Finishing',
        'Pekerja / Kenek / Helper',
    ];

    /**
     * Tampilkan halaman utama manajemen pekerja dan penugasan proyek
     */
    public function index(Request $request): View|RedirectResponse
    {
        $projects = Project::orderBy('name')->get();
        if ($projects->isEmpty()) {
            return redirect()->route('projects.create')->with('info', 'Belum ada proyek aktif. Silakan buat proyek baru terlebih dahulu.');
        }

        $projectId = $request->query('project_id') ?? session('active_project_id');
        $project = Project::find($projectId) ?? $projects->first();
        session(['active_project_id' => $project->id]);

        $activeTab = $request->query('tab', 'project');
        $search = trim((string) $request->query('search', ''));
        $tradeFilter = $request->query('trade', 'all');
        $statusFilter = $request->query('status', '');

        // 1. Master Worker Query (Katalog Global)
        $masterQuery = Worker::withCount('projectWorkers');

        if (! empty($search)) {
            $masterQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('trade', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if (! empty($tradeFilter) && $tradeFilter !== 'all') {
            $masterQuery->where('trade', $tradeFilter);
        }

        if ($statusFilter === 'active') {
            $masterQuery->where('is_active', true);
        } elseif ($statusFilter === 'inactive') {
            $masterQuery->where('is_active', false);
        }

        $masterWorkers = $masterQuery->orderBy('trade')->orderBy('name')->paginate(20)->withQueryString();

        // 2. Pekerja di Proyek Terkait (Project Workers)
        $projectWorkers = ProjectWorker::with(['worker', 'user'])
            ->where('project_id', $project->id)
            ->orderByRaw("CASE WHEN status = 'active' THEN 1 WHEN status = 'standby' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->get();

        // 3. Katalog Lengkap Pekerja Aktif untuk Fast Assignment
        $assignedWorkerIds = $projectWorkers->pluck('worker_id')->toArray();
        $catalog = Worker::where('is_active', true)->orderBy('trade')->orderBy('name')->get();

        // 4. Statistik KPI Lapangan
        $activeProjectWorkers = $projectWorkers->where('status', 'active');
        $stats = [
            'total_master' => Worker::count(),
            'active_in_project' => $activeProjectWorkers->count(),
            'standby_in_project' => $projectWorkers->where('status', 'standby')->count(),
            'mandor_tukang_count' => $activeProjectWorkers->filter(function ($pw) {
                $tradeLower = strtolower($pw->effective_trade);

                return str_contains($tradeLower, 'mandor') || str_contains($tradeLower, 'tukang');
            })->count(),
            'daily_payroll_estimate' => (float) $activeProjectWorkers->sum('daily_wage'),
        ];

        $tradeOptions = self::TRADE_OPTIONS;

        return view('workers.index', compact(
            'project',
            'projects',
            'activeTab',
            'search',
            'tradeFilter',
            'statusFilter',
            'masterWorkers',
            'projectWorkers',
            'catalog',
            'assignedWorkerIds',
            'stats',
            'tradeOptions'
        ));
    }

    /**
     * Simpan pekerja / tukang baru ke katalog master
     */
    public function storeMaster(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'trade' => 'required|string|max:100',
            'code' => 'nullable|string|max:50|unique:workers,code',
            'phone' => 'nullable|string|max:50',
            'nik' => 'nullable|string|max:50',
            'daily_rate' => 'required|numeric|min:0',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'nullable',
        ]);

        // Auto generate code jika kosong
        if (empty($validated['code'])) {
            $tradeLower = strtolower($validated['trade']);
            $prefix = 'WKR-TKG-';
            if (str_contains($tradeLower, 'mandor')) {
                $prefix = 'WKR-MDR-';
            } elseif (str_contains($tradeLower, 'kenek') || str_contains($tradeLower, 'pekerja') || str_contains($tradeLower, 'helper')) {
                $prefix = 'WKR-KNK-';
            }

            $existingCodes = Worker::where('code', 'like', "{$prefix}%")->pluck('code')->toArray();
            $maxNum = 0;
            foreach ($existingCodes as $code) {
                if (preg_match('/(\d+)$/', (string) $code, $m)) {
                    $num = intval($m[1]);
                    if ($num > $maxNum) {
                        $maxNum = $num;
                    }
                }
            }
            $validated['code'] = $prefix.str_pad((string) ($maxNum + 1), 3, '0', STR_PAD_LEFT);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $worker = Worker::create($validated);

        return redirect()->route('workers.index', ['tab' => 'master'])
            ->with('success', "Pekerja '{$worker->name}' ({$worker->code} - {$worker->trade}) berhasil ditambahkan ke katalog master!");
    }

    /**
     * Perbarui data pekerja di katalog master
     */
    public function updateMaster(Request $request, Worker $worker): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'trade' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:workers,code,'.$worker->id,
            'phone' => 'nullable|string|max:50',
            'nik' => 'nullable|string|max:50',
            'daily_rate' => 'required|numeric|min:0',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'nullable',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $worker->update($validated);

        return redirect()->route('workers.index', ['tab' => 'master'])
            ->with('success', "Data master pekerja '{$worker->name}' berhasil diperbarui!");
    }

    /**
     * Hapus data pekerja dari katalog master (dengan pengecekan relasi proyek)
     */
    public function destroyMaster(Worker $worker): RedirectResponse
    {
        $allocatedCount = $worker->projectWorkers()->count();
        if ($allocatedCount > 0) {
            return redirect()->route('workers.index', ['tab' => 'master'])
                ->with('error', "Pekerja '{$worker->name}' tidak dapat dihapus karena sedang ditugaskan di {$allocatedCount} proyek! Anda dapat menonaktifkan statusnya.");
        }

        $name = $worker->name;
        $worker->delete();

        return redirect()->route('workers.index', ['tab' => 'master'])
            ->with('success', "Pekerja '{$name}' berhasil dihapus dari katalog master.");
    }

    /**
     * Tugaskan satu pekerja secara spesifik ke proyek (bisa input nama langsung atau dari katalog)
     */
    public function assignSingle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'worker_id' => 'nullable|exists:workers,id',
            'name' => 'required_without:worker_id|nullable|string|max:255',
            'assigned_trade' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'nik' => 'nullable|string|max:50',
            'daily_wage' => 'required|numeric|min:0',
            'status' => 'required|in:active,standby,completed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:500',
        ]);

        if (! empty($validated['worker_id'])) {
            $worker = Worker::findOrFail($validated['worker_id']);
            if (! empty($validated['name'])) {
                $worker->update(['name' => $validated['name']]);
            }
        } else {
            $name = trim((string) $validated['name']);
            $trade = $validated['assigned_trade'] ?? 'Pekerja';

            // Cari jika pekerja sudah ada dengan nama yang sama, atau buatkan master baru
            $worker = Worker::where('name', $name)->first();
            if (! $worker) {
                // Auto generate kode pekerja master
                $tradeLower = strtolower($trade);
                $prefix = 'WKR-TKG-';
                if (str_contains($tradeLower, 'mandor')) {
                    $prefix = 'WKR-MDR-';
                } elseif (str_contains($tradeLower, 'kenek') || str_contains($tradeLower, 'pekerja') || str_contains($tradeLower, 'helper')) {
                    $prefix = 'WKR-KNK-';
                }

                $existingCodes = Worker::where('code', 'like', "{$prefix}%")->pluck('code')->toArray();
                $maxNum = 0;
                foreach ($existingCodes as $code) {
                    if (preg_match('/(\d+)$/', (string) $code, $m)) {
                        $num = intval($m[1]);
                        if ($num > $maxNum) {
                            $maxNum = $num;
                        }
                    }
                }
                $code = $prefix.str_pad((string) ($maxNum + 1), 3, '0', STR_PAD_LEFT);

                $worker = Worker::create([
                    'code' => $code,
                    'name' => $name,
                    'trade' => $trade,
                    'daily_rate' => $validated['daily_wage'],
                    'phone' => $validated['phone'] ?? null,
                    'nik' => $validated['nik'] ?? null,
                    'is_active' => true,
                ]);
            } else {
                if (! empty($validated['phone']) && empty($worker->phone)) {
                    $worker->update(['phone' => $validated['phone']]);
                }
            }
        }

        $assignedTrade = ! empty($validated['assigned_trade']) ? $validated['assigned_trade'] : ($worker->trade ?? 'Pekerja');

        ProjectWorker::updateOrCreate(
            [
                'project_id' => $validated['project_id'],
                'worker_id' => $worker->id,
            ],
            [
                'assigned_trade' => $assignedTrade,
                'daily_wage' => $validated['daily_wage'],
                'status' => $validated['status'],
                'start_date' => $validated['start_date'] ?? now()->toDateString(),
                'end_date' => $validated['end_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'added_by' => Auth::id(),
            ]
        );

        return redirect()->route('workers.index', ['project_id' => $validated['project_id'], 'tab' => 'project'])
            ->with('success', "Pekerja '{$worker->name}' berhasil ditambahkan ke proyek!");
    }

    /**
     * Alokasi cepat banyak pekerja sekaligus (Fast Bulk Assign)
     */
    public function fastBulkAssign(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'items' => 'required|array|min:1',
            'items.*.selected' => 'nullable|boolean',
            'items.*.worker_id' => 'required|exists:workers,id',
            'items.*.assigned_trade' => 'nullable|string|max:100',
            'items.*.daily_wage' => 'nullable|numeric|min:0',
            'items.*.status' => 'required|in:active,standby,completed',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        $savedCount = 0;
        foreach ($validated['items'] as $itemData) {
            if (! empty($itemData['selected'])) {
                $worker = Worker::find($itemData['worker_id']);
                $dailyWage = (isset($itemData['daily_wage']) && $itemData['daily_wage'] !== '')
                    ? (float) $itemData['daily_wage']
                    : (float) ($worker?->daily_rate ?? 0);

                $assignedTrade = ! empty($itemData['assigned_trade'])
                    ? $itemData['assigned_trade']
                    : ($worker?->trade ?? 'Pekerja');

                ProjectWorker::updateOrCreate(
                    [
                        'project_id' => $validated['project_id'],
                        'worker_id' => $itemData['worker_id'],
                    ],
                    [
                        'assigned_trade' => $assignedTrade,
                        'daily_wage' => $dailyWage,
                        'status' => $itemData['status'] ?? 'active',
                        'notes' => $itemData['notes'] ?? null,
                        'start_date' => now()->toDateString(),
                        'added_by' => Auth::id(),
                    ]
                );
                $savedCount++;
            }
        }

        if ($savedCount === 0) {
            return redirect()->back()->with('warning', 'Tidak ada pekerja yang dicentang untuk dialokasikan.');
        }

        return redirect()->route('workers.index', ['project_id' => $validated['project_id'], 'tab' => 'project'])
            ->with('success', "{$savedCount} pekerja / tukang berhasil dialokasikan ke proyek!");
    }

    /**
     * Perbarui data penugasan pekerja di proyek (status, upah, zona/catatan)
     */
    public function updateProjectWorker(Request $request, ProjectWorker $projectWorker): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_trade' => 'nullable|string|max:100',
            'daily_wage' => 'required|numeric|min:0',
            'status' => 'required|in:active,standby,completed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:500',
        ]);

        $projectWorker->update($validated);

        return redirect()->route('workers.index', ['project_id' => $projectWorker->project_id, 'tab' => 'project'])
            ->with('success', "Penugasan pekerja '{$projectWorker->worker->name}' di proyek berhasil diperbarui!");
    }

    /**
     * Hapus alokasi pekerja dari proyek
     */
    public function destroyProjectWorker(ProjectWorker $projectWorker): RedirectResponse
    {
        $projectId = $projectWorker->project_id;
        $name = $projectWorker->worker->name;
        $projectWorker->delete();

        return redirect()->route('workers.index', ['project_id' => $projectId, 'tab' => 'project'])
            ->with('success', "Pekerja '{$name}' telah dikeluarkan dari penugasan proyek ini.");
    }
}
