<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AiService;
use App\Services\UmlValidatorService;
use App\Services\GraphLayoutService;
use App\Services\DrawioXmlService;

echo "============================================================\n";
echo "   PENGUJIAN VALIDASI UMUM (5 DOMAIN + EDGE CASES) ADAPT\n";
echo "============================================================\n\n";

$aiService = app(AiService::class);
$validator = app(UmlValidatorService::class);
$layoutService = app(GraphLayoutService::class);
$xmlService = app(DrawioXmlService::class);

$domains = [
    'Domain 1: Akademik (Sidang Skripsi & Yudisium)' => [
        'narrative' => 'Mahasiswa menyerahkan naskah skripsi ke Bagian Akademik. Formulir pendaftaran diverifikasi oleh Staf Akademik. Jika berkas lengkap, Dosen Pembimbing menyetujui jadwal sidang. Jika berkas tidak lengkap, Mahasiswa melengkapi kekurangan berkas. Selanjutnya, Dosen Penguji menguji mahasiswa. Setelah sidang selesai, Dosen Penguji memberikan nilai akhir. Mahasiswa merevisi naskah skripsi berdasarkan catatan penguji. Terakhir, Bagian Akademik mencetak sertifikat kelulusan.',
        'expected_actors' => ['Mahasiswa', 'Staf Akademik', 'Dosen Pembimbing', 'Dosen Penguji', 'Bagian Akademik'],
    ],
    'Domain 2: Rumah Sakit (Pelayanan Rawat Inap & Instalasi Farmasi)' => [
        'narrative' => 'Pasien mendaftar di Loket Pendaftaran. Petugas Loket mencatat data rekam medis pasien. Dokter Umum memeriksa kondisi fisik pasien. Jika memerlukan rawat inap, Dokter Umum membuat surat rujukan rawat inap dan Perawat menyiapkan kamar rawat. Jika tidak memerlukan rawat inap, Dokter Umum meresepkan obat jalan. Kemudian, Apoteker meracik obat di instalasi farmasi. Pasien menerima obat dan Kasir menerima pembayaran tagihan pengobatan.',
        'expected_actors' => ['Pasien', 'Petugas Loket', 'Dokter Umum', 'Perawat', 'Apoteker', 'Kasir'],
    ],
    'Domain 3: Perbankan (Pengajuan & Analisis Kredit Usaha)' => [
        'narrative' => 'Nasabah mengajukan permohonan kredit kepada Customer Service. Berkas persyaratan diperiksa oleh Analis Kredit. Jika skor kredit memenuhi syarat, Kepala Cabang menandatangani surat perjanjian kredit. Jika skor kredit tidak memenuhi syarat, Customer Service mengembalikan berkas kepada nasabah. Setelah persetujuan ditandatangani, Teller mencairkan dana kredit ke rekening nasabah. Nasabah menerima buku tabungan dan bukti pencairan.',
        'expected_actors' => ['Nasabah', 'Customer Service', 'Analis Kredit', 'Kepala Cabang', 'Teller'],
    ],
    'Domain 4: Pergudangan & Inventaris (Bongkar Muat & Concurrency Fork/Join)' => [
        'narrative' => 'Vendor mengirimkan barang ke area bongkar muat gudang. Petugas Gudang memeriksa surat jalan dan kondisi fisik barang. Jika barang sesuai pesanan, secara paralel Petugas Gudang mencatat data barang ke sistem inventaris dan Kepala Gudang menandatangani berita acara serah terima. Setelah kedua aktivitas tersebut selesai dilakukan, Sistem Gudang memperbarui total stok inventaris dan Petugas Gudang menempatkan barang ke rak penyimpanan.',
        'expected_actors' => ['Vendor', 'Petugas Gudang', 'Kepala Gudang', 'Sistem Gudang'],
    ],
    'Domain 5: Pelayanan Publik (Pencatatan Sipil / E-KTP)' => [
        'narrative' => 'Warga menyerahkan berkas permohonan cetak KTP ke Petugas Loket. Berkas kependudukan diverifikasi oleh Staf Verifikator. Jika data kependudukan valid, Operator mencetak blangko KTP elektronik dan Kepala Dinas menandatangani dokumen pengesahan kependudukan. Jika data kependudukan tidak valid, Petugas Loket menolak berkas permohonan. Akhirnya, Warga mengambil fisik KTP di loket pengambilan.',
        'expected_actors' => ['Warga', 'Petugas Loket', 'Staf Verifikator', 'Operator', 'Kepala Dinas'],
    ],
    'Edge Case 1: Struktur Kalimat Pasif Terbalik (Inversi Semantik KBBI)' => [
        'narrative' => 'Formulir pendaftaran diisi oleh Mahasiswa. Dokumen persyaratan diperiksa oleh Panitia Ujian. Jika berkas diterima, kartu ujian dicetak oleh Petugas Registrasi. Mahasiswa mengikuti ujian tertulis. Lembar jawaban dinilai oleh Tim Penguji.',
        'expected_actors' => ['Mahasiswa', 'Panitia Ujian', 'Petugas Registrasi', 'Tim Penguji'],
    ],
    'Edge Case 2: Ambiguitas & Narasi Terlalu Singkat' => [
        'narrative' => 'Sistem berjalan.',
        'expected_ambiguous' => true,
    ]
];

$allPassed = true;

foreach ($domains as $domainName => $config) {
    echo "============================================================\n";
    echo "PENGUJIAN: $domainName\n";
    echo "============================================================\n";
    
    $result = $aiService->generateDiagram($config['narrative']);
    
    if (!empty($config['expected_ambiguous'])) {
        if ($result['status'] === 'needs_clarification') {
            echo "[SUCCESS] Sistem dengan tepat mendeteksi narasi belum memadai / memerlukan klarifikasi:\n";
            echo "   Pesan: " . $result['message'] . "\n\n";
            continue;
        } else {
            echo "[FAILED] Sistem seharusnya menolak narasi ambigu / terlalu pendek!\n\n";
            $allPassed = false;
            continue;
        }
    }
    
    if ($result['status'] !== 'success' || empty($result['data'])) {
        echo "[FAILED] Eksekusi generate diagram gagal: " . ($result['message'] ?? 'Unknown error') . "\n\n";
        $allPassed = false;
        continue;
    }
    
    $diagramJson = $result['data'];
    $validation = $validator->validate($diagramJson);
    
    echo "1. Validasi Notasi UML & Kaidah Tata Bahasa:\n";
    echo "   - Status: " . ($validation['valid'] ? 'VALID (PASSED)' : 'TIDAK VALID (FAILED)') . "\n";
    if (!empty($validation['errors'])) {
        echo "   - Errors:\n";
        foreach ($validation['errors'] as $err) {
            echo "     * $err\n";
        }
        $allPassed = false;
    }
    if (!empty($validation['warnings'])) {
        echo "   - Warnings (" . count($validation['warnings']) . "):\n";
        foreach ($validation['warnings'] as $warn) {
            echo "     * $warn\n";
        }
    }
    
    echo "\n2. Deteksi Partisi Swimlane (" . count($diagramJson['partitions'] ?? []) . " Aktor):\n";
    foreach ($diagramJson['partitions'] ?? [] as $part) {
        $partName = is_array($part) ? ($part['name'] ?? 'Unknown') : $part;
        echo "   - $partName\n";
    }
    
    echo "\n3. Action Nodes & Notasi UML (" . count($diagramJson['nodes'] ?? []) . " Nodes):\n";
    $actions = 0;
    $decisions = 0;
    $nonActiveVerbs = [];
    $nonEmptyDiamonds = [];
    
    foreach ($diagramJson['nodes'] ?? [] as $node) {
        $type = $node['type'] ?? 'activity';
        $label = $node['label'] ?? '';
        $part = $node['partition'] ?? '-';
        
        if ($type === 'activity' || $type === 'action') {
            $actions++;
            echo "   [Action]   [Aktor: $part] \"$label\"\n";
            
            // Verifikasi kata kerja aktif KBBI
            $firstWord = strtolower(explode(' ', trim($label))[0] ?? '');
            if (preg_match('/^(di|ter)[a-z]/i', $firstWord)) {
                $nonActiveVerbs[] = $label;
            }
        } elseif ($type === 'decision' || $type === 'merge') {
            $decisions++;
            echo "   [$type] [Aktor: $part] Label: \"" . ($label ?: '(Kosong - Sesuai OMG UML)') . "\"\n";
            if (trim($label) !== '') {
                $nonEmptyDiamonds[] = "{$node['id']}: '$label'";
            }
        } elseif (in_array($type, ['fork', 'join', 'initial', 'final'])) {
            echo "   [$type]    [Aktor: $part] Label: \"" . ($label ?: '-') . "\"\n";
        }
    }
    
    echo "\n4. Edge & Guard Percabangan (" . count($diagramJson['edges'] ?? []) . " Edges):\n";
    foreach ($diagramJson['edges'] ?? [] as $edge) {
        $guard = !empty($edge['guard']) ? " [guard: \"{$edge['guard']}\"]" : "";
        echo "   " . $edge['source'] . " --> " . $edge['target'] . $guard . "\n";
    }
    
    echo "\n5. Layout & Draw.io XML Pipeline:\n";
    $layout = $layoutService->layout($diagramJson);
    $xmlContent = $xmlService->generateXml($layout);
    $isXmlValid = $xmlService->isValidXml($xmlContent);
    
    if ($isXmlValid && strlen($xmlContent) > 200) {
        echo "   [PASS] Graph Layout (Dagre) & Draw.io XML berhasil dibuat (" . strlen($xmlContent) . " bytes) dan valid mxGraphModel.\n";
    } else {
        echo "   [FAIL] Serialisasi XML Draw.io gagal atau tidak valid!\n";
        $allPassed = false;
    }
    
    // Audit Hasil Akhir Domain
    if (empty($nonActiveVerbs) && empty($nonEmptyDiamonds) && $validation['valid']) {
        echo "   ==> HASIL DOMAIN: SUKSES SEMPURNA (100% Sesuai Standar OMG UML, Modul ITK & KBBI)\n";
    } else {
        echo "   ==> HASIL DOMAIN: ADA TEMUAN YANG PERLU DIPERBAIKI\n";
        $allPassed = false;
    }
    echo "\n";
}

if ($allPassed) {
    echo "============================================================\n";
    echo "   SEMUA 5 DOMAIN + 2 EDGE CASES LULUS PENGUJIAN (100% PASSED)\n";
    echo "============================================================\n";
    exit(0);
} else {
    echo "============================================================\n";
    echo "   TERDAPAT PENGUJIAN YANG GAGAL\n";
    echo "============================================================\n";
    exit(1);
}
