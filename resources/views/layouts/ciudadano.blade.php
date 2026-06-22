<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('images/favicon.png') }}" />

    <title>Sistema de Ventanilla Única | Salamanca, Guanajuato</title>

    <!-- Hojas de estilo -->
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;900&family=Roboto:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/layouts/ciudadano.js') }}" defer></script>

    <link href="{{ asset('css/layouts/ciudadano.css') }}" rel="stylesheet">
    @yield('css')

</head>

<body class="sb-nav-fixed">
    <!-- Cortinilla de transición -->
    <div id="pageLoader" class="page-loader" aria-hidden="true">
        <div class="page-loader-content">
            <img src="{{ asset('images/escudoBlanco.png') }}" alt="" class="page-loader-logo">
            <h2 class="page-loader-title">Ventanilla Única</h2>
            <p class="page-loader-subtitle">Sistema de Ventanilla Única de Salamanca, Guanajuato.</p>
            <div class="page-loader-spinner"></div>
        </div>
    </div>

    <!-- Navbar -->
    <nav class="sb-topnav navbar navbar-expand navbar-dark">
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Menú usuario -->
        <ul class="navbar-nav ms-auto me-3 me-lg-4">
            <li class="nav-item dropdown">
                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                    onclick="toggleUserDropdown(event)">
                    <i class="fas fa-user"></i> <span class="ms-2">{{ auth()->user()?->name ?? '' }}@if (auth()->user()?->username)
                            ({{ auth()->user()->username }})
                        @endif
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown" id="userDropdownMenu">
                    <li class="user-dropdown-header">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-info">
                            <div class="user-name">{{ auth()->user()?->name ?? 'Usuario' }}@if (auth()->user()?->username)
                                    ({{ auth()->user()->username }})
                                @endif
                            </div>
                            @if (auth()->user()?->email)
                                <div class="user-email">{{ auth()->user()->email }}</div>
                            @endif
                        </div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#">
                            <i class="fas fa-user-circle me-2"></i><span class="highlight-text">{{ __('Mi perfil') }}</span>
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('logout') }}"
                            onclick="event.preventDefault(); confirmarCierreSesion();">
                            <i class="fas fa-sign-out-alt me-2"></i><span
                                class="highlight-text">{{ __('Cerrar sesión') }}</span>
                        </a>
                    </li>
                </ul>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </li>
        </ul>
    </nav>

    <div id="layoutSidenav">
        <!-- Menú lateral -->
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-logo">
                    <img src="{{ asset('images/escudoBlanco.png') }}">
                </div>

                <div class="sb-sidenav-header">
                    <h1>Sistema de Ventanilla Única</h1>
                </div>

                <div class="sb-sidenav-menu">
                    <div class="sb-sidenav-search">
                        <label for="sidebarSectionSearch" class="visually-hidden">Buscar</label>
                        <div class="sb-sidenav-search-box">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <input type="text" id="sidebarSectionSearch" class="sb-sidenav-search-input"
                                placeholder="Buscar..." autocomplete="off">
                            <button type="button" class="sb-sidenav-search-clear" id="sidebarSectionSearchClear"
                                aria-label="Limpiar búsqueda">
                                <i class="fas fa-times" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="nav">
                        <!-- Inicio -->
                        <a class="nav-link active {{ request()->routeIs('home') ? 'active-current' : '' }}"
                            href="{{ route('home') }}">
                            <div class="sb-nav-link-icon"><i class="fa-solid fa-house"></i></div>
                            Inicio
                        </a>
                    </div>
                </div>
            </nav>
        </div>

        <!-- Contenido principal -->
        <div id="layoutSidenav_content">
            <main>
                @yield('content')
            </main>

            <!-- Pie de página -->
            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">&copy; {{ date('Y') }} Salamanca, Guanajuato</div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    @php
        $adminConfig = [
            'csrfToken' => csrf_token(),
            'flashSuccess' => session('success', ''),
        ];
    @endphp
    <script>
        window.adminConfig = @json($adminConfig);
    </script>
    @include('modalColores')

    @yield('scripts')
    @stack('scripts')

</body>

</html>
