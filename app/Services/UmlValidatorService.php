<?php

namespace App\Services;

class UmlValidatorService
{
    /**
     * Multi-layered validation of Activity Diagram data against schema, KBBI grammar,
     * and OMG UML 2.5.1 & Modul ITK SI2514022 standards.
     */
    public function validate(array $diagramData): array
    {
        $errors = [];
        $warnings = [];

        // Layer 1: Schema format and basic integrity checks
        if (empty($diagramData['nodes']) || !is_array($diagramData['nodes'])) {
            return [
                'valid' => false,
                'errors' => ['Diagram tidak memiliki daftar node/aktivitas yang valid.'],
                'warnings' => [],
            ];
        }

        $partitions = $diagramData['partitions'] ?? [];
        if (!is_array($partitions) || empty($partitions)) {
            $warnings[] = "Diagram belum mendefinisikan partisi swimlane secara eksplisit (OMG UML 2.5.1 Section 15.6.3).";
        }

        $nodes = [];
        $outgoingCounts = [];
        $incomingCounts = [];
        $hasInitial = false;
        $hasFinal = false;

        foreach ($diagramData['nodes'] as $node) {
            if (empty($node['id'])) {
                $errors[] = "Terdapat elemen node tanpa pengenal (id) yang valid.";
                continue;
            }

            $id = $node['id'];
            $nodes[$id] = $node;
            $outgoingCounts[$id] = 0;
            $incomingCounts[$id] = 0;

            $type = $node['type'] ?? 'activity';
            if ($type === 'initial') {
                $hasInitial = true;
            } elseif ($type === 'final') {
                $hasFinal = true;
            }

            // Layer 5: Validate actor and swimlane assignment
            if (!empty($partitions)) {
                $assignedPartition = $node['partition'] ?? '';
                if (empty($assignedPartition)) {
                    $warnings[] = "Node '{$id}' belum ditempatkan ke dalam partisi Swimlane (OMG UML 2.5.1 Section 15.6.3).";
                }
            }
        }

        $edges = $diagramData['edges'] ?? [];
        if (!is_array($edges)) {
            $edges = [];
        }

        // Layer 3: Validate node and edge relationships
        foreach ($edges as $edge) {
            $src = $edge['source'] ?? null;
            $tgt = $edge['target'] ?? null;

            if (!$src || !isset($nodes[$src])) {
                $errors[] = "Panah alur memiliki titik asal (source) yang tidak ditemukan: '{$src}'.";
                continue;
            }
            if (!$tgt || !isset($nodes[$tgt])) {
                $errors[] = "Panah alur memiliki titik tujuan (target) yang tidak ditemukan: '{$tgt}'.";
                continue;
            }

            $outgoingCounts[$src]++;
            $incomingCounts[$tgt]++;

            // Layer 6: Guard condition semantics according to OMG UML 2.5.1 Section 15.2.3.4
            $srcType = $nodes[$src]['type'] ?? '';
            $tgtType = $nodes[$tgt]['type'] ?? '';
            $guard = trim($edge['guard'] ?? '');

            if ($srcType === 'decision') {
                if (empty($guard)) {
                    $warnings[] = "Alur dari Decision Node '{$nodes[$src]['label']}' menuju '{$nodes[$tgt]['label']}' belum memiliki guard condition [kondisi] (OMG UML 2.5.1 Section 15.2.3.4).";
                }
            } elseif ($srcType === 'merge' && !empty($guard)) {
                $warnings[] = "Merge Node menyatukan alur kontrol alternatif berlogika 'OR', sehingga alur keluar tidak boleh memiliki guard condition (OMG UML 2.5.1 Section 15.3.3.5).";
            } elseif ($srcType === 'fork' && !empty($guard)) {
                $warnings[] = "Fork Node memecah alur konkuren yang berjalan bersamaan ('AND' split), sehingga alur keluar tidak boleh memiliki guard condition (OMG UML 2.5.1 Section 15.3.3.3).";
            } elseif ($tgtType === 'join' && !empty($guard)) {
                $warnings[] = "Join Node menyinkronkan alur konkuren ('AND' sync), sehingga alur masuk tidak boleh memiliki guard condition (OMG UML 2.5.1 Section 15.3.3.4).";
            }
        }

        // Layer 2 & 6: UML Semantics checks
        if (!$hasInitial) {
            $warnings[] = "Diagram sebaiknya memiliki tepat 1 Initial Node sebagai titik mula eksekusi (OMG UML 2.5.1 Section 15.3.3.2).";
        }
        if (!$hasFinal) {
            $warnings[] = "Diagram belum memiliki Activity Final Node sebagai titik selesai alur (OMG UML 2.5.1 Section 15.3.3.2).";
        }

        foreach ($nodes as $id => $node) {
            $type = $node['type'] ?? 'activity';
            $label = trim($node['label'] ?? '');
            $partition = trim($node['partition'] ?? '');

            if ($type === 'decision') {
                if ($outgoingCounts[$id] < 2) {
                    $errors[] = "Decision Node '{$id}' harus memiliki minimal 2 alur keluar (outgoing edges) untuk percabangan alternatif (OMG UML 2.5.1 Section 15.3.3.1).";
                }
                // Decision diamond must be empty
                if (!empty($label)) {
                    $warnings[] = "Simbol belah ketupat Decision Node ('{$id}') seharusnya tidak memuat teks ('{$label}'); teks kondisi harus diletakkan pada panah keluar sebagai Guard Condition (Modul ITK Slide 7).";
                }
            } elseif ($type === 'merge') {
                if ($incomingCounts[$id] < 2) {
                    $warnings[] = "Merge Node sebaiknya menerima minimal 2 alur masuk alternatif yang menyatu kembali (OMG UML 2.5.1 Section 15.3.3.5).";
                }
                if ($outgoingCounts[$id] > 1) {
                    $errors[] = "Merge Node hanya boleh memiliki tepat 1 alur keluar menuju proses selanjutnya (OMG UML 2.5.1 Section 15.3.3.5).";
                }
                if (!empty($label)) {
                    $warnings[] = "Merge Node ('{$id}') berbentuk belah ketupat polos dan tidak boleh memuat teks di dalamnya.";
                }
            } elseif ($type === 'fork') {
                if ($outgoingCounts[$id] < 2) {
                    $errors[] = "Fork Node harus membagi alur menjadi minimal 2 aktivitas paralel yang berjalan bersamaan (OMG UML 2.5.1 Section 15.3.3.3).";
                }
                if ($incomingCounts[$id] > 1) {
                    $warnings[] = "Fork Node umumnya hanya menerima 1 alur masuk untuk dipecah ke alur paralel (OMG UML 2.5.1 Section 15.3.3.3).";
                }
            } elseif ($type === 'join') {
                if ($incomingCounts[$id] < 2) {
                    $errors[] = "Join Node harus menyinkronkan minimal 2 alur masuk paralel sebelum melanjutkan alur (OMG UML 2.5.1 Section 15.3.3.4).";
                }
                if ($outgoingCounts[$id] > 1) {
                    $errors[] = "Join Node hanya boleh memiliki tepat 1 alur keluar setelah proses tersinkronisasi (OMG UML 2.5.1 Section 15.3.3.4).";
                }
            } elseif ($type === 'initial') {
                if ($incomingCounts[$id] > 0) {
                    $warnings[] = "Initial Node tidak boleh memiliki alur masuk (incoming edge) (OMG UML 2.5.1 Section 15.3.3.2).";
                }
            } elseif ($type === 'final') {
                if ($outgoingCounts[$id] > 0) {
                    $errors[] = "Activity Final Node tidak boleh memiliki alur keluar (OMG UML 2.5.1 Section 15.3.3.2).";
                }
            } elseif ($type === 'flow_final') {
                if ($outgoingCounts[$id] > 0) {
                    $errors[] = "Flow Final Node tidak boleh memiliki alur keluar (OMG UML 2.5.1 Section 15.3.3.6).";
                }
            } elseif ($type === 'object') {
                if ($incomingCounts[$id] === 0 && $outgoingCounts[$id] === 0) {
                    $warnings[] = "Object Node '{$label}' belum terhubung ke alur aktivitas manapun.";
                }
            } elseif ($type === 'time_event') {
                if ($incomingCounts[$id] === 0 && $outgoingCounts[$id] === 0) {
                    $warnings[] = "Time Event (Jam Pasir) '{$label}' belum terhubung ke alur aktivitas manapun.";
                }
            } elseif ($type === 'activity') {
                // Layer 4: Active Verb Convention (KBBI & UML Standard)
                $firstWord = strtolower(preg_split('/\s+/', $label)[0] ?? '');

                // Check passive form berawalan 'di-' or 'ter-'
                if ((str_starts_with($firstWord, 'di') && strlen($firstWord) > 3) ||
                    (str_starts_with($firstWord, 'ter') && strlen($firstWord) > 4)) {
                    $warnings[] = "Aktivitas '{$label}' menggunakan kata kerja pasif ('{$firstWord}'). Sesuai kaidah KBBI dan standar UML, gunakan frasa kata kerja aktif transitif berawalan 'me-'.";
                }

                // Check pure nouns that should be active verbs
                $nounPattern = '/^(pembayaran|pemeriksaan|validasi|pencatatan|pengiriman|pemesanan|verifikasi|pembatalan|pengajuan|pendaftaran|penjadwalan|penyimpanan)$/i';
                if (preg_match($nounPattern, $firstWord)) {
                    $warnings[] = "Aktivitas '{$label}' menggunakan kata benda (nomina). Sesuai kaidah KBBI, gunakan frasa kata kerja aktif (contoh: 'Melakukan {$label}' atau 'Memeriksa...').";
                }

                // Check overly generic labels
                if (preg_match('/^(proses\s+data|pengelolaan\s+informasi|pengecekan\s+data|pemrosesan)$/i', $label)) {
                    $warnings[] = "Aktivitas '{$label}' terlalu umum. Jelaskan tindakan spesifik yang dilakukan aktor dan objek sasarannya.";
                }

                // Check actor redundancy in label
                if (!empty($partition) && preg_match('/^' . preg_quote($partition, '/') . '\b/i', $label)) {
                    $warnings[] = "Label aktivitas '{$label}' memuat nama aktor yang redundan dengan nama Swimlane ('{$partition}'). Hapus nama aktor dari label aktivitas.";
                }
            }
        }

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
