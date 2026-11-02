@extends('layouts.app')

@section('title', 'Beranda | Adapt - Activity Diagram Analysis Processing Tools')

@section('content')
<div class="dashboard-grid">
    <!-- Left / Main Area -->
    <div class="dashboard-main">
        <!-- Hero Banner matching Mockup -->
        <section class="hero-banner">
            <div class="hero-content">
                <span class="hero-intro">Selamat datang di</span>
                <h1 class="hero-title">Adapt</h1>
                <p class="hero-subtitle">Activity Diagram Analysis Processing Tools</p>
                <p class="hero-desc">
                    Analisis narasi proses dengan cerdas dan cepat, untuk menyusun rancangan Activity Diagram yang presisi dan dapat diuji.
                </p>
            </div>

            <!-- Flowchart Mockup Illustration matching Mockup -->
            <div class="hero-illustration">
                <div class="float-star star-1">✦</div>
                <div class="float-star star-2">✦</div>
                <div class="diagram-phone-card">
                    <div class="phone-bar"></div>
                    <div class="phone-screen">
                        <div class="screen-node node-rect-top"></div>
                        <div class="screen-line line-1"></div>
                        <div class="screen-node node-diamond"></div>
                        <div class="screen-line line-2"></div>
                        <div class="screen-node node-rect-bottom"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section: Pilih Mode -->
        <section class="mode-section">
            <div class="section-title-wrap">
                <h2 class="serif-heading">Pilih Mode</h2>
                <p class="section-sub">Mulai analisis sesuai kebutuhan Anda.</p>
            </div>

            <div class="mode-cards-grid">
                <!-- Mode 1: Generator (Soft Sage Card) -->
                <div class="mode-card card-sage">
                    <div class="card-top-icon icon-sage">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <path d="m9 15 2 2 4-4"></path>
                        </svg>
                    </div>

                    <h3 class="card-title">Generator</h3>
                    <p class="card-desc">
                        Membantu membuat rancangan diagram, partisi swimlanes, dan kode Draw.io XML berdasarkan dokumen narasi proses yang Anda masukkan.
                    </p>

                    <div class="card-action">
                        <a href="{{ route('generator.index') }}" class="pill-btn-action btn-sage">
                            <span>Mulai Analisis</span>
                            <span class="arrow-right">→</span>
                        </a>
                    </div>

                    <!-- Subtle leaf watermark -->
                    <div class="card-watermark watermark-sage">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Mode 2: Auditor (Soft Terracotta Card) -->
                <div class="mode-card card-terracotta">
                    <div class="card-top-icon icon-terracotta">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <circle cx="11.5" cy="14.5" r="2.5"></circle>
                            <path d="m13.3 16.3 2.2 2.2"></path>
                        </svg>
                    </div>

                    <h3 class="card-title">Auditor</h3>
                    <p class="card-desc">
                        Memeriksa kesesuaian gambar diagram dengan standar kaidah notasi UML dan narasi proses yang telah ditentukan.
                    </p>

                    <div class="card-action">
                        <a href="{{ route('auditor.index') }}" class="pill-btn-action btn-terracotta">
                            <span>Mulai Analisis</span>
                            <span class="arrow-right">→</span>
                        </a>
                    </div>

                    <!-- Subtle leaf watermark -->
                    <div class="card-watermark watermark-terracotta">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Right Sidebar Panel: "Tentang Sistem Ini" matching Mockup -->
    <aside class="dashboard-sidebar">
        <div class="about-card">
            <div class="about-header">
                <div class="about-icon-wrap">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"></path>
                    </svg>
                </div>
                <h3 class="about-title">Tentang Sistem Ini</h3>
            </div>

            <p class="about-desc">
                Sistem ini membantu Anda menganalisis narasi proses dan dokumen diagram, menyusun Activity Diagram sesuai standar diagrams.net (Draw.io), dengan jawaban akurat dan dapat ditinjau kembali melalui sumber referensi.
            </p>

            <div class="feature-bullets">
                <div class="bullet-item">
                    <div class="bullet-icon-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            <path d="m9 12 2 2 4-4"></path>
                        </svg>
                    </div>
                    <div class="bullet-text">
                        <span class="bullet-title">Berbasis dokumen resmi</span>
                        <span class="bullet-sub">Modul ITK SI2514022 & OMG UML 2.5.1</span>
                    </div>
                </div>

                <div class="bullet-item">
                    <div class="bullet-icon-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                    </div>
                    <div class="bullet-text">
                        <span class="bullet-title">Dilengkapi sumber referensi</span>
                        <span class="bullet-sub">Mudah ditelusuri per aturan</span>
                    </div>
                </div>

                <div class="bullet-item">
                    <div class="bullet-icon-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                        </svg>
                    </div>
                    <div class="bullet-text">
                        <span class="bullet-title">Proses cepat & efisien</span>
                        <span class="bullet-sub">Hemat waktu & bebas tumpang tindih</span>
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
