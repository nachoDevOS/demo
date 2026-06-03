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
            --mp-azul: #1B4F8A;
            --mp-azul-dark: #123566;
            --mp-azul-light: #2563EB;
        }
        body { background: #F0F4F8; font-family: 'Segoe UI', sans-serif; }
        .navbar-mp { background: var(--mp-azul); }
        .navbar-mp .navbar-brand { color: #fff; font-weight: 700; font-size: 1.3rem; letter-spacing: .5px; }
        .navbar-mp .nav-link { color: rgba(255,255,255,.85) !important; }
        .navbar-mp .nav-link:hover, .navbar-mp .nav-link.active { color: #fff !important; }
        .sidebar { background: var(--mp-azul); min-height: calc(100vh - 56px); padding-top: 1.5rem; }
        .sidebar .nav-link { color: rgba(255,255,255,.8); padding: .65rem 1.5rem; border-radius: 0; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,.15); color: #fff; }
        .sidebar .nav-link i { width: 20px; }
        .main-content { padding: 2rem; }
        .card-mp { border: none; box-shadow: 0 2px 8px rgba(0,0,0,.08); border-radius: 10px; }
        .badge-notificacion { background: #1B4F8A; }
        .badge-instructivo  { background: #1D6A3A; }
        .badge-urgente      { background: #B71C1C; }
        .badge-reunion      { background: #E65100; }
        .btn-mp { background: var(--mp-azul); border-color: var(--mp-azul); color: #fff; }
        .btn-mp:hover { background: var(--mp-azul-dark); border-color: var(--mp-azul-dark); color: #fff; }
        .pc-badge { background: #E8F5E9; color: #1D6A3A; border: 1px solid #A5D6A7; border-radius: 20px; padding: 4px 14px; font-size: .85rem; font-weight: 600; }
    </style>
    @stack('styles')
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-mp px-3">
    <a class="navbar-brand" href="{{ route('panel') }}">
        <i class="fas fa-paper-plane me-2"></i>MensaPanel
    </a>
    <div class="ms-auto d-flex align-items-center gap-3">
        <span class="text-white-50 small">
            <i class="fas fa-user-shield me-1"></i>{{ Auth::user()->name ?? '' }}
        </span>
        <form action="{{ route('logout') }}" method="POST" class="d-inline">
            @csrf
            <button class="btn btn-sm btn-outline-light"><i class="fas fa-sign-out-alt"></i> Salir</button>
        </form>
    </div>
</nav>

<div class="d-flex">
    <div class="sidebar d-none d-md-block" style="width:220px; flex-shrink:0;">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('panel') ? 'active' : '' }}" href="{{ route('panel') }}">
                    <i class="fas fa-paper-plane me-2"></i> Enviar Mensaje
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('historial') ? 'active' : '' }}" href="{{ route('historial') }}">
                    <i class="fas fa-history me-2"></i> Historial
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('confirmaciones') ? 'active' : '' }}" href="{{ route('confirmaciones') }}">
                    <i class="fas fa-check-double me-2"></i> Confirmaciones
                </a>
            </li>
        </ul>
    </div>

    <div class="flex-grow-1 main-content">
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
