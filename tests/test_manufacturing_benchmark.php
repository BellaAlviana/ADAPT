<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AiService;
use App\Services\UmlValidatorService;
use App\Services\GraphLayoutService;
use App\Services\DrawioXmlService;

$narrative = "Alur dimulai ketika manajer produksi menekan tombol Mulai Perakitan pada panel kontrol utama di ruang kemudi. Tepat setelah tombol ditekan, sistem otomasi pabrik langsung memicu dua instruksi kerja ke area robotik secara serentak.

Jalur pertama memerintahkan lengan robot mekanis di Lini A untuk mulai mengelas kerangka besi produk. Pada detik yang sama, jalur kedua mengirimkan instruksi ke mesin pencetak di Lini B untuk menyuntikkan cairan plastik ke dalam cetakan komponen elektronik. Kedua aktivitas manufaktur ini berjalan secara terpisah dan independen di areanya masing-masing tanpa perlu saling menunggu.

Setelah lengan robot selesai mengelas kerangka dan mesin pencetak selesai membuat komponen elektronik, kedua hasil produksi tersebut akan berjalan di atas ban berjalan (conveyor belt) menuju titik pertemuan yang sama. Sistem akan memastikan kedua komponen telah tiba dengan lengkap, lalu memicu robot perakit untuk menyatukan kedua bagian tersebut menjadi produk final sebelum proses berakhir.";

$ai = app(AiService::class);
$res = $ai->generateDiagram($narrative);

echo "STATUS: " . ($res['status'] ?? 'unknown') . "\n";
echo "MESSAGE: " . ($res['message'] ?? '') . "\n\n";

if (!empty($res['data'])) {
    echo "PARTITIONS:\n";
    print_r($res['data']['partitions']);
    echo "\nNODES:\n";
    foreach ($res['data']['nodes'] as $n) {
        echo "ID: {$n['id']} | Type: {$n['type']} | Partition: {$n['partition']} | Label: '{$n['label']}'\n";
    }
    echo "\nEDGES:\n";
    foreach ($res['data']['edges'] as $e) {
        $guard = !empty($e['guard']) ? " [guard: {$e['guard']}]" : "";
        echo "{$e['source']} -> {$e['target']}{$guard}\n";
    }

    echo "\n--- VALIDATION & LAYOUT PIPELINE ---\n";
    $validator = app(UmlValidatorService::class);
    $vResult = $validator->validate($res['data']);
    echo "1. UML Validation valid? " . ($vResult['valid'] ? 'YES (PASSED)' : 'NO (FAILED)') . "\n";
    if (!empty($vResult['errors'])) {
        echo "Errors:\n";
        print_r($vResult['errors']);
    }
    if (!empty($vResult['warnings'])) {
        echo "Warnings (" . count($vResult['warnings']) . "):\n";
        print_r($vResult['warnings']);
    }

    $layoutService = app(GraphLayoutService::class);
    $layout = $layoutService->layout($res['data']);
    echo "2. Layout computed: Width={$layout['width']}, Height={$layout['height']}\n";
    echo "   Partitions (" . count($layout['partitions']) . "):\n";
    foreach ($layout['partitions'] as $p) {
        echo "   - {$p['name']}: x={$p['x']}, w={$p['width']}\n";
    }

    $xmlService = app(DrawioXmlService::class);
    $xml = $xmlService->generateXml($layout);
    $isXmlValid = $xmlService->isValidXml($xml);
    echo "3. Draw.io XML valid? " . ($isXmlValid ? 'YES' : 'NO') . " (" . strlen($xml) . " bytes)\n";

    $svg = $layoutService->renderSvg($layout);
    echo "4. SVG Preview generated? " . (!empty($svg) ? 'YES' : 'NO') . " (" . strlen($svg) . " bytes)\n";
    file_put_contents(__DIR__ . '/benchmark_diagram_preview.svg', $svg);
    file_put_contents(__DIR__ . '/benchmark_diagram.drawio', $xml);
    echo "   Files saved to tests/benchmark_diagram_preview.svg and tests/benchmark_diagram.drawio\n";
}
