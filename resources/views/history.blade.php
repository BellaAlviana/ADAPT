@extends('layouts.app')

@section('title', 'Riwayat Penggunaan AI | Adapt')

@section('content')
<div class="dashboard-grid">
    <!-- Main Left Column -->
    <div class="dashboard-main">
        <div class="page-title-box">
            <span class="mode-pill pill-neutral">DATABASE TERSIMPAN</span>
            <h1 class="serif-page-title">Riwayat Penggunaan AI</h1>
            <p class="page-subtitle">Daftar seluruh draf Activity Diagram dan laporan audit yang telah diproses dan tersimpan di database MySQL.</p>
        </div>

        <!-- Section: Riwayat Generator -->
        <section class="history-block">
            <div class="history-block-head">
                <div class="card-top-icon icon-sage" style="width: 32px; height: 32px; font-size: 13px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="serif-sub-title">Riwayat Perancangan (Generator)</h2>
                    <p class="history-sub-text">Draf Activity Diagram yang dihasilkan dari narasi teks.</p>
                </div>
            </div>

            @if ($generations && $generations->count() > 0)
                <div class="history-cards-list">
                    @foreach ($generations as $gen)
                    <div class="history-item-card">
                        <div class="item-card-header">
                            <span class="status-chip {{ $gen->status === 'success' ? 'chip-success' : 'chip-warning' }}">
                                {{ $gen->status === 'success' ? '✓ Berhasil Disusun' : 'Perlu Klarifikasi' }}
                            </span>
                            <span class="item-date">{{ $gen->created_at->format('d M Y, H:i') }}</span>
                        </div>

                        <p class="item-narrative">{{ Str::limit($gen->narrative, 140) }}</p>

                        <div class="item-actions">
                            <a href="{{ route('generator.show', $gen->id) }}" class="btn-item-open">
                                <span>Buka Diagram</span>
                                <span>→</span>
                            </a>
                            @if ($gen->status === 'success' && $gen->xml_content)
                            <a href="{{ route('generator.download', $gen->id) }}" class="btn-item-download">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                <span>Unduh .drawio</span>
                            </a>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state-box">
                    <p>Belum ada riwayat perancangan diagram. Silakan coba membuat diagram di halaman Generator.</p>
                </div>
            @endif
        </section>

        <!-- Section: Riwayat Auditor -->
        <section class="history-block" style="margin-top: 36px;">
            <div class="history-block-head">
                <div class="card-top-icon icon-terracotta" style="width: 32px; height: 32px; font-size: 13px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11.5" cy="14.5" r="2.5"></circle>
                        <path d="m13.3 16.3 2.2 2.2"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="serif-sub-title">Riwayat Pemeriksaan (Auditor)</h2>
                    <p class="history-sub-text">Hasil audit gambar diagram dan rekomendasi perbaikan.</p>
                </div>
            </div>

            @if ($audits && $audits->count() > 0)
                <div class="history-cards-list">
                    @foreach ($audits as $audit)
                    <div class="history-item-card">
                        <div class="item-card-header">
                            <span class="status-chip chip-success">✓ Selesai Diaudit</span>
                            <span class="item-date">{{ $audit->created_at->format('d M Y, H:i') }}</span>
                        </div>

                        <p class="item-narrative">{{ Str::limit($audit->narrative, 140) }}</p>

                        <div class="item-actions">
                            <a href="{{ route('auditor.show', $audit->id) }}" class="btn-item-open">
                                <span>Buka Laporan Audit</span>
                                <span>→</span>
                            </a>
                            @if ($audit->revised_xml_content)
                            <a href="{{ route('auditor.download', $audit->id) }}" class="btn-item-download">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                <span>Unduh Revisi .drawio</span>
                            </a>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state-box">
                    <p>Belum ada riwayat audit diagram. Silakan coba mengunggah diagram di halaman Auditor.</p>
                </div>
            @endif
        </section>
    </div>

    <!-- Right Sidebar Panel -->
    <aside class="dashboard-sidebar">
        <div class="about-card">
            <div class="about-header">
                <div class="about-icon-wrap">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                        <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                        <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                    </svg>
                </div>
                <h3 class="about-title">Penyimpanan Terpusat</h3>
            </div>

            <p class="about-desc">
                Seluruh data perancangan dan audit disimpan secara aman di database <strong>MySQL (adapt_db)</strong> sehingga dapat ditinjau kapan saja tanpa kehilangan draf.
            </p>

            <div class="feature-bullets">
                <div class="bullet-item">
                    <div class="bullet-icon-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                    </div>
                    <div class="bullet-text">
                        <span class="bullet-title">Tersimpan Permanen</span>
                        <span class="bullet-sub">Berkas Draw.io XML dan riwayat evaluasi aman.</span>
                    </div>
                </div>

                <div class="bullet-item">
                    <div class="bullet-icon-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path><polyline points="16 6 12 2 8 6"></polyline><line x1="12" y1="2" x2="12" y2="15"></line></svg>
                    </div>
                    <div class="bullet-text">
                        <span class="bullet-title">Ekspor Kapan Saja</span>
                        <span class="bullet-sub">Unduh kembali file .drawio kapan saja dibutuhkan.</span>
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
@endsection
