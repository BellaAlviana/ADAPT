<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Adapt - Activity Diagram Analysis Processing Tools')</title>
    <meta name="description" content="Adapt membantu pembuatan dan pemeriksaan UML Activity Diagram berbasis kaidah baku OMG UML 2.5.1 dan KBBI.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-layout">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="{{ route('home') }}" class="sidebar-brand">
                    <div class="brand-leaf-wrap">
                        <svg class="brand-leaf-icon" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"></path>
                            <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"></path>
                        </svg>
                    </div>
                    <div class="brand-titles">
                        <span class="brand-title">Adapt</span>
                        <span class="brand-sub">Activity Diagram Analysis<br>Processing Tools</span>
                    </div>
                </a>
            </div>

            <!-- Navigation Links -->
            <nav class="sidebar-nav">
                <a href="{{ route('home') }}" class="nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <span>Beranda</span>
                </a>

                <a href="{{ route('generator.index') }}" class="nav-item {{ request()->routeIs('generator.*') ? 'active' : '' }}">
                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"></path>
                    </svg>
                    <span>Generator</span>
                </a>

                <a href="{{ route('auditor.index') }}" class="nav-item {{ request()->routeIs('auditor.*') ? 'active' : '' }}">
                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <circle cx="11.5" cy="14.5" r="2.5"></circle>
                        <path d="m13.3 16.3 2.2 2.2"></path>
                    </svg>
                    <span>Auditor</span>
                </a>

                <a href="{{ route('history') }}" class="nav-item {{ request()->routeIs('history') ? 'active' : '' }}">
                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span>Riwayat Penggunaan AI</span>
                </a>
            </nav>

            <!-- Bottom Botanical Decorative Section -->
            <div class="sidebar-bottom">
                <div class="botanical-art">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2a10 10 0 0 1 10 10c0 5.5-4.5 10-10 10S2 17.5 2 12C2 6.5 6.5 2 12 2m0 2c-4.4 0-8 3.6-8 8 0 3.5 2.3 6.5 5.5 7.5.3-.6.5-1.5.5-2.5 0-3-2-4-2-7 0-2.2 1.8-4 4-4s4 1.8 4 4c0 3-2 4-2 7 0 1 .2 1.9.5 2.5 3.2-1 5.5-4 5.5-7.5 0-4.4-3.6-8-8-8z"></path>
                    </svg>
                </div>
                <p class="sidebar-quote">Analisis lebih cerdas,<br>untuk keputusan yang lebih baik.</p>
            </div>
        </aside>

        <!-- Main Wrapper -->
        <div class="main-wrapper">
            <!-- Topbar -->
            <header class="topbar">
                <div class="topbar-search">
                    <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" placeholder="Cari dokumen atau topik..." class="search-input" id="global-search-input">
                </div>

                <div class="topbar-actions">
                    <button type="button" class="topbar-btn" title="Mode Tampilan" onclick="alert('Tema Adapt menggunakan palet alami Soft Sage & Earthy.')">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="5"></circle>
                            <line x1="12" y1="1" x2="12" y2="3"></line>
                            <line x1="12" y1="21" x2="12" y2="23"></line>
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                            <line x1="1" y1="12" x2="3" y2="12"></line>
                            <line x1="21" y1="12" x2="23" y2="12"></line>
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                        </svg>
                    </button>

                    <div class="user-avatar" title="Akun Pengguna">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                </div>
            </header>

            <!-- Page Main Content -->
            <main class="page-content">
                @if (session('info'))
                    <div class="alert alert-info">
                        <span class="alert-icon">✓</span>
                        <div>{{ session('info') }}</div>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="alert alert-warning">
                        <span class="alert-icon">!</span>
                        <div>{{ session('warning') }}</div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-error">
                        <span class="alert-icon">✕</span>
                        <div>{{ session('error') }}</div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-error">
                        <span class="alert-icon">✕</span>
                        <div>
                            <strong>Periksa kembali masukan Anda:</strong>
                            <ul class="error-list">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
