<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <!-- Metadatos básicos -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('images/favicon.png') }}" />

    <title>Sistema de Administración de la Ventanilla Única | Acceso</title>

    <!-- Fuente: Montserrat (estilo profesional y legible) -->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">

    @yield('head')

    <!-- Vite (si tu app lo usa) -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>

<body>
    <div id="app">
        @yield('content')
    </div>

    <!-- Sección para scripts específicos de cada vista -->
    @yield('scripts')
</body>

</html>
