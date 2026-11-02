<?php

namespace Tests\Feature;

use App\Models\DiagramGeneration;
use App\Models\DiagramAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdaptTest extends TestCase
{
    public function test_home_page_loads_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Adapt');
        $response->assertSee('Activity Diagram Analysis Processing Tools');
        $response->assertSee('Pilih Mode');
    }

    public function test_generator_index_loads_successfully(): void
    {
        $response = $this->get('/generator');
        $response->assertStatus(200);
        $response->assertSee('Narasi Proses Bisnis');
    }

    public function test_generator_creates_diagram_with_kbbi_active_verbs(): void
    {
        $narrative = "Pelanggan memilih menu dan melakukan pembayaran. Sistem memeriksa hasil pembayaran. Jika pembayaran berhasil, sistem meneruskan pesanan kepada koki untuk dimasak. Jika pembayaran gagal, sistem membatalkan pesanan.";

        $response = $this->post('/generator', [
            'narrative' => $narrative,
        ]);

        $response->assertStatus(200);
        $response->assertSee('Hasil Perancangan Activity Diagram');
        $response->assertSee('Unduh Berkas Draw.io (.drawio)');

        $this->assertDatabaseHas('diagram_generations', [
            'status' => 'success',
        ]);

        $latest = DiagramGeneration::latest('id')->first();
        $this->assertNotNull($latest);
        $this->assertNotEmpty($latest->xml_content);
        $this->assertStringContainsString('mxfile', $latest->xml_content);

        // Verify KBBI Active Verbs
        $this->assertStringContainsString('Memilih Menu', $latest->xml_content);
        $this->assertStringContainsString('Memverifikasi Pembayaran', $latest->xml_content);
        $this->assertStringContainsString('Memasak Pesanan', $latest->xml_content);
        $this->assertStringContainsString('Membatalkan Pesanan', $latest->xml_content);

        // Test download endpoint
        $downloadResponse = $this->get("/generator/{$latest->id}/download");
        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('Content-Disposition', "attachment; filename=\"adapt_activity_diagram_{$latest->id}.drawio\"");
    }

    public function test_generator_creates_orthogonal_clean_drawio_diagram(): void
    {
        $narrative = "User melakukan login, lalu sistem menampilkan pesanan yang belum dibayar. User memilih pembayaran pesanan. Jika user membatalkan, alur pembayaran selesai dibatalkan. Jika user mengonfirmasi pembayaran, user memasukkan kata sandi. Sistem memverifikasi kebenaran sandi. Jika sandi salah, sistem menampilkan pesan error dan user diminta memasukkan sandi kembali. Jika sandi benar, pihak third-party payment memproses pembayaran berhasil, kemudian sistem menerbitkan informasi tagihan dan alur selesai.";

        $response = $this->post('/generator', [
            'narrative' => $narrative,
        ]);

        $response->assertStatus(200);

        $latest = DiagramGeneration::latest('id')->first();
        $this->assertNotNull($latest);

        // Assert Draw.io orthogonal edge style (rounded=0, zero curves)
        $this->assertStringContainsString('edgeStyle=orthogonalEdgeStyle;rounded=0', $latest->xml_content);
        $this->assertStringContainsString('swimlane', $latest->xml_content);
        $this->assertStringContainsString('Third-party payment', $latest->xml_content);

        // Verify SVG has no curved bezier ' C ' commands
        $responseContent = $response->getContent();
        $this->assertStringNotContainsString(' C ', $responseContent);
    }

    public function test_generator_detects_ambiguous_narrative_and_requests_clarification(): void
    {
        $narrative = "Pengguna menekan tombol submit lalu sistem memproses data. Jika server merespons, alur berlanjut.";

        $response = $this->post('/generator', [
            'narrative' => $narrative,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('warning');

        $this->assertDatabaseHas('diagram_generations', [
            'status' => 'needs_clarification',
        ]);
    }

    public function test_generator_strips_actor_brackets_and_titles_from_activity_nodes(): void
    {
        $narrative = <<<NARRATIVE
Fitur: Proses Pemesanan dan Pengiriman e-Commerce
Aktor: Pembeli, Sistem Gudang, Kurir
Alur Proses:
1. [Pembeli] melakukan pembayaran belanjaan pada aplikasi.
2. [Sistem Gudang] menerima notifikasi dan melakukan verifikasi pembayaran.
3. [Sistem Gudang] memeriksa ketersediaan stok barang di rak.
4. Jika stok barang habis, [Sistem Gudang] melakukan pembatalan otomatis.
5. Jika stok barang aman, [Sistem Gudang] menyiapkan barang untuk dikemas.
6. [Jalur Merge] Alur pembatalan otomatis (dari langkah 4) atau alur pembatalan manual oleh pembeli sebelum dikemas akan bertemu di satu titik, yaitu [Sistem Gudang] memproses pengembalian dana (refund) ke pembeli, lalu proses selesai.
7. [Awal Fork] Setelah barang selesai dikemas pada langkah 5, dua aktivitas berikut harus berjalan secara bersamaan (paralel):
	• [Sistem Gudang] memperbarui sisa stok di database publik.
	• [Sistem Gudang] mencetak label pengiriman dokumen dan memanggil [Kurir].
8. [Awal Join] Setelah [Sistem Gudang] selesai memperbarui database stok DAN [Kurir] telah datang mengambil barang ke gudang, kedua jalur paralel ini harus selesai dan menyatu kembali.
9. [Kurir] mengantarkan paket perjalanan menuju alamat tujuan.
10. [Pembeli] menerima paket dan mengonfirmasi pesanan selesai.
NARRATIVE;

        $response = $this->post('/generator', [
            'narrative' => $narrative,
        ]);

        $response->assertStatus(200);

        $latest = DiagramGeneration::latest('id')->first();
        $this->assertNotNull($latest);
        $nodes = $latest->diagram_data['nodes'];

        // Assert no node contains bracketed actor tags or title prefixes
        foreach ($nodes as $node) {
            $label = $node['label'];
            $this->assertStringNotContainsString('[pembeli]', strtolower($label));
            $this->assertStringNotContainsString('[sistem gudang]', strtolower($label));
            $this->assertStringNotContainsString('[kurir]', strtolower($label));
            $this->assertStringNotContainsString('fitur:', strtolower($label));
            $this->assertStringNotContainsString('alur proses', strtolower($label));
            $this->assertStringNotContainsString('[awal fork]', strtolower($label));
            $this->assertStringNotContainsString('[awal join]', strtolower($label));
            $this->assertStringNotContainsString('[jalur merge]', strtolower($label));
        }

        // Verify correct swimlane partitions were extracted
        $this->assertContains('Pelanggan', $latest->diagram_data['partitions']);
        $this->assertContains('Sistem Gudang', $latest->diagram_data['partitions']);
        $this->assertContains('Kurir', $latest->diagram_data['partitions']);

        // Verify decision diamond has empty label
        $decisionNodes = array_filter($nodes, fn($n) => $n['type'] === 'decision');
        $this->assertNotEmpty($decisionNodes);
        foreach ($decisionNodes as $dNode) {
            $this->assertSame('', $dNode['label'], 'Decision node diamond must be empty.');
        }
    }

    public function test_auditor_index_loads_successfully(): void
    {
        $response = $this->get('/auditor');
        $response->assertStatus(200);
        $response->assertSee('Dual-Channel Logic Auditor');
    }

    public function test_auditor_inspects_diagram_and_enforces_active_verbs(): void
    {
        $fakeImage = UploadedFile::fake()->image('activity_diagram.png', 800, 600);
        $narrative = "Pelanggan memilih menu dan melakukan pembayaran. Sistem memeriksa status pembayaran. Jika berhasil, pesanan diteruskan ke dapur. Jika gagal, pesanan dibatalkan.";

        $response = $this->post('/auditor', [
            'diagram' => $fakeImage,
            'narrative' => $narrative,
        ]);

        $response->assertStatus(200);
        $response->assertSee('Laporan Hasil Pemeriksaan Diagram');
        $response->assertSee('OMG UML 2.5.1');
        $response->assertSee('Modul ITK SI2514022');

        $this->assertDatabaseHas('diagram_audits', [
            'status' => 'success',
        ]);

        $latestAudit = DiagramAudit::latest('id')->first();
        $this->assertNotNull($latestAudit);
        $this->assertNotEmpty($latestAudit->findings);

        // Verify active verb category exists in findings
        $hasActiveVerbFinding = false;
        foreach ($latestAudit->findings as $f) {
            if (($f['category'] ?? '') === 'verb_active_convention') {
                $hasActiveVerbFinding = true;
                break;
            }
        }
        $this->assertTrue($hasActiveVerbFinding, 'Temuan kaidah verba aktif KBBI harus disertakan dalam audit.');

        // Test download revised XML
        $downloadResponse = $this->get("/auditor/{$latestAudit->id}/download");
        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('Content-Disposition', "attachment; filename=\"adapt_audited_revisi_{$latestAudit->id}.drawio\"");
    }

    public function test_history_page_loads_successfully(): void
    {
        $response = $this->get('/history');
        $response->assertStatus(200);
        $response->assertSee('Riwayat Penggunaan AI');
    }

    public function test_generator_handles_custom_narratives_dynamically_without_stale_output(): void
    {
        // Custom scenario: Hospital patient examination (NOT restaurant/koki)
        $narrative = "Dokter memeriksa kondisi pasien di ruang periksa. Dokter menuliskan resep obat. Apoteker menyiapkan obat di ruang farmasi. Apoteker menyerahkan obat kepada pasien.";

        $response = $this->post('/generator', [
            'narrative' => $narrative,
        ]);

        $response->assertStatus(200);

        $latest = DiagramGeneration::latest('id')->first();
        $this->assertNotNull($latest);

        // Ensure it did NOT return the hardcoded restaurant/koki diagram
        $this->assertStringNotContainsString('Koki', $latest->xml_content);
        $this->assertStringNotContainsString('Memasak Pesanan', $latest->xml_content);

        // Verify dynamic actors and active verbs were extracted
        $this->assertStringContainsString('Dokter', $latest->xml_content);
        $this->assertStringContainsString('Apoteker', $latest->xml_content);
        $this->assertStringContainsString('Memeriksa Kondisi Pasien', $latest->xml_content);
        $this->assertStringContainsString('Menyiapkan Obat', $latest->xml_content);

        // Verify Draw.io XML unblocked editing & label positioning
        $this->assertStringContainsString('container=0;pointerEvents=0', $latest->xml_content);
        $this->assertStringContainsString('verticalLabelPosition=top;verticalAlign=bottom', $latest->xml_content);
        $this->assertStringContainsString('verticalLabelPosition=bottom;verticalAlign=top', $latest->xml_content);
    }

    public function test_generator_handles_custom_decision_branch_with_empty_diamond(): void
    {
        $narrative = "Pembeli memilih barang belanjaan di toko. Kasir memindai barcode belanjaan. Jika uang tunai cukup, kasir mencetak struk belanja. Jika uang tunai tidak cukup, kasir membatalkan transaksi belanja.";

        $response = $this->post('/generator', [
            'narrative' => $narrative,
        ]);

        $response->assertStatus(200);

        $latest = DiagramGeneration::latest('id')->first();
        $this->assertNotNull($latest);

        // Verify dynamic actors (Pembeli mapped to canonical Pelanggan role, and Kasir)
        $this->assertStringContainsString('Pelanggan', $latest->xml_content);
        $this->assertStringContainsString('Kasir', $latest->xml_content);

        // Decision diamond must NOT have any activity or question label
        $this->assertStringNotContainsString('value="Status Pembayaran?"', $latest->xml_content);

        // Outgoing branches must have guards
        $this->assertStringContainsString('uang tunai cukup', $latest->xml_content);
        $this->assertStringContainsString('uang tunai tidak cukup', $latest->xml_content);
    }
}
