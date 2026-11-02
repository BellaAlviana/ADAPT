@extends('layouts.app')

@section('title', 'Logic Auditor | Adapt')

@section('content')
<div class="dashboard-grid">
    <!-- Main Left Column -->
    <div class="dashboard-main">
        <div class="page-title-box">
            <span class="mode-pill pill-terracotta">MODE 02</span>
            <h1 class="serif-page-title">Dual-Channel Logic Auditor</h1>
            <p class="page-subtitle">Unggah gambar Activity Diagram dan masukkan narasi proses acuan untuk mengaudit kesesuaian logika dan notasi baku UML.</p>
        </div>

        <!-- Form Card (Styled with Soft Terracotta Theme) -->
        <section class="card-terracotta-form">
            <form action="{{ route('auditor.audit') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="auditor-input-grid">
                    <!-- Channel 1: Visual Image -->
                    <div class="form-group-custom">
                        <div class="form-label-row">
                            <label for="diagram" class="custom-label">
                                <span class="channel-tag tag-terracotta">Kanal 1 (Visual)</span>
                                Gambar Activity Diagram
                            </label>
                        </div>

                        <div class="file-dropzone">
                            <input
                                type="file"
                                id="diagram"
                                name="diagram"
                                accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp"
                                required
                                onchange="previewUploadImage(event)"
                            >
                            <div class="dropzone-content">
                                <div class="dropzone-icon">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <polyline points="21 15 16 10 5 21"></polyline>
                                    </svg>
                                </div>
                                <span id="file-chosen-text" class="dropzone-text">Pilih atau seret berkas gambar (PNG, JPG, WEBP maks. 5 MB)</span>
                            </div>
                        </div>

                        <div id="image-thumb-preview" class="thumb-preview-box" style="display: none;">
                            <img id="thumb-img" src="" alt="Pratinjau Diagram">
                        </div>
                    </div>

                    <!-- Channel 2: Textual Narrative -->
                    <div class="form-group-custom">
                        <div class="form-label-row">
                            <label for="narrative" class="custom-label">
                                <span class="channel-tag tag-terracotta">Kanal 2 (Tekstual)</span>
                                Narasi Proses yang Seharusnya
                            </label>
                            <span class="char-hint">Min. 15 karakter</span>
                        </div>

                        <div class="preset-pill-row">
                            <span class="preset-title">Contoh Narasi:</span>
                            <button type="button" class="preset-chip" onclick="loadAuditSample(1)">Alur Pembayaran</button>
                            <button type="button" class="preset-chip" onclick="loadAuditSample(2)">Alur Tiket Masuk</button>
                        </div>

                        <textarea
                            id="narrative"
                            name="narrative"
                            rows="5"
                            minlength="15"
                            maxlength="10000"
                            required
                            placeholder="Jelaskan proses bisnis atau skenario yang seharusnya digambarkan oleh diagram tersebut..."
                        >{{ old('narrative', $currentAudit->narrative ?? '') }}</textarea>
                        <small class="hint-kbbi">Narasi ini digunakan untuk memverifikasi kebenaran alur dan percabangan pada gambar diagram.</small>
                    </div>
                </div>

                <div class="form-btn-row">
                    <button type="submit" class="pill-btn-action btn-terracotta">
                        <span>Mulai Analisis</span>
                        <span class="arrow-right">→</span>
                    </button>
                </div>
            </form>
        </section>

        <!-- Audit Results Section -->
        @if (isset($currentAudit) && $currentAudit)
        <section class="result-block">
            <div class="result-box">
                <div class="result-top-bar">
                    <div>
                        <div class="status-chip chip-success">✓ Pemeriksaan Selesai</div>
                        <h2 class="serif-card-title">Laporan Hasil Pemeriksaan Diagram</h2>
                        <p class="result-sub">Temuan diidentifikasi berdasarkan komparasi gambar diagram, narasi, <strong>Modul ITK SI2514022 (Week 2: Activity Diagram)</strong>, dan standar <strong>OMG UML 2.5.1</strong>.</p>
                    </div>

                    @if (!empty($currentAudit->revised_xml_content))
                    <div class="result-btn-wrap">
                        <a href="{{ route('auditor.download', $currentAudit->id) }}" class="pill-btn-action btn-terracotta">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            <span>Unduh XML Revisi (.drawio)</span>
                        </a>
                    </div>
                    @endif
                </div>

                <!-- Findings List Cards -->
                <div class="findings-wrapper">
                    <div class="findings-top">
                        <h3 class="serif-sub-title">Daftar Rekomendasi & Sitasi Aturan</h3>
                        <span class="count-badge">{{ count($currentAudit->findings ?? []) }} Butir Temuan</span>
                    </div>

                    @if (!empty($currentAudit->findings))
                        <div class="findings-stack">
                            @foreach ($currentAudit->findings as $idx => $f)
                            <div class="finding-card-item">
                                <div class="finding-card-header">
                                    <span class="badge-num">#{{ $idx + 1 }}</span>
                                    <span class="badge-category">{{ strtoupper(str_replace('_', ' ', $f['category'] ?? 'UML RULE')) }}</span>
                                    <span class="badge-target">{{ $f['location'] ?? 'Diagram' }}</span>
                                </div>

                                <div class="finding-card-body">
                                    <div class="finding-desc-row">
                                        <span class="desc-label">Temuan / Bukti:</span>
                                        <p class="desc-text">{{ $f['evidence'] }}</p>
                                    </div>

                                    @if (!empty($f['rule_citation']))
                                    <div class="citation-callout">
                                        <div class="citation-header">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                                            <span>Rujukan Resmi (RAG Grounding):</span>
                                        </div>
                                        <p class="citation-content">{{ $f['rule_citation'] }}</p>
                                    </div>
                                    @endif

                                    <div class="recommendation-callout">
                                        <span class="rec-title">Saran Perbaikan:</span>
                                        <p class="rec-content">{{ $f['recommendation'] }}</p>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state-box">
                            <p>Diagram telah memenuhi kaidah logika proses dan standar UML yang diperiksa.</p>
                        </div>
                    @endif
                </div>

                <!-- Revised Visual Diagram Preview with Zoom Controls -->
                @if (isset($revisedSvg) && $revisedSvg)
                <div class="canvas-preview-card" style="margin-top: 28px;">
                    <div class="canvas-toolbar">
                        <div class="canvas-title-group">
                            <span class="canvas-title">Visualisasi Diagram Hasil Revisi (Draw.io Clean White Format)</span>
                            <span class="canvas-note">Skala otomatis pas layar. Gunakan tombol zoom untuk memperbesar/memperkecil atau geser (drag).</span>
                        </div>
                        <div class="canvas-zoom-controls">
                            <button type="button" class="btn-zoom" onclick="changeAuditZoom(-0.15)" title="Perkecil (Zoom Out)">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            </button>
                            <span class="zoom-level-badge" id="audit-zoom-badge">65%</span>
                            <button type="button" class="btn-zoom" onclick="changeAuditZoom(0.15)" title="Perbesar (Zoom In)">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            </button>
                            <button type="button" class="btn-zoom btn-zoom-action" onclick="resetAuditZoom()" title="Sesuaikan Ukuran Optimal">
                                Fit View
                            </button>
                            <button type="button" class="btn-zoom btn-zoom-action" onclick="setExactAuditZoom(1.0)" title="Ukuran Asli 100%">
                                100%
                            </button>
                        </div>
                    </div>

                    <div class="svg-viewport" id="audit-svg-viewport">
                        <div class="svg-zoom-target" id="audit-svg-zoom-wrapper">
                            {!! $revisedSvg !!}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </section>
        @endif
    </div>

    <!-- Right Sidebar Panel: Information Guide -->
    <aside class="dashboard-sidebar">
        <div class="about-card">
            <div class="about-header">
                <div class="about-icon-wrap icon-terracotta">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                </div>
                <h3 class="about-title">Dokumen Rujukan Audit</h3>
            </div>

            <p class="about-desc">
                Pemeriksaan mengacu pada <strong>Modul SI2514022 ITK (Week 2: Activity Diagram)</strong>, <strong>OMG UML 2.5.1</strong>, dan <strong>KBBI</strong>:
            </p>

            <div class="feature-bullets">
                <div class="bullet-item">
                    <div class="bullet-icon-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 22 12 12 22 2 12 12 2"></polygon></svg>
                    </div>
                    <div class="bullet-text">
                        <span class="bullet-title">Decision Diamond Kosong (Slide 7)</span>
                        <span class="bullet-sub">Belah ketupat tidak diisi teks nama aktivitas; guard `[kondisi]` pada garis keluar.</span>
                    </div>
                </div>

                <div class="bullet-item">
                    <div class="bullet-icon-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5"></path></svg>
                    </div>
                    <div class="bullet-text">
                        <span class="bullet-title">Merge Node "OR" (Slide 8)</span>
                        <span class="bullet-sub">Menyatukan alur alternatif tanpa menunggu semua cabang selesai (beda dari Join).</span>
                    </div>
                </div>

                <div class="bullet-item">
                    <div class="bullet-icon-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="2" rx="1"></rect></svg>
                    </div>
                    <div class="bullet-text">
                        <span class="bullet-title">Konkurensi Fork/Join (Slide 9)</span>
                        <span class="bullet-sub">Balok sinkronisasi untuk memodelkan proses paralel yang berlangsung simultan.</span>
                    </div>
                </div>

                <div class="bullet-item">
                    <div class="bullet-icon-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                    </div>
                    <div class="bullet-text">
                        <span class="bullet-title">Kaidah Verba Aktif (Slide 6 & KBBI)</span>
                        <span class="bullet-sub">Memastikan kotak aktivitas diawali frasa kata kerja aktif transitif berawalan <em>me-</em>.</span>
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
function loadAuditSample(type) {
    const textarea = document.getElementById('narrative');
    if (type === 1) {
        textarea.value = "Pelanggan memilih menu dan melakukan pembayaran. Sistem memeriksa status pembayaran. Jika berhasil, pesanan diteruskan ke dapur. Jika gagal, pesanan dibatalkan.";
    } else if (type === 2) {
        textarea.value = "Petugas memvalidasi tiket masuk. Setelah tiket dinyatakan valid, sistem secara bersamaan mencatat waktu kedatangan pengunjung dan memperbarui log kunjungan. Setelah kedua proses selesai, pengunjung dipersilakan masuk.";
    }
}

function previewUploadImage(event) {
    const input = event.target;
    const label = document.getElementById('file-chosen-text');
    const container = document.getElementById('image-thumb-preview');
    const img = document.getElementById('thumb-img');

    if (input.files && input.files[0]) {
        const file = input.files[0];
        label.textContent = file.name + " (" + (file.size / 1024).toFixed(1) + " KB)";

        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            container.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

// Auditor Diagram Zoom & Pan Engine
let currentAuditScale = 0.65;
const minAuditScale = 0.25;
const maxAuditScale = 2.2;

function renderAuditZoom() {
    const target = document.getElementById('audit-svg-zoom-wrapper');
    const badge = document.getElementById('audit-zoom-badge');
    if (target) {
        target.style.transform = `scale(${currentAuditScale})`;
    }
    if (badge) {
        badge.textContent = `${Math.round(currentAuditScale * 100)}%`;
    }
}

function changeAuditZoom(delta) {
    currentAuditScale = Math.min(maxAuditScale, Math.max(minAuditScale, parseFloat((currentAuditScale + delta).toFixed(2))));
    renderAuditZoom();
}

function setExactAuditZoom(val) {
    currentAuditScale = val;
    renderAuditZoom();
}

function resetAuditZoom() {
    const vp = document.getElementById('audit-svg-viewport');
    const target = document.getElementById('audit-svg-zoom-wrapper');
    const svg = target ? target.querySelector('svg') : null;
    if (vp && svg) {
        const vpW = Math.max(280, vp.clientWidth - 48);
        const svgW = svg.viewBox?.baseVal?.width || parseFloat(svg.getAttribute('width')) || 780;
        const fitScale = parseFloat((vpW / svgW).toFixed(2));
        currentAuditScale = Math.max(0.4, Math.min(0.85, fitScale));
    } else {
        currentAuditScale = 0.65;
    }
    renderAuditZoom();
    if (vp) {
        vp.scrollTop = 0;
        vp.scrollLeft = 0;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const vp = document.getElementById('audit-svg-viewport');
    if (vp) {
        resetAuditZoom();

        // Ctrl + Wheel Zoom
        vp.addEventListener('wheel', function(e) {
            if (e.ctrlKey) {
                e.preventDefault();
                changeAuditZoom(e.deltaY < 0 ? 0.08 : -0.08);
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
