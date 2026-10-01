<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\RabExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RabImportExportController extends Controller
{
    public function __construct(protected RabExcelService $excelService) {}

    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = $this->excelService->generateTemplate();
        $fileName = 'Template_RAB_Konstruksi_Baku.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function showImportForm(Request $request)
    {
        $projectId = $request->query('project_id') ?? session('active_project_id');
        $project = $projectId ? Project::find($projectId) : null;
        $projects = Project::orderBy('name')->get();

        return view('rab.import', compact('project', 'projects'));
    }

    public function previewImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:20480', // max 20MB
            'target_project_id' => 'nullable|exists:projects,id',
        ]);

        $file = $request->file('file');
        $preview = $this->excelService->validateAndPreview($file);

        // Store preview in session for commit step
        session(['rab_import_preview' => $preview]);
        session(['rab_import_target_project_id' => $request->target_project_id]);

        $targetProject = $request->target_project_id ? Project::find($request->target_project_id) : null;

        return view('rab.import-preview', compact('preview', 'targetProject'));
    }

    public function commitImport(Request $request)
    {
        $preview = session('rab_import_preview');
        $targetProjectId = session('rab_import_target_project_id');

        if (! $preview) {
            return redirect()->route('rab.import.form')
                ->with('error', 'Sesi import telah kedaluwarsa. Silakan unggah kembali file Excel.');
        }

        if (! $preview['valid']) {
            return redirect()->route('rab.import.form')
                ->with('error', 'Terdapat error pada file Excel. Harap perbaiki sebelum mengimpor.');
        }

        $project = $this->excelService->import($preview, $targetProjectId, Auth::id());

        // Clear session
        session()->forget(['rab_import_preview', 'rab_import_target_project_id']);
        session(['active_project_id' => $project->id]);

        return redirect()->route('rab.builder', ['project_id' => $project->id])
            ->with('success', "Import RAB untuk proyek '{$project->name}' berhasil dilakukan!");
    }

    public function export(Project $project): StreamedResponse
    {
        $spreadsheet = $this->excelService->export($project);
        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $project->name);
        $fileName = "RAB_{$safeName}_".date('Ymd_His').'.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
