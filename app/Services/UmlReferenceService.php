<?php

namespace App\Services;

class UmlReferenceService
{
    /**
     * Curated knowledge base grounded on three official authorities:
     * 1. Standar Resmi OMG UML 2.5.1 Specification (Chapter 15: Activities & Chapter 16: Actions)
     * 2. Modul Perkuliahan: Software Design and Development (SI2514022) - Week 2: Activity Diagram, Information Systems ITK
     * 3. Kaidah Tata Bahasa Baku Bahasa Indonesia (KBBI) & Pedoman Pembentukan Istilah
     */
    protected static array $curriculum = [
        'institution' => 'Information Systems - ITK (Institut Teknologi Kalimantan)',
        'course' => 'Software Design and Development (SI2514022)',
        'module' => 'Week 2: Activity Diagram',
        'term' => 'Odd Semester 2026/2027',
    ];

    /**
     * Structured knowledge catalog indexed by rule key
     */
    protected static array $rules = [
        'verb_active_convention' => [
            'authority' => 'KBBI & OMG UML 2.5.1 (Section 15.2.3.1) & Modul ITK (Slide 6 & 12)',
            'standard' => 'Kaidah Morfologi KBBI & Konvensi Penamaan Aksi UML',
            'section' => 'KBBI Morfologi Verba meN- & Modul ITK Slide 6 ("Activities and Action")',
            'title' => 'Kaidah Penamaan Action Node Menggunakan Kata Kerja Aktif Transitif',
            'description' => 'Nama aktivitas (Action Node) wajib berupa frasa kata kerja aktif transitif berawalan me- (me-, mem-, men-, meng-, meny-) yang diikuti objek/sasaran aksi (contoh: "Memilih Menu", "Memverifikasi Pembayaran", "Menyiapkan Berkas"). Dilarang menggunakan kata kerja pasif (awalan di-) atau nomina murni (contoh: "Pembayaran"). Jika narasi menggunakan kalimat pasif ("Formulir diisi mahasiswa"), ubah menjadi bentuk aktif dengan subjek sebagai pemilik swimlane ("Mahasiswa") dan aksi aktif ("Mengisi Formulir").',
            'recommendation' => 'Gunakan frasa kerja aktif berawalan me- yang spesifik dan jelas objek sasarannya sesuai konteks narasi.',
            'keywords' => ['aksi', 'aktivitas', 'kata kerja', 'aktif', 'pasif', 'verba', 'kbbi', 'diisi', 'diperiksa', 'disimpan', 'diajukan', 'dicatat', 'diterbitkan'],
        ],
        'decision_node' => [
            'authority' => 'OMG UML 2.5.1 (Section 15.3.3.1) & Modul ITK SI2514022 (Slide 7)',
            'standard' => 'OMG Unified Modeling Language (OMG UML) 2.5.1',
            'section' => 'Section 15.3.3.1 (DecisionNode) & Modul ITK Slide 7 ("Decision/Conditional Node")',
            'title' => 'Aturan Struktur Decision Node (Belah Ketupat Bersyarat & Guard Condition)',
            'description' => 'Decision node memodelkan titik evaluasi logika dengan 1 alur masuk dan minimal 2 alur keluar alternatif. Simbol belah ketupat WAJIB berlabel kosong (label = ""). Teks kondisi atau syarat pemicu WAJIB dicantumkan pada garis panah keluar sebagai Guard Condition di dalam tanda kurung siku [kondisi]. Kondisi percabangan harus berasal langsung dari fakta narasi pengguna.',
            'recommendation' => 'Gunakan Decision Node berbentuk belah ketupat polos dan cantumkan setiap kondisi pada panah keluar sebagai Guard [kondisi].',
            'keywords' => ['decision', 'percabangan', 'kondisi', 'jika', 'apabila', 'bila', 'kalau', 'apakah', 'berhasil', 'gagal', 'valid', 'tidak valid', 'diterima', 'ditolak'],
        ],
        'merge_node' => [
            'authority' => 'OMG UML 2.5.1 (Section 15.3.3.5) & Modul ITK SI2514022 (Slide 8)',
            'standard' => 'OMG Unified Modeling Language (OMG UML) 2.5.1',
            'section' => 'Section 15.3.3.5 (MergeNode) & Modul ITK Slide 8 ("Merge Node")',
            'title' => 'Aturan Struktur Merge Node (Penyatuan Alur Alternatif Berlogika "OR")',
            'description' => 'Merge Node menyatukan kembali alur-alur alternatif yang berasal dari Decision Node ke satu alur keluar umum. Berbeda dengan Join, Merge Node berlogika "OR" (aliran langsung dilanjutkan begitu salah satu alur masuk aktif tanpa menunggu alur lain). Simbol belah ketupat WAJIB berlabel kosong (label = "") dan garis keluar TIDAK boleh memiliki guard condition.',
            'recommendation' => 'Gunakan Merge Node berupa belah ketupat kosong untuk menyatukan kembali cabang-cabang keputusan alternatif.',
            'keywords' => ['merge', 'penyatuan', 'alternatif', 'menyatu kembali', 'kembali ke alur utama', 'bertemu kembali'],
        ],
        'guard_condition' => [
            'authority' => 'OMG UML 2.5.1 (Section 15.2.3.4) & Modul ITK SI2514022 (Slide 7)',
            'standard' => 'OMG Unified Modeling Language (OMG UML) 2.5.1',
            'section' => 'Section 15.2.3.4 (Control Flow & Guards) & Modul ITK Slide 7',
            'title' => 'Kaidah Penulisan Guard Condition pada Percabangan',
            'description' => 'Guard Condition dievaluasi saat token kontrol mencapai Decision Node. Teks guard ditulis dalam kurung siku [kondisi] pada panah keluar. Seluruh guard keluar dari decision yang sama harus saling lepas (mutually exclusive) dan lengkap (exhaustive) menampung kemungkinan kejadian.',
            'recommendation' => 'Tuliskan teks guard secara eksplisit di dalam tanda kurung siku [kondisi] pada setiap panah keluar decision.',
            'keywords' => ['guard', 'syarat', 'kondisi', 'kriteria', 'aturan'],
        ],
        'fork_join' => [
            'authority' => 'OMG UML 2.5.1 (Section 15.3.3.3 & 15.3.3.4) & Modul ITK SI2514022 (Slide 9)',
            'standard' => 'OMG Unified Modeling Language (OMG UML) 2.5.1',
            'section' => 'Section 15.3.3.3 (ForkNode), 15.3.3.4 (JoinNode) & Modul ITK Slide 9 ("Fork and Join Nodes")',
            'title' => 'Aturan Alur Konkuren & Paralel (Fork Split dan Join Synchronization Bar)',
            'description' => 'Fork Node memecah 1 alur masuk menjadi beberapa alur yang dieksekusi secara BERSAMAAN / PARALEL / SIMULTAN ("AND" split). Join Node menyatukan dan MENYINKRONKAN kembali alur-alur paralel tersebut ("AND" synchronization: proses selanjutnya baru dijalankan jika SEMUA alur masuk telah selesai). Kedua notasi digambarkan dengan bilah balok hitam tebal horizontal/vertikal berlabel kosong tanpa guard.',
            'recommendation' => 'Gunakan Fork Node saat narasi menyebutkan proses berlangsung bersamaan/paralel, dan pasangkan dengan Join Node untuk menyinkronkannya kembali.',
            'keywords' => ['fork', 'join', 'paralel', 'bersamaan', 'simultan', 'serentak', 'sinkron', 'kedua proses', 'secara paralel', 'secara bersamaan'],
        ],
        'initial_final' => [
            'authority' => 'OMG UML 2.5.1 (Section 15.3.3.2) & Modul ITK SI2514022 (Slide 6 & 12)',
            'standard' => 'OMG Unified Modeling Language (OMG UML) 2.5.1',
            'section' => 'Section 15.3.3.2 (InitialNode & ActivityFinalNode) & Modul ITK Slide 6',
            'title' => 'Titik Awal (Solid Black Circle) dan Titik Akhir (Bullseye)',
            'description' => 'Tepat satu Initial Node (lingkaran hitam pekat berlabel "Mulai") menandai awal eksekusi, diletakkan pada swimlane aktor pemula proses. Activity Final Node (lingkaran cincin bertitik tengah pekat / bullseye berlabel "Selesai") menghentikan seluruh proses ketika aktivitas tuntas.',
            'recommendation' => 'Pastikan diagram memiliki tepat satu Initial Node di swimlane pemula dan Activity Final Node penutup.',
            'keywords' => ['mulai', 'awal', 'start', 'initial', 'selesai', 'akhir', 'final', 'end', 'berakhir'],
        ],
        'swimlane' => [
            'authority' => 'OMG UML 2.5.1 (Section 15.6.3) & Modul ITK SI2514022 (Slide 5 & 10)',
            'standard' => 'OMG Unified Modeling Language (OMG UML) 2.5.1',
            'section' => 'Section 15.6.3 (ActivityPartition) & Modul ITK Slide 10 ("Lanes/Swimlanes")',
            'title' => 'Pemisahan Partisi Tanggung Jawab & Aktor (Swimlanes)',
            'description' => 'Swimlanes membagi diagram ke dalam kolom/baris logis yang masing-masing merepresentasikan satu aktor, sistem, departemen, atau pihak yang bertanggung jawab atas aktivitas di dalamnya. Aktor harus diekstrak secara dinamis sesuai narasi proses (misal: Mahasiswa, Dosen, Dokter, Pasien, Nasabah, Teller, Kurir). Nama aktor TIDAK boleh diulang dalam label aktivitas.',
            'recommendation' => 'Kelompokkan setiap Action Node ke dalam swimlane aktor yang benar-benar melakukan tindakan tersebut.',
            'keywords' => ['swimlane', 'partisi', 'aktor', 'peran', 'kolom', 'tanggung jawab', 'peserta', 'pihak'],
        ],
        'flow_final_node' => [
            'authority' => 'OMG UML 2.5.1 (Section 15.3.3.6)',
            'standard' => 'OMG Unified Modeling Language (OMG UML) 2.5.1',
            'section' => 'Section 15.3.3.6 (FlowFinalNode)',
            'title' => 'Penghentian Cabang Khusus (Flow Final Node / Circle X)',
            'description' => 'Flow Final Node (lingkaran dengan simbol tanda silang "X") digunakan untuk menghentikan satu cabang kontrol tertentu (misalnya cabang pembatalan pesanan atau verifikasi gagal) tanpa mematikan seluruh proses atau cabang-cabang lain yang sedang berlangsung.',
            'recommendation' => 'Gunakan Flow Final Node saat alur pembatalan lokal dihentikan sementara proses lain tetap berjalan.',
            'keywords' => ['flow final', 'batal', 'dibatalkan', 'berhenti', 'dihentikan', 'terminasi', 'keluar'],
        ],
        'object_node' => [
            'authority' => 'OMG UML 2.5.1 (Section 15.2.3.2)',
            'standard' => 'OMG Unified Modeling Language (OMG UML) 2.5.1',
            'section' => 'Section 15.2.3.2 (Object Nodes & Data Stores)',
            'title' => 'Notasi Objek Data dan Dokumen Fisik (Object Node)',
            'description' => 'Object Node (simbol persegi panjang bersudut siku) memodelkan data, berkas, dokumen fisik, atau status entitas yang dihasilkan (output) atau dikonsumsi (input) oleh Action Node (contoh: "Faktur Tagihan", "Resep Obat", "Data Transaksi").',
            'recommendation' => 'Gunakan Object Node berbentuk persegi panjang bersudut siku untuk merepresentasikan data atau dokumen yang mengalir.',
            'keywords' => ['dokumen', 'berkas', 'faktur', 'resep', 'laporan', 'surat', 'data', 'object', 'objek'],
        ],
        'time_event' => [
            'authority' => 'OMG UML 2.5.1 (Section 16.3.3)',
            'standard' => 'OMG Unified Modeling Language (OMG UML) 2.5.1',
            'section' => 'Section 16.3.3 (TimeEvent & TimeTrigger)',
            'title' => 'Pemicu Berbasis Waktu & Durasi Tunggu (Time Event / Hourglass)',
            'description' => 'Time Event digambarkan dengan simbol jam pasir (hourglass). Notasi ini merepresentasikan kejadian yang dipicu oleh berjalannya waktu, durasi tunggu (misal: "Menunggu 15 Menit"), batas waktu kedaluwarsa (timeout), atau jadwal berkala.',
            'recommendation' => 'Gunakan simbol jam pasir saat alur proses bergantung pada durasi waktu tunggu atau timeout.',
            'keywords' => ['waktu', 'menunggu', 'durasi', 'menit', 'jam', 'hari', 'timeout', 'tenggat', 'jadwal'],
        ],
        'signal_nodes' => [
            'authority' => 'OMG UML 2.5.1 (Section 16.3.1 & 16.3.2)',
            'standard' => 'OMG Unified Modeling Language (OMG UML) 2.5.1',
            'section' => 'Section 16.3 (SendSignalAction & AcceptEventAction)',
            'title' => 'Notasi Pengiriman dan Penerimaan Sinyal Asinkron',
            'description' => 'Send Signal Action (pentagon cembung ke kanan) memodelkan pengiriman notifikasi/sinyal ke entitas eksternal. Accept Event Action (pentagon dengan takik cekung) memodelkan penerimaan pemicu/sinyal dari pihak luar.',
            'recommendation' => 'Gunakan Send Signal dan Accept Event untuk komunikasi pesan/event asinkron.',
            'keywords' => ['sinyal', 'notifikasi', 'event', 'kirim sinyal', 'terima sinyal', 'konfirmasi bank'],
        ],
        'steps_to_create' => [
            'authority' => 'Modul ITK SI2514022 (Slide 12: How to Make an Activity Diagram)',
            'standard' => 'Pedoman Metodologis Perancangan Activity Diagram ITK',
            'section' => 'Modul ITK Slide 12',
            'title' => '6 Langkah Standar Perancangan Activity Diagram (ITK SI2514022)',
            'description' => 'Urutan perancangan: 1) Tentukan ruang lingkup (awal & akhir), 2) Identifikasi aksi-aksi dengan rounded rectangle, 3) Tambahkan Initial dan Final nodes, 4) Tentukan Decision points bersyarat, 5) Petakan alur bersamaan (Fork & Join), 6) Kelompokkan ke dalam Swimlanes berdasarkan aktor penanggung jawab.',
            'recommendation' => 'Ikuti keenam langkah metodologis ini untuk memastikan kelengkapan dan kebenaran diagram.',
            'keywords' => ['langkah', 'tahapan', 'pedoman', 'metodologi', 'pembuatan'],
        ],
        'drawio_standards' => [
            'authority' => 'diagrams.net (Draw.io) UML Activity Diagram Stencil Standard',
            'standard' => 'Format mxGraph XML diagrams.net',
            'section' => 'Standard Draw.io mxGraph Activity Specification',
            'title' => 'Standar Geometri & Layout Draw.io (Orthogonal Edge & Open Container)',
            'description' => 'Garis penghubung wajib menggunakan orthogonalEdgeStyle siku 90 derajat (rounded=0). Belah ketupat Decision dan Merge wajib kosong tanpa teks di dalamnya. Balok Fork dan Join berukuran tebal padat. Swimlanes dibuat dengan container transparan (pointerEvents=0) agar pengguna dapat leluasa menggeser dan mengedit node secara manual.',
            'recommendation' => 'Hasilkan XML Draw.io dengan garis ortogonal rapi, penempatan swimlane proporsional, dan tanpa tumpang tindih.',
            'keywords' => ['drawio', 'xml', 'layout', 'orthogonal', 'diagrams.net', 'mxgraph'],
        ],
    ];

    /**
     * Retrieval-Augmented Generation (RAG) Mechanism:
     * Analyzes user narrative, detects semantic features, and dynamically retrieves
     * relevant rules, citations, and guidance from OMG UML 2.5.1, KBBI, and ITK module.
     */
    public static function retrieveRelevantContext(string $narrative, array $detectedFeatures = []): array
    {
        $textLower = strtolower($narrative);
        $scoredRules = [];

        // Base mandatory rules that apply to every Activity Diagram
        $mandatoryKeys = ['verb_active_convention', 'initial_final', 'swimlane', 'steps_to_create', 'drawio_standards'];
        foreach ($mandatoryKeys as $key) {
            if (isset(self::$rules[$key])) {
                $scoredRules[$key] = 100; // base weight
            }
        }

        // Semantic feature detectors
        $featureDetectors = [
            'decision_node' => ['/\b(jika|apabila|bila|kalau|apakah|kondisi|status|berhasil|gagal|valid|tidak\s*valid|diterima|ditolak|lulus|tidak\s*lulus)\b/i'],
            'guard_condition' => ['/\b(jika|apabila|bila|kalau|kondisi|syarat|kriteria)\b/i', '/\[[^\]]+\]/'],
            'merge_node' => ['/\b(jika|apabila|kembali|bertemu|menyatu|alternatif|atau)\b/i'],
            'fork_join' => ['/\b(secara\s+paralel|secara\s+bersamaan|paralel|bersamaan|simultan|serentak|dua\s+proses|kedua\s+tahap|kedua\s+proses)\b/i', '/\[(?:awal\s*fork|fork|awal\s*join|join)\]/i'],
            'flow_final_node' => ['/\b(batal|dibatalkan|berhenti|dihentikan|stop|keluar|gagal\s+dan\s+selesai)\b/i'],
            'object_node' => ['/\b(dokumen|berkas|faktur|resep|data|laporan|surat|transaksi|formulir)\b/i'],
            'time_event' => ['/\b(menunggu|selama\s+[0-9]+|setelah\s+[0-9]+|durasi|timeout|tenggat|jadwal|menit|jam|hari)\b/i'],
            'signal_nodes' => ['/\b(sinyal|notifikasi|event|sms|email|broadcast|mengirimkan\s+notifikasi|menerima\s+konfirmasi)\b/i'],
        ];

        foreach ($featureDetectors as $ruleKey => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $textLower)) {
                    $scoredRules[$ruleKey] = ($scoredRules[$ruleKey] ?? 0) + 50;
                }
            }
        }

        // Check keyword matches across all indexed rules
        foreach (self::$rules as $key => $rule) {
            foreach ($rule['keywords'] as $kw) {
                if (str_contains($textLower, strtolower($kw))) {
                    $scoredRules[$key] = ($scoredRules[$key] ?? 0) + 15;
                }
            }
        }

        // Sort rules by relevance score
        arsort($scoredRules);

        $selectedRules = [];
        $citations = [];
        $contextLines = [];

        $contextLines[] = "=== DOKUMEN REFERENSI BAKU YANG DIRETRIEVE (RAG) ===";
        $contextLines[] = "Sumber Otoritatif:";
        $contextLines[] = "1. OMG Unified Modeling Language (OMG UML) 2.5.1 Specification (Chapter 15 & 16)";
        $contextLines[] = "2. Modul Perkuliahan SI2514022 (Software Design & Development, Information Systems ITK)";
        $contextLines[] = "3. Kamus Besar Bahasa Indonesia (KBBI) & Tata Bahasa Baku Bahasa Indonesia";
        $contextLines[] = "";

        foreach (array_keys($scoredRules) as $key) {
            $rule = self::$rules[$key];
            $selectedRules[$key] = $rule;
            $citations[] = "{$rule['authority']} [{$rule['section']}]";

            $contextLines[] = "[{$rule['title']}]";
            $contextLines[] = "Otoritas/Rujukan : {$rule['authority']}";
            $contextLines[] = "Pasal/Bagian    : {$rule['section']}";
            $contextLines[] = "Aturan Baku     : {$rule['description']}";
            $contextLines[] = "Implementasi    : {$rule['recommendation']}";
            $contextLines[] = "";
        }

        return [
            'context_text' => implode("\n", $contextLines),
            'citations' => array_unique($citations),
            'rules' => $selectedRules,
        ];
    }

    /**
     * Retrieve rule reference by topic / category
     */
    public static function getReference(string $topic): ?array
    {
        return self::$rules[$topic] ?? null;
    }

    /**
     * Search relevant rules based on keywords
     */
    public static function findRelevant(string $query): array
    {
        $q = strtolower($query);
        $results = [];

        foreach (self::$rules as $key => $rule) {
            if (
                str_contains($q, $key) ||
                str_contains(strtolower($rule['title']), $q) ||
                str_contains(strtolower($rule['description']), $q)
            ) {
                $results[$key] = $rule;
            }
        }

        return !empty($results) ? $results : ['general' => self::$rules['verb_active_convention']];
    }

    /**
     * Retrieve curriculum metadata
     */
    public static function getCurriculum(): array
    {
        return self::$curriculum;
    }

    /**
     * Retrieve all rules
     */
    public static function getAllRules(): array
    {
        return self::$rules;
    }
}
