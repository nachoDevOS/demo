<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MensaPanel') — Sistema de Mensajería</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --mp-azul:       #1B4F8A;
            --mp-azul-dark:  #123566;
            --mp-azul-light: #2563EB;
            --mp-sidebar-w:  230px;
        }

        * { box-sizing: border-box; }

        body {
            background: #EEF2F8;
            font-family: 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
        }

        /* ── Navbar ─────────────────────────────────────────── */
        .navbar-mp {
            background: var(--mp-azul);
            height: 58px;
            box-shadow: 0 2px 12px rgba(0,0,0,.18);
            z-index: 100;
        }
        .navbar-mp .navbar-brand {
            color: #fff;
            font-weight: 700;
            font-size: 1.15rem;
            letter-spacing: .4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .navbar-mp .brand-icon {
            background: rgba(255,255,255,.18);
            border-radius: 8px;
            width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            font-size: .9rem;
        }
        .navbar-mp .user-chip {
            background: rgba(255,255,255,.12);
            color: rgba(255,255,255,.9);
            border-radius: 20px;
            padding: 4px 12px;
            font-size: .8rem;
            display: flex; align-items: center; gap: 6px;
        }
        .btn-salir {
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.25);
            color: #fff;
            border-radius: 8px;
            padding: 4px 14px;
            font-size: .82rem;
            transition: all .2s;
        }
        .btn-salir:hover {
            background: rgba(255,255,255,.22);
            color: #fff;
        }

        /* ── Sidebar ─────────────────────────────────────────── */
        .sidebar {
            background: var(--mp-azul);
            width: var(--mp-sidebar-w);
            min-height: calc(100vh - 58px);
            flex-shrink: 0;
            padding: 1.2rem 0 1.5rem;
            display: flex;
            flex-direction: column;
        }
        .sidebar-section-label {
            color: rgba(255,255,255,.35);
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            padding: 0 1.4rem .5rem;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.72);
            padding: .62rem 1.4rem;
            margin: 1px .6rem;
            border-radius: 8px;
            font-size: .88rem;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all .18s;
            position: relative;
        }
        .sidebar .nav-link i {
            width: 18px;
            text-align: center;
            font-size: .85rem;
        }
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,.1);
            color: #fff;
        }
        .sidebar .nav-link.active {
            background: rgba(255,255,255,.18);
            color: #fff;
            font-weight: 600;
        }
        .sidebar .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0; top: 6px; bottom: 6px;
            width: 3px;
            background: #fff;
            border-radius: 0 3px 3px 0;
        }
        .sidebar-divider {
            border-top: 1px solid rgba(255,255,255,.1);
            margin: .8rem .6rem;
        }
        .sidebar-bottom {
            margin-top: auto;
            padding: 0 1.4rem;
            color: rgba(255,255,255,.3);
            font-size: .72rem;
        }

        /* ── Contenido ───────────────────────────────────────── */
        .main-content { padding: 2rem 2.2rem; }

        /* ── Cards ───────────────────────────────────────────── */
        .card-mp {
            border: none;
            border-radius: 14px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.06);
            background: #fff;
        }
        .card-mp .card-header {
            border-radius: 14px 14px 0 0 !important;
        }

        /* ── Badges tipo ─────────────────────────────────────── */
        .badge-notificacion { background: #1B4F8A; }
        .badge-instructivo  { background: #1D6A3A; }
        .badge-urgente      { background: #B71C1C; }
        .badge-reunion      { background: #E65100; }

        /* ── PC badge ────────────────────────────────────────── */
        .pc-badge {
            background: #E8F5E9;
            color: #1D6A3A;
            border: 1px solid #A5D6A7;
            border-radius: 20px;
            padding: 4px 14px;
            font-size: .8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* ── Botón principal ─────────────────────────────────── */
        .btn-mp {
            background: linear-gradient(135deg, #1B4F8A 0%, #2563EB 100%);
            border: none;
            color: #fff;
            border-radius: 10px;
            font-weight: 600;
            transition: all .2s;
            box-shadow: 0 4px 14px rgba(27,79,138,.35);
        }
        .btn-mp:hover {
            background: linear-gradient(135deg, #123566 0%, #1B4F8A 100%);
            color: #fff;
            box-shadow: 0 6px 18px rgba(27,79,138,.45);
            transform: translateY(-1px);
        }
        .btn-mp:active { transform: translateY(0); }
    </style>
    @stack('styles')
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-mp px-3">
    <a class="navbar-brand" href="{{ route('panel') }}">
        <span class="brand-icon"><i class="fas fa-paper-plane"></i></span>
        MensaPanel
    </a>
    <div class="ms-auto d-flex align-items-center gap-2">
        <span class="user-chip">
            <i class="fas fa-user-circle"></i>{{ Auth::user()->name ?? '' }}
        </span>
        <form action="{{ route('logout') }}" method="POST" class="d-inline">
            @csrf
            <button class="btn-salir"><i class="fas fa-sign-out-alt me-1"></i>Salir</button>
        </form>
    </div>
</nav>

<div class="d-flex">
    <div class="sidebar d-none d-md-flex">
        <p class="sidebar-section-label">Menú</p>
        <ul class="nav flex-column w-100">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('panel') ? 'active' : '' }}" href="{{ route('panel') }}">
                    <i class="fas fa-paper-plane"></i> Enviar Mensaje
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('historial') ? 'active' : '' }}" href="{{ route('historial') }}">
                    <i class="fas fa-history"></i> Historial
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('confirmaciones') ? 'active' : '' }}" href="{{ route('confirmaciones') }}">
                    <i class="fas fa-check-double"></i> Confirmaciones
                </a>
            </li>
        </ul>
        <div class="sidebar-divider"></div>
        <p class="sidebar-section-label" style="margin-top:.4rem;">Configuración</p>
        <ul class="nav flex-column w-100">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('tipos.*') ? 'active' : '' }}" href="{{ route('tipos.index') }}">
                    <i class="fas fa-tags"></i> Tipos de mensaje
                </a>
            </li>
        </ul>
        <div class="sidebar-divider"></div>
        <div class="sidebar-bottom">v1.0 &nbsp;·&nbsp; MensaPanel</div>
    </div>

    <div class="flex-grow-1 main-content">
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
