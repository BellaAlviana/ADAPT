@extends('layouts.app')

@section('title', 'Generator Activity Diagram | Adapt')

@section('content')
<div class="dashboard-grid">
    <!-- Main Left Column -->
    <div class="dashboard-main">
        <div class="page-title-box">
            <span class="mode-pill pill-sage">MODE 01</span>
            <h1 class="serif-page-title">Generator Activity Diagram</h1>
            <p class="page-subtitle">Jelaskan narasi proses bisnis atau skenario use case untuk menghasilkan draf diagram dan berkas Draw.io.</p>
        </div>

        <!-- Form Card (Styled with Soft Sage Theme) -->
        <section class="card-sage-form">
            <form action="{{ route('generator.generate') }}" method="POST" id="generator-form">
                @csrf

                <div class="form-group-custom">
                    <div class="form-label-row">
                        <label for="narrative" class="custom-label">Narasi Proses Bisnis / Skenario Use Case</label>
                        <span class="char-hint">Min. 20 karakter</span>
                    </div>

                    <!-- Preset Samples -->
                    <div class="preset-pill-row">
                        <span class="preset-title">Contoh Cepat:</span>
                        <button type="button" class="preset-chip" onclick="loadSample(5)" style="background: #eaf4eb; border-color: #92b899; font-weight: 600;">✨ Alur Lengkap (Fork, Join, Decision, Merge)</button>
                        <button type="button" class="preset-chip" onclick="loadSample(4)">Alur 3 Swimlane (User, Sistem, Third-Party)</button>
                        <button type="button" class="preset-chip" onclick="loadSample(1)">Percabangan Restoran (Decision)</button>
                        <button type="button" class="preset-chip" onclick="loadSample(2)">Validasi Tiket Paralel (Fork/Join)</button>
                        <button type="button" class="preset-chip" onclick="loadSample(3)">Uji Klarifikasi (Ambigu)</button>
                    </div>

                    <textarea
                        id="narrative"
                        name="narrative"
                        rows="7"
                        minlength="20"
                        maxlength="10000"
                        required
                        placeholder="Contoh: User melakukan login, lalu sistem menampilkan pesanan yang belum dibayar. User memilih pembayaran pesanan..."
                    >{{ old('narrative', $currentResult->narrative ?? '') }}</textarea>

                    <div class="field-footer">
                        <small class="hint-kbbi">Sesuai kaidah KBBI & notasi UML, nama aktivitas disusun menggunakan <strong>kata kerja aktif transitif</strong> (berawalan <em>me-</em>).</small>
                        <span id="char-counter" class="counter-badge">0 karakter</span>
                    </div>
                </div>

                <div class="form-btn-row">
                    <button type="submit" class="pill-btn-action btn-sage">
                        <span>Mulai Analisis</span>
                        <span class="arrow-right">→</span>
                    </button>
                    <button type="button" class="pill-btn-outline" onclick="document.getElementById('narrative').value = ''; updateCharCount();">
                        Bersihkan
                    </button>
                </div>
            </form>
        </section>

        <!-- Result Section (If generated) -->
        @if (isset($currentResult) && $currentResult)
        <section class="result-block">
            <div class="result-box">
                <div class="result-top-bar">
                    <div>
                        <div class="status-chip chip-success">✓ Draf Selesai & Lolos Validasi</div>
                        <h2 class="serif-card-title">Hasil Perancangan Activity Diagram</h2>
                        <p class="result-sub">Ditata rapi secara otomatis (Dagre Layout) dan disesuaikan dengan format Draw.io manual.</p>
                    </div>

                    <div class="result-btn-wrap">
                        <a href="{{ route('generator.download', $currentResult->id) }}" class="pill-btn-action btn-sage">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            <span>Unduh Berkas Draw.io (.drawio)</span>
                        </a>
                    </div>
                </div>

                <!-- Validation Status Pills -->
                <div class="val-pills-wrap">
                    <span class="val-pill pill-success">✓ Kaidah Notasi UML Terpenuhi</span>
                    <span class="val-pill pill-success">✓ Kata Kerja Aktif (KBBI)</span>
                    <span class="val-pill pill-success">✓ Garis Siku 90° (Draw.io Standar)</span>
                    @php
                        $nodeTypes = array_column($currentResult->diagram_data['nodes'] ?? [], 'type');
                    @endphp
                    @if (in_array('decision', $nodeTypes) || in_array('merge', $nodeTypes))
                    <span class="val-pill pill-success">✓ Decision & Merge Node</span>
                    @endif
                    @if (in_array('fork', $nodeTypes) || in_array('join', $nodeTypes))
                    <span class="val-pill pill-success">✓ Fork & Join (Paralel)</span>
                    @endif
                    @if (!empty($currentResult->diagram_data['partitions']))
                    <span class="val-pill pill-neutral">{{ count($currentResult->diagram_data['partitions']) }} Swimlane Aktor</span>
                    @endif
                </div>

                @if (!empty($validation['warnings']))
                <div class="notice-callout warning-callout">
                    <strong>Catatan Logika:</strong>
                    <ul>
                        @foreach ($validation['warnings'] as $warn)
                            <li>{{ $warn }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Canvas Preview with Zoom & Pan Controls -->
                <div class="canvas-preview-card">
                    <div class="canvas-toolbar">
                        <div class="canvas-title-group">
                            <span class="canvas-title">Pratinjau Visual Diagram (Draw.io Clean White Format)</span>
                            <span class="canvas-note">Skala otomatis pas layar. Gunakan tombol zoom untuk memperbesar/memperkecil atau geser (drag).</span>
                        </div>
                        <div class="canvas-zoom-controls">
                            <button type="button" class="btn-zoom" onclick="changeZoom(-0.15)" title="Perkecil (Zoom Out)">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            </button>
                            <span class="zoom-level-badge" id="zoom-badge">65%</span>
                            <button type="button" class="btn-zoom" onclick="changeZoom(0.15)" title="Perbesar (Zoom In)">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            </button>
                            <button type="button" class="btn-zoom btn-zoom-action" onclick="resetZoomToFit()" title="Sesuaikan Ukuran Optimal">
                                Fit View
                            </button>
                            <button type="button" class="btn-zoom btn-zoom-action" onclick="setExactZoom(1.0)" title="Ukuran Asli 100%">
                                100%
                            </button>
                        </div>
                    </div>

                    <div class="svg-viewport" id="svg-viewport-box">
                        <div class="svg-zoom-target" id="svg-zoom-wrapper">
                            {!! $svgPreview ?? '<p class="empty-text">Memuat visual...</p>' !!}
                        </div>
                    </div>
                </div>

                <!-- XML Accordion -->
                <details class="code-accordion">
                    <summary class="accordion-head">
                        <span>Lihat Kode XML diagrams.net (mxGraph)</span>
                        <span class="accordion-btn">Salin / Buka</span>
                    </summary>
                    <div class="accordion-content">
                        <div class="code-actions">
                            <span>Format: .drawio mxfile</span>
                            <button type="button" class="btn-copy-code" onclick="copyXml()">Salin XML</button>
                        </div>
                        <textarea id="xml-code-block" readonly rows="8">{{ $currentResult->xml_content }}</textarea>
                    </div>
                </details>
            </div>
        </section>
        @endif
    </div>

    <!-- Right Sidebar Panel: Information Guide -->
    <aside class="dashboard-sidebar">
        <div class="about-card">
            <div class="about-header">
                <div class="about-icon-wrap icon-sage">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm1 15h-2v-6h2zm0-8h-2V7h2z"></path>
                    </svg>
                </div>
                <h3 class="about-title">Rujukan & Kaidah UML</h3>
            </div>

            <p class="about-desc">
                Dirancang mengacu pada <strong>Modul SI2514022 (Week 2: Activity Diagram) - Information Systems ITK</strong> dan standar <strong>OMG UML 2.5.1</strong>:
            </p>

            <div class="feature-bullets">
                <div class="bullet-item">
                    <div class="bullet-icon-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"></path></svg>
                    </div>
                    <div class="bullet-text">
                        <span class="bullet-title">Kata Kerja Aktif (Slide 6 & KBBI)</span>
                        <span class="bullet-sub">Aktivitas adalah proses tindakan nyata; wajib kata kerja aktif berawalan <em>me-</em>, bukan pasif atau nomina.</span>
                    </div>
                </div>

                <div class="bullet-item">
                    <div class="bullet-icon-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 22 12 12 22 2 12 12 2"></polygon></svg>
                    </div>
                    <div class="bullet-text">
                        <span class="bullet-title">Decision & Merge Node (Slide 7 & 8)</span>
                        <span class="bullet-sub">Decision memecah alur alternatif ber-guard; Merge menyatukan kembali cabang alternatif tanpa guard.</span>
                    </div>
                </div>

                <div class="bullet-item">
                    <div class="bullet-icon-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="2" rx="1"></rect></svg>
                    </div>
                    <div class="bullet-text">
                        <span class="bullet-title">Konkurensi Fork/Join (Slide 9)</span>
                        <span class="bullet-sub">Balok sinkronisasi tebal memodelkan alur simultan yang berjalan bersamaan secara paralel.</span>
                    </div>
                </div>

                <div class="bullet-item">
                    <div class="bullet-icon-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>
                    </div>
                    <div class="bullet-text">
                        <span class="bullet-title">Partisi Swimlanes (Slide 10)</span>
                        <span class="bullet-sub">Membagi batas tanggung jawab aktor/partisipan (User, System, Pihak Ketiga).</span>
                    </div>
                </div>

                <div class="bullet-item">
                    <div class="bullet-icon-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><line x1="8" y1="8" x2="16" y2="16"></line><line x1="16" y1="8" x2="8" y2="16"></line></svg>
                    </div>
                    <div class="bullet-text">
                        <span class="bullet-title">Flow Final & Sinyal (UML 15.3 & 16.3)</span>
                        <span class="bullet-sub">Flow Final (lingkaran silang X) membatalkan cabang kontrol tanpa mematikan alur lain; Sinyal (pentagon) menangani event asinkron.</span>
                    </div>
                </div>

                <div class="bullet-item">
                    <div class="bullet-icon-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 2h14v4l-5 5 5 5v4H5v-4l5-5-5-5V2z"></path></svg>
                    </div>
                    <div class="bullet-text">
                        <span class="bullet-title">Time Event (Jam Pasir / Hourglass)</span>
                        <span class="bullet-sub">Simbol jam pasir untuk memodelkan pemicu waktu tunggu, jeda durasi, atau batas timeout proses bisnis.</span>
                    </div>
                </div>

                <div class="bullet-item">
                    <div class="bullet-icon-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="0"></rect></svg>
                    </div>
                    <div class="bullet-text">
                        <span class="bullet-title">Object Node / Dokumen Data</span>
                        <span class="bullet-sub">Persegi panjang bersudut siku untuk merepresentasikan aliran data fisik/dokumen transaksi antar proses.</span>
                    </div>
                </div>

                <div class="bullet-item">
                    <div class="bullet-icon-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                    </div>
                    <div class="bullet-text">
                        <span class="bullet-title">Konektor Draw.io 90° Orthogonal</span>
                        <span class="bullet-sub">Garis siku 90 derajat rapi tanpa kurva meliuk, kompatibel penuh saat dibuka di diagrams.net.</span>
                    </div>
                </div>
            </div>

            <!-- Bottom decorative foliage -->
            <div class="about-leaf-bottom">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                    <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"></path>
                    <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"></path>
                </svg>
            </div>
        </div>
    </aside>
</div>

<script>
function loadSample(type) {
    const textarea = document.getElementById('narrative');
    if (type === 5) {
        textarea.value = "Pelanggan memasukkan pesanan barang. Sistem memverifikasi stok barang di gudang. Jika stok tersedia, sistem mengonfirmasi ketersediaan barang. Jika stok kosong, sistem menawarkan opsi pre-order. Kedua alur keputusan menyatu kembali untuk meminta pembayaran pelanggan. Pelanggan melakukan pembayaran tagihan. Secara bersamaan, Sistem mencetak resi pembayaran dan Petugas Gudang mengemas barang pesanan. Setelah kedua proses paralel tersebut selesai, Kurir mengirimkan paket pesanan ke alamat tujuan hingga alur selesai.";
    } else if (type === 4) {
        textarea.value = "User melakukan login, lalu sistem menampilkan pesanan yang belum dibayar. User memilih pembayaran pesanan. Jika user membatalkan, alur pembayaran selesai dibatalkan. Jika user mengonfirmasi pembayaran, user memasukkan kata sandi. Sistem memverifikasi kebenaran sandi. Jika sandi salah, sistem menampilkan pesan error dan user diminta memasukkan sandi kembali. Jika sandi benar, pihak third-party payment memproses pembayaran berhasil, kemudian sistem menerbitkan informasi tagihan dan alur selesai.";
    } else if (type === 1) {
        textarea.value = "Pelanggan memilih menu dan melakukan pembayaran. Sistem memeriksa hasil pembayaran. Pembayaran hanya memiliki dua kemungkinan hasil, yaitu berhasil atau gagal. Jika pembayaran berhasil, sistem meneruskan pesanan kepada koki untuk dimasak. Jika pembayaran gagal, sistem membatalkan pesanan.";
    } else if (type === 2) {
        textarea.value = "Petugas memvalidasi tiket masuk. Setelah tiket dinyatakan valid, sistem secara bersamaan mencatat waktu kedatangan pengunjung dan memperbarui log kunjungan. Setelah kedua proses tersebut selesai, sistem mempersilakan pengunjung masuk.";
    } else if (type === 3) {
        textarea.value = "Pengguna menekan tombol submit lalu sistem memproses data. Jika server merespons, alur berlanjut.";
    }
    updateCharCount();
}

function updateCharCount() {
    const el = document.getElementById('narrative');
    const counter = document.getElementById('char-counter');
    if (el && counter) {
        counter.textContent = el.value.length + " karakter";
    }
}

function copyXml() {
    const text = document.getElementById('xml-code-block');
    if (text) {
        text.select();
        navigator.clipboard.writeText(text.value).then(() => {
            alert('Kode XML Draw.io berhasil disalin ke clipboard!');
        });
    }
}

// Diagram Zoom & Pan Engine
let currentScale = 0.65;
const minScale = 0.25;
const maxScale = 2.2;

function renderZoom() {
    const target = document.getElementById('svg-zoom-wrapper');
    const badge = document.getElementById('zoom-badge');
    if (target) {
        target.style.transform = `scale(${currentScale})`;
    }
    if (badge) {
        badge.textContent = `${Math.round(currentScale * 100)}%`;
    }
}

function changeZoom(delta) {
    currentScale = Math.min(maxScale, Math.max(minScale, parseFloat((currentScale + delta).toFixed(2))));
    renderZoom();
}

function setExactZoom(val) {
    currentScale = val;
    renderZoom();
}

function resetZoomToFit() {
    const vp = document.getElementById('svg-viewport-box');
    const target = document.getElementById('svg-zoom-wrapper');
    const svg = target ? target.querySelector('svg') : null;
    if (vp && svg) {
        const vpW = Math.max(280, vp.clientWidth - 48);
        const svgW = svg.viewBox?.baseVal?.width || parseFloat(svg.getAttribute('width')) || 780;
        const fitScale = parseFloat((vpW / svgW).toFixed(2));
        currentScale = Math.max(0.4, Math.min(0.85, fitScale));
    } else {
        currentScale = 0.65;
    }
    renderZoom();
    if (vp) {
        vp.scrollTop = 0;
        vp.scrollLeft = 0;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('narrative');
    if (el) {
        el.addEventListener('input', updateCharCount);
        updateCharCount();
    }

    const vp = document.getElementById('svg-viewport-box');
    if (vp) {
        resetZoomToFit();

        // Ctrl + Mouse Wheel Zoom
        vp.addEventListener('wheel', function(e) {
            if (e.ctrlKey) {
                e.preventDefault();
                changeZoom(e.deltaY < 0 ? 0.08 : -0.08);
            }
        }, { passive: false });

        // Drag to pan
        let isDown = false;
        let startX, startY, scrollLeft, scrollTop;

        vp.addEventListener('mousedown', function(e) {
            isDown = true;
            startX = e.pageX - vp.offsetLeft;
            startY = e.pageY - vp.offsetTop;
            scrollLeft = vp.scrollLeft;
            scrollTop = vp.scrollTop;
        });

        vp.addEventListener('mouseleave', function() {
            isDown = false;
        });

        vp.addEventListener('mouseup', function() {
            isDown = false;
        });

        vp.addEventListener('mousemove', function(e) {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - vp.offsetLeft;
            const y = e.pageY - vp.offsetTop;
            vp.scrollLeft = scrollLeft - (x - startX) * 1.3;
            vp.scrollTop = scrollTop - (y - startY) * 1.3;
        });
    }
});
</script>
@endsection
