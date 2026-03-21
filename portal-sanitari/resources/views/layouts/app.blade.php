<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Sanitari - @yield('page-title', 'Dashboard')</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #e0f4ff 0%, #d4f0ff 50%, #e4f6ff 100%);
            display: flex;
        }

        /* ── Sidebar ────────────────────────────────────── */
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: #ffffff;
            border-right: 1px solid rgba(200, 225, 255, 0.6);
            box-shadow: 2px 0 16px rgba(0, 80, 160, 0.04);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            transition: width 0.3s ease;
            overflow: hidden;
        }

        .sidebar.collapsed { width: 72px; }
        .sidebar.collapsed .sidebar-brand-text,
        .sidebar.collapsed .nav-section-label,
        .sidebar.collapsed .nav-item-text,
        .sidebar.collapsed .btn-logout-text { display: none; }
        .sidebar.collapsed .sidebar-brand { padding: 16px; justify-content: center; }
        .sidebar.collapsed .sidebar-brand a { display: none; }
        .sidebar.collapsed .nav-item { justify-content: center; padding: 12px; }
        .sidebar.collapsed .sidebar-footer { padding: 12px; }
        .sidebar.collapsed .btn-logout { justify-content: center; padding: 11px; }
        .sidebar.collapsed .btn-logout svg { margin: 0; }

        /* Toggle button */
        .sidebar-collapse-btn {
            width: 36px;
            height: 36px;
            background: transparent;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.2s ease;
            margin-left: auto;
        }
        .sidebar.collapsed .sidebar-collapse-btn {
            margin: 0 auto;
        }
        .sidebar-collapse-btn:hover {
            background: #f0f6ff;
            border-color: #4285f4;
        }
        .sidebar-collapse-btn svg {
            width: 18px;
            height: 18px;
            color: #475569;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 22px 24px 22px 24px;
            flex: 0 0 auto;
            text-decoration: none;
            border-bottom: 1px solid #eef3fa;
        }

        .sidebar-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #eef4ff, #dfe9f8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .sidebar-icon svg {
            width: 22px;
            height: 22px;
            color: #3b6fcc;
        }

        .sidebar-brand-text {
            display: flex;
            flex-direction: column;
        }

        .sidebar-title {
            font-size: 16px;
            font-weight: 700;
            color: #1a2c4e;
            line-height: 1.2;
        }

        .sidebar-subtitle {
            font-size: 11px;
            font-weight: 500;
            color: #5b8cd4;
        }

        /* Nav items */
        .sidebar-nav {
            flex: 1;
            padding: 16px 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .nav-section-label {
            font-size: 10px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 12px 14px 6px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            color: #475569;
            transition: all 0.2s ease;
        }

        .nav-item:hover {
            background: #f0f6ff;
            color: #2563eb;
        }

        .nav-item.active {
            background: linear-gradient(135deg, #eef4ff 0%, #dbeafe 100%);
            color: #2563eb;
            font-weight: 600;
        }

        .nav-item svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        /* Sidebar footer */
        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid #eef3fa;
        }

        .btn-logout {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 11px 16px;
            background: transparent;
            color: #dc2626;
            border: 1.5px solid #fecaca;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            background: #fef2f2;
            border-color: #f87171;
        }

        .btn-logout svg {
            width: 18px;
            height: 18px;
        }

        /* ── Main area ──────────────────────────────────── */
        .main-wrapper {
            margin-left: 260px;
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s ease;
        }

        .sidebar.collapsed ~ .main-wrapper { margin-left: 72px; }

        .topbar {
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(12px);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(200, 225, 255, 0.5);
        }

        .topbar-title {
            font-size: 18px;
            font-weight: 700;
            color: #1a2c4e;
        }

        .topbar-user {
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
        }

        .main-content {
            flex: 1;
            padding: 36px 32px;
        }

        /* ── Cards ──────────────────────────────────────── */
        .content-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 36px 40px 32px;
            box-shadow: 0 4px 24px rgba(0, 100, 200, 0.07), 0 1px 3px rgba(0, 50, 100, 0.04);
            border: 1px solid rgba(200, 225, 255, 0.5);
            max-width: 720px;
            animation: fadeInUp 0.4s ease-out;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .card-header-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 6px;
        }

        .card-header-row svg {
            width: 24px;
            height: 24px;
            color: #334155;
        }

        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: #1a2c4e;
        }

        .card-subtitle {
            font-size: 14px;
            color: #5b8cd4;
            font-weight: 500;
            margin-bottom: 24px;
            padding-left: 36px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #1e2d45;
            margin-bottom: 8px;
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: 14px 18px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: #334155;
            background: #f8fafc;
            transition: all 0.25s ease;
            outline: none;
            -webkit-appearance: none;
            appearance: none;
        }

        .form-select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 44px;
            cursor: pointer;
        }

        .form-input::placeholder { color: #94a3b8; font-weight: 400; }

        .form-input:focus,
        .form-select:focus {
            border-color: #4285f4;
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(66, 133, 244, 0.1);
        }

        .form-input:hover:not(:focus),
        .form-select:hover:not(:focus) {
            border-color: #cbd5e1;
        }

        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4285f4 0%, #3574e2 100%);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.25s ease;
            margin-top: 16px;
            letter-spacing: 0.01em;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            transition: left 0.5s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #3574e2, #2a63cc);
            box-shadow: 0 4px 15px rgba(66, 133, 244, 0.35);
            transform: translateY(-1px);
        }

        .btn-primary:hover::before { left: 100%; }

        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(66, 133, 244, 0.3);
        }

        /* ── Mobile toggle ──────────────────────────────── */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            z-index: 200;
            width: 40px;
            height: 40px;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
        }

        .sidebar-toggle svg { width: 20px; height: 20px; color: #334155; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-toggle { display: flex; }
            .main-wrapper { margin-left: 0; }
            .topbar { padding: 16px 16px 16px 64px; }
            .main-content { padding: 24px 16px; }
            .content-card { padding: 24px 20px 20px; }
        }

        @yield('extra-styles')
    </style>
</head>
<body>

    <!-- mobile toggle -->
    <button class="sidebar-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>

    <!-- ── Sidebar ─────────────────────────────────────── -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <a href="/dashboard" style="display:flex;align-items:center;gap:14px;text-decoration:none;">
                <div class="sidebar-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                </div>
                <div class="sidebar-brand-text">
                    <span class="sidebar-title">Portal Sanitari</span>
                    <span class="sidebar-subtitle">Sistema d'Anàlisi</span>
                </div>
            </a>
            <!-- Hamburger toggle -->
            <button class="sidebar-collapse-btn" id="sidebar-toggle" title="Plegar menú">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
        </div>

        <nav class="sidebar-nav">
            <span class="nav-section-label">Eines</span>

            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <!-- Person icon -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span class="nav-item-text">Anàlisi de Pacient</span>
            </a>


        </nav>

        <div class="sidebar-footer">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-logout">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span class="btn-logout-text">Sortir</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- ── Main ────────────────────────────────────────── -->
    <div class="main-wrapper">
        <header class="topbar">
            <h2 class="topbar-title">@yield('page-title')</h2>
            <span class="topbar-user">👤 {{ Auth::user()->name }}</span>
        </header>

        <main class="main-content">
            @yield('content')
        </main>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebar-toggle');
        if (localStorage.getItem('sidebar-collapsed') === 'true') sidebar.classList.add('collapsed');
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
        });
    </script>
</body>
</html>
