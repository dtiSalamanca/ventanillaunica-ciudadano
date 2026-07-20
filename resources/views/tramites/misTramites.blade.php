@extends('layouts.ciudadano')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/tramites/misTramites.css') }}">
@endsection

@section('content')
    <div class="main-container">
        {{-- Header --}}
        <div class="page-header">
            <div class="header-content">
                <img src="{{ asset('images/escudoBlanco.png') }}" alt="Escudo de Salamanca" class="header-escudo">

                <div class="header-main">
                    <h1 class="page-title">Mis trámites</h1>
                    <p class="page-subtitle">Consulta el estado de tus solicitudes de trámite.</p>
                </div>
            </div>
        </div>

        {{-- Búsqueda --}}
        <div class="mis-tramites-search">
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="misTramites-search-input" placeholder="Buscar por nombre de trámite..."
                    autocomplete="off">
                <button type="button" id="misTramites-search-clear" class="search-clear" title="Limpiar búsqueda"
                    style="display: none">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        @if ($solicitudes->isEmpty())
            <div class="empty-state">
                <i class="fa-solid fa-inbox"></i>
                <p>Aún no has realizado ninguna solicitud de trámite.</p>
                <a href="{{ route('indexTramites') }}" class="btn-ir-tramites">
                    <i class="fa-solid fa-file-invoice"></i> Ver trámites disponibles
                </a>
            </div>
        @else
            <div class="mis-tramites-grid">
                @foreach ($solicitudes as $solicitud)
                    @php
                        $estatusTexto = match ($solicitud->estatus_solicitud) {
                            0 => 'Pendiente',
                            1 => 'Aprobada',
                            2 => 'Rechazada',
                            default => 'Desconocido',
                        };
                        $estatusClase = match ($solicitud->estatus_solicitud) {
                            0 => 'estatus--pendiente',
                            1 => 'estatus--aprobada',
                            2 => 'estatus--rechazada',
                            default => 'estatus--desconocido',
                        };
                        $estatusIcono = match ($solicitud->estatus_solicitud) {
                            0 => 'fa-solid fa-clock',
                            1 => 'fa-solid fa-circle-check',
                            2 => 'fa-solid fa-circle-xmark',
                            default => 'fa-solid fa-circle-question',
                        };
                    @endphp

                    <div class="solicitud-card" data-nombre="{{ mb_strtolower($solicitud->tramite->nombre_tramite) }}">
                        <div class="solicitud-card-header">
                            <div class="solicitud-card-icono">
                                <i class="fas fa-file-invoice"></i>
                            </div>

                            <div class="solicitud-card-info">
                                <div class="solicitud-card-nombre">{{ $solicitud->tramite->nombre_tramite }}</div>
                                <div class="solicitud-card-dependencia">
                                    <i class="fa-solid fa-building me-1"></i>
                                    <span class="solicitud-card-dependencia-label">Dependencia:</span>
                                    {{ $solicitud->tramite->dependencia?->nombre_dependencia ?? 'Sin dependencia' }}
                                </div>
                            </div>

                            <span class="badge-estatus {{ $estatusClase }}">
                                <i class="{{ $estatusIcono }} me-1"></i>{{ $estatusTexto }}
                            </span>
                        </div>

                        <div class="solicitud-card-body">
                            <div class="solicitud-card-detalle">
                                <div class="solicitud-card-detalle-item">
                                    <span class="detalle-label">Fecha de solicitud</span>
                                    <span
                                        class="detalle-valor">{{ $solicitud->fecha_solicitud?->format('d/m/Y H:i') ?? '—' }}</span>
                                </div>

                                @if ($solicitud->predio)
                                    <div class="solicitud-card-detalle-item">
                                        <span class="detalle-label">Predio</span>
                                        <span class="detalle-valor">
                                            <i class="fa-solid fa-map-pin me-1"></i>
                                            {{ $solicitud->predio->clave_predio }}
                                        </span>
                                    </div>
                                @endif

                                <div class="solicitud-card-detalle-item">
                                    <span class="detalle-label">Precio del trámite</span>
                                    <span
                                        class="detalle-valor detalle-valor--precio">${{ number_format($solicitud->tramite->precio_tramite, 2) }}</span>
                                </div>

                                @if ($solicitud->fecha_resolucion)
                                    <div class="solicitud-card-detalle-item">
                                        <span class="detalle-label">Fecha de resolución</span>
                                        <span
                                            class="detalle-valor">{{ $solicitud->fecha_resolucion?->format('d/m/Y H:i') ?? '—' }}</span>
                                    </div>
                                @endif
                            </div>

                            @if ($solicitud->observacion_solicitud)
                                <div class="solicitud-card-observacion">
                                    <i class="fa-solid fa-quote-left solicitud-card-observacion-icono"></i>
                                    <p>{{ $solicitud->observacion_solicitud }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="solicitud-card-footer">
                            <span class="solicitud-card-id">Solicitud #{{ $solicitud->id_solicitud }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="empty-state empty-state-filtro" hidden>
                <i class="fa-solid fa-magnifying-glass"></i>
                <p>No se encontraron solicitudes con el filtro aplicado.</p>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/tramites/misTramites.js') }}" defer></script>
@endsection
