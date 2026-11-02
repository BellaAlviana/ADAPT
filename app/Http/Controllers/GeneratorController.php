<?php

namespace App\Http\Controllers;

use App\Models\DiagramGeneration;
use App\Services\AiService;
use App\Services\DrawioXmlService;
use App\Services\GraphLayoutService;
use App\Services\UmlValidatorService;
use Illuminate\Http\Request;

class GeneratorController extends Controller
{
    protected AiService $aiService;
    protected UmlValidatorService $validatorService;
    protected GraphLayoutService $layoutService;
    protected DrawioXmlService $xmlService;

    public function __construct(
        AiService $aiService,
        UmlValidatorService $validatorService,
        GraphLayoutService $layoutService,
        DrawioXmlService $xmlService
    ) {
        $this->aiService = $aiService;
        $this->validatorService = $validatorService;
        $this->layoutService = $layoutService;
        $this->xmlService = $xmlService;
    }

    public function index()
    {
        $recentGenerations = DiagramGeneration::latest()->take(5)->get();
        return view('generator', [
            'recentGenerations' => $recentGenerations,
            'currentResult' => null,
            'svgPreview' => null,
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'narrative' => 'required|string|min:20|max:10000',
        ], [
            'narrative.required' => 'Narasi proses wajib diisi.',
            'narrative.min' => 'Narasi proses minimal 20 karakter agar alur dapat dimodelkan.',
            'narrative.max' => 'Narasi proses maksimal 10.000 karakter.',
        ]);

        $narrative = $request->input('narrative');

        // Step 1: AI / Deterministic Processing
        $aiResult = $this->aiService->generateDiagram($narrative);

        if ($aiResult['status'] === 'needs_clarification') {
            $generation = DiagramGeneration::create([
                'narrative' => $narrative,
                'status' => 'needs_clarification',
                'clarification_message' => $aiResult['message'],
            ]);

            return redirect()->route('generator.index')
                ->withInput()
                ->with('warning', "Memerlukan Klarifikasi: " . $aiResult['message']);
        }

        $diagramData = $aiResult['data'];

        // Step 2: Backend UML & Schema Validator
        $validation = $this->validatorService->validate($diagramData);

        // Step 3: Dagre Graph Layout Engine
        $layout = $this->layoutService->layout($diagramData);
        $svgPreview = $this->layoutService->renderSvg($layout);

        // Step 4: Draw.io XML Generator & Validator
        $xmlContent = $this->xmlService->generateXml($layout);
        $isXmlValid = $this->xmlService->isValidXml($xmlContent);

        // Step 5: Save to MySQL database
        $generation = DiagramGeneration::create([
            'narrative' => $narrative,
            'status' => $validation['valid'] && $isXmlValid ? 'success' : 'failed',
            'diagram_data' => $diagramData,
            'xml_content' => $xmlContent,
            'validation_notes' => [
                'is_valid' => $validation['valid'],
                'errors' => $validation['errors'],
                'warnings' => $validation['warnings'],
                'is_xml_valid' => $isXmlValid,
            ],
        ]);

        return view('generator', [
            'recentGenerations' => DiagramGeneration::latest()->take(5)->get(),
            'currentResult' => $generation,
            'svgPreview' => $svgPreview,
            'validation' => $validation,
        ])->with('info', 'Rancangan Activity Diagram berhasil dibuat dan divalidasi!');
    }

    public function show(int $id)
    {
        $generation = DiagramGeneration::findOrFail($id);
        $layout = $this->layoutService->layout($generation->diagram_data ?? []);
        $svgPreview = $this->layoutService->renderSvg($layout);

        return view('generator', [
            'recentGenerations' => DiagramGeneration::latest()->take(5)->get(),
            'currentResult' => $generation,
            'svgPreview' => $svgPreview,
            'validation' => $generation->validation_notes ?? ['valid' => true, 'errors' => [], 'warnings' => []],
        ]);
    }

    public function download(int $id)
    {
        $generation = DiagramGeneration::findOrFail($id);

        if (empty($generation->xml_content)) {
            return redirect()->route('generator.index')->with('error', 'File XML tidak tersedia.');
        }

        $filename = "adapt_activity_diagram_{$generation->id}.drawio";

        return response($generation->xml_content, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
