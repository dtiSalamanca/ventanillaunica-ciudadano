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

        {{-- Búsqueda + filtros --}}
        <div class="mis-tramites-toolbar">
            <div class="mis-tramites-toolbar-top">
                <div class="search-bar">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="misTramites-search-input" placeholder="Buscar por nombre de trámite..."
                        autocomplete="off">
                    <button type="button" id="misTramites-search-clear" class="search-clear" title="Limpiar búsqueda"
                        style="display: none">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <p class="mis-tramites-hint">
                    <i class="fa-solid fa-circle-info"></i>
                    Haz clic en una solicitud para ver los detalles.
                </p>
            </div>

            <div class="mis-tramites-filtros" role="group" aria-label="Filtrar por estatus">
                <button type="button" class="filtro-chip is-active" data-estatus-filtro="todos">
                    <span class="filtro-dot filtro-dot--todos"></span>
                    Todos
                    <span class="filtro-count" data-conteo="todos">0</span>
                </button>
                <button type="button" class="filtro-chip" data-estatus-filtro="0">
                    <span class="filtro-dot filtro-dot--pendiente"></span>
                    Pendientes
                    <span class="filtro-count" data-conteo="0">0</span>
                </button>
                <button type="button" class="filtro-chip" data-estatus-filtro="1">
                    <span class="filtro-dot filtro-dot--turnado"></span>
                    Turnados
                    <span class="filtro-count" data-conteo="1">0</span>
                </button>
                <button type="button" class="filtro-chip" data-estatus-filtro="2">
                    <span class="filtro-dot filtro-dot--rechazada"></span>
                    Rechazados
                    <span class="filtro-count" data-conteo="2">0</span>
                </button>
                <button type="button" class="filtro-chip" data-estatus-filtro="3">
                    <span class="filtro-dot filtro-dot--por-pagar"></span>
                    Por pagar
                    <span class="filtro-count" data-conteo="3">0</span>
                </button>
                <button type="button" class="filtro-chip" data-estatus-filtro="5">
                    <span class="filtro-dot filtro-dot--completado"></span>
                    Completados
                    <span class="filtro-count" data-conteo="5">0</span>
                </button>
                <button type="button" class="filtro-chip" data-estatus-filtro="6">
                    <span class="filtro-dot filtro-dot--expirado"></span>
                    Expirados
                    <span class="filtro-count" data-conteo="6">0</span>
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
                        $ordenPago = $solicitud->ordenPago;
                        $tieneFolio = filled($ordenPago?->folio_pago);

                        // Estado efectivo considerando la vigencia: una solicitud
                        // completada cuya vigencia ya venció se muestra como 6 (Expirado).
                        $estadoMostrado = $solicitud->estadoMostrado();

                        $estatusTexto = match ($estadoMostrado) {
                            0 => 'Pendiente',
                            1 => 'Turnado',
                            2 => 'Rechazado',
                            3 => 'Por pagar',
                            5 => 'Completado',
                            6 => 'Expirado',
                            default => 'Desconocido',
                        };
                        $estatusClase = match ($estadoMostrado) {
                            0 => 'estatus--pendiente',
                            1 => 'estatus--turnado',
                            2 => 'estatus--rechazada',
                            3 => 'estatus--por-pagar',
                            5 => 'estatus--completado',
                            6 => 'estatus--expirado',
                            default => 'estatus--desconocido',
                        };
                        $estatusIcono = match ($estadoMostrado) {
                            0 => 'fa-solid fa-clock',
                            1 => 'fa-solid fa-arrow-right',
                            2 => 'fa-solid fa-circle-xmark',
                            3 => 'fa-solid fa-credit-card',
                            5 => 'fa-solid fa-check-circle',
                            6 => 'fa-solid fa-hourglass-end',
                            default => 'fa-solid fa-circle-question',
                        };

                        // Monto asignado por el enlace al aprobar la solicitud, subir el
                        // resolutivo y designar el precio. Solo se muestra cuando el trámite
                        // está completado y existe un precio asignado (el precio base del
                        // catálogo puede variar, por eso no se usa como respaldo).
                        $precioMostrar = $ordenPago?->precio_tramite;

                        // Nombre del archivo del resolutivo para mostrarlo en la URL.
                        $nombreResolutivo = $solicitud->resolucion?->documento_resolucion
                            ? basename($solicitud->resolucion->documento_resolucion)
                            : null;
                    @endphp

                    <div class="solicitud-card" id="solicitud-{{ $solicitud->id_solicitud }}"
                        data-nombre="{{ mb_strtolower($solicitud->tramite->nombre_tramite) }}"
                        data-estatus="{{ $estadoMostrado }}">
                        <button type="button" class="solicitud-card-header" aria-expanded="false"
                            aria-controls="detalle-{{ $solicitud->id_solicitud }}">
                            <span class="solicitud-card-icono">
                                <i class="fas fa-file-invoice"></i>
                            </span>

                            <span class="solicitud-card-info">
                                <span class="solicitud-card-nombre">{{ $solicitud->tramite->nombre_tramite }}</span>
                                <span class="solicitud-card-dependencia">
                                    <i class="fa-solid fa-building me-1"></i>
                                    <span class="solicitud-card-dependencia-label">Dependencia:</span>
                                    {{ $solicitud->tramite->dependencia?->nombre_dependencia ?? 'Sin dependencia' }}
                                </span>
                            </span>

                            <span class="badge-estatus {{ $estatusClase }}">
                                <i class="{{ $estatusIcono }} me-1"></i>{{ $estatusTexto }}
                            </span>

                            <span class="solicitud-card-chevron" aria-hidden="true">
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </button>

                        <div class="solicitud-card-detalle-wrap" id="detalle-{{ $solicitud->id_solicitud }}">
                            <div class="solicitud-card-detalle-inner">
                                <div class="solicitud-card-body">
                                    <div class="solicitud-card-detalle">
                                        <div class="solicitud-card-detalle-item">
                                            <span class="detalle-label">Fecha de solicitud</span>
                                            <span
                                                class="detalle-valor">{{ $solicitud->fecha_solicitud?->format('d/m/Y h:i A') ?? '—' }}</span>
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

                                        {{-- El precio se muestra cuando hay una orden de pago con monto asignado,
                                             tanto en "Por pagar" (sin folio) como en "Completado" (con folio). --}}
                                        @if ($ordenPago !== null && filled($precioMostrar))
                                            <div class="solicitud-card-detalle-item">
                                                <span class="detalle-label">Precio del trámite</span>
                                                <span
                                                    class="detalle-valor detalle-valor--precio">${{ number_format($precioMostrar, 2) }}</span>
                                            </div>
                                        @endif

                                        @if ($solicitud->fecha_resolucion)
                                            <div class="solicitud-card-detalle-item">
                                                <span class="detalle-label">Fecha de resolución</span>
                                                <span
                                                    class="detalle-valor">{{ $solicitud->fecha_resolucion?->format('d/m/Y h:i A') ?? '—' }}</span>
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

                                    @if ($tieneFolio)
                                        <a href="{{ route('descargarResolutivo', $nombreResolutivo ? [$solicitud, $nombreResolutivo] : [$solicitud]) }}"
                                            class="btn-ver-resolutivo" target="_blank" rel="noopener noreferrer"
                                            title="Ver o descargar el resolutivo de este trámite">
                                            <i class="fa-solid fa-file-pdf"></i>
                                            <span>Ver/Descargar resolutivo</span>
                                        </a>
                                    @endif

                                    {{-- Botón "Generar orden de pago" comentado (en desuso):
                                    @elseif ($ordenPago !== null)
                                        <button type="button" class="btn-orden-pago"
                                            title="Generar orden de pago para este trámite">
                                            <i class="fa-solid fa-file-invoice-dollar"></i>
                                            <span>Generar orden de pago</span>
                                        </button>
                                    --}}
                                </div>
                            </div>
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
