<?php

namespace App\Http\Controllers;

use App\Models\DiagramAudit;
use App\Services\AiService;
use App\Services\DrawioXmlService;
use App\Services\GraphLayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AuditorController extends Controller
{
    protected AiService $aiService;
    protected GraphLayoutService $layoutService;
    protected DrawioXmlService $xmlService;

    public function __construct(
        AiService $aiService,
        GraphLayoutService $layoutService,
        DrawioXmlService $xmlService
    ) {
        $this->aiService = $aiService;
        $this->layoutService = $layoutService;
        $this->xmlService = $xmlService;
    }

    public function index()
    {
        $recentAudits = DiagramAudit::latest()->take(5)->get();
        return view('auditor', [
            'recentAudits' => $recentAudits,
            'currentAudit' => null,
            'revisedSvg' => null,
        ]);
    }

    public function audit(Request $request)
    {
        $request->validate([
            'diagram' => 'required|image|mimes:png,jpg,jpeg,webp|max:5120',
            'narrative' => 'required|string|min:15|max:10000',
        ], [
            'diagram.required' => 'Gambar diagram wajib diunggah.',
            'diagram.image' => 'Berkas harus berupa gambar valid.',
            'diagram.max' => 'Ukuran gambar maksimal 5 MB.',
            'narrative.required' => 'Narasi proses acuan wajib diisi.',
            'narrative.min' => 'Narasi proses minimal 15 karakter.',
        ]);

        // Store uploaded image
        $file = $request->file('diagram');
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
        $path = $file->storeAs('uploads/diagrams', $fileName, 'public');
        $fullPath = storage_path('app/public/' . $path);

        $narrative = $request->input('narrative');

        // Execute Dual-channel Audit
        $auditResult = $this->aiService->auditDiagram($fullPath, $narrative);

        if ($auditResult['status'] === 'needs_clarification') {
            return redirect()->route('auditor.index')
                ->withInput()
                ->with('warning', "Audit Perlu Klarifikasi: " . $auditResult['message']);
        }

        // Generate revised diagram layout & XML
        $revisedSvg = null;
        $revisedXml = null;
        if (!empty($auditResult['revised_data'])) {
            $layout = $this->layoutService->layout($auditResult['revised_data']);
            $revisedSvg = $this->layoutService->renderSvg($layout);
            $revisedXml = $this->xmlService->generateXml($layout);
        }

        // Save to MySQL
        $audit = DiagramAudit::create([
            'image_path' => $path,
            'narrative' => $narrative,
            'status' => 'success',
            'findings' => $auditResult['findings'] ?? [],
            'revised_diagram_data' => $auditResult['revised_data'] ?? null,
            'revised_xml_content' => $revisedXml,
        ]);

        return view('auditor', [
            'recentAudits' => DiagramAudit::latest()->take(5)->get(),
            'currentAudit' => $audit,
            'revisedSvg' => $revisedSvg,
        ])->with('info', 'Pemeriksaan logika diagram berhasil diselesaikan.');
    }

    public function show(int $id)
    {
        $audit = DiagramAudit::findOrFail($id);

        $revisedSvg = null;
        if (!empty($audit->revised_diagram_data)) {
            $layout = $this->layoutService->layout($audit->revised_diagram_data);
            $revisedSvg = $this->layoutService->renderSvg($layout);
        }

        return view('auditor', [
            'recentAudits' => DiagramAudit::latest()->take(5)->get(),
            'currentAudit' => $audit,
            'revisedSvg' => $revisedSvg,
        ]);
    }

    public function download(int $id)
    {
        $audit = DiagramAudit::findOrFail($id);

        if (empty($audit->revised_xml_content)) {
            return redirect()->route('auditor.index')->with('error', 'File XML revisi tidak tersedia.');
        }

        $filename = "adapt_audited_revisi_{$audit->id}.drawio";

        return response($audit->revised_xml_content, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
