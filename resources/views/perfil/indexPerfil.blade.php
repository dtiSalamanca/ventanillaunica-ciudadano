@extends('layouts.ciudadano')

@section('css')
    <link rel="stylesheet" href="{{ asset('perfil/indexPerfil.css') }}">
@endsection

@section('content')
    <div class="main-container">
        @php
            $porcentaje = $totalDocumentos > 0 ? (int) round(($documentosCompletados / $totalDocumentos) * 100) : 0;
        @endphp

        <div class="page-header">
            <div class="header-content">
                <img src="{{ asset('images/escudoBlanco.png') }}" alt="Escudo de Salamanca" class="header-escudo">
                <div class="header-main">
                    <h1 class="page-title">Mi perfil</h1>
                    <p class="page-subtitle">Consulta tus datos personales y el estatus de tus documentos.</p>
                </div>
            </div>
        </div>

        @if ($totalDocumentos > 0)
            <div class="completeness-card">
                <div class="completeness-card__header">
                    <span class="completeness-card__label">Documentos completados</span>
                    <span class="completeness-card__valor">{{ $porcentaje }}%</span>
                </div>
                <div class="progreso-barra">
                    <div class="progreso-barra-fill" data-progreso="{{ $porcentaje }}"></div>
                </div>
            </div>
        @endif

        <div class="profile-tabs" role="tablist" aria-label="Secciones del perfil">
            <button class="profile-tabs__tab profile-tabs__tab--active" role="tab" aria-selected="true" aria-controls="panel-documentos" id="tab-documentos" type="button">
                <i class="fas fa-clipboard-list"></i> Documentos
            </button>
            <button class="profile-tabs__tab profile-tabs__tab--disabled" role="tab" aria-selected="false" aria-disabled="true" tabindex="-1" title="Próximamente" type="button">
                <i class="fas fa-clock-rotate-left"></i> Historial
            </button>
        </div>

        <div class="profile-tab-content" role="tabpanel" id="panel-documentos" aria-labelledby="tab-documentos">
            <div class="bento-grid">
                <div class="profile-card">
                    <div class="profile-card__avatar" aria-hidden="true">
                        {{ Str::of(auth()->user()->name)->explode(' ')->map(fn ($palabra) => Str::substr($palabra, 0, 1))->take(2)->implode('') }}
                    </div>

                    <h2 class="profile-card__name">{{ auth()->user()->name }}</h2>

                    <div class="profile-card__contact">
                        <div class="profile-card__contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>{{ auth()->user()->email }}</span>
                        </div>
                    </div>

                    @if (auth()->user()->email_verified_at)
                        <span class="badge-verificacion badge-verificacion--ok"><i class="fas fa-circle-check me-1"></i>Correo verificado</span>
                    @else
                        <span class="badge-verificacion badge-verificacion--pendiente"><i class="fas fa-circle-exclamation me-1"></i>Correo sin verificar</span>
                    @endif

                    @if ($totalDocumentos > 0)
                        <div class="profile-card__stats">
                            <div class="profile-stat">
                                <div class="profile-stat__icono"><i class="fas fa-folder"></i></div>
                                <div class="profile-stat__texto">
                                    <span class="profile-stat__valor">{{ $totalDocumentos }}</span>
                                    <span class="profile-stat__label">Total de documentos</span>
                                </div>
                            </div>
                            <div class="profile-stat profile-stat--ok">
                                <div class="profile-stat__icono"><i class="fas fa-circle-check"></i></div>
                                <div class="profile-stat__texto">
                                    <span class="profile-stat__valor">{{ $documentosCompletados }}</span>
                                    <span class="profile-stat__label">Cargados</span>
                                </div>
                            </div>
                            <div class="profile-stat profile-stat--pendiente">
                                <div class="profile-stat__icono"><i class="fas fa-circle-exclamation"></i></div>
                                <div class="profile-stat__texto">
                                    <span class="profile-stat__valor">{{ $totalDocumentos - $documentosCompletados }}</span>
                                    <span class="profile-stat__label">Pendientes</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="documents-card">
                    <div class="documents-card__header">
                        <h2 class="documents-card__title">Documentos requeridos</h2>

                        @if ($totalDocumentos > 0)
                            <div class="documentos-filtro" role="group" aria-label="Filtrar documentos">
                                <button class="filtro-btn filtro-btn--active" data-filtro="todos" type="button">Todos</button>
                                <button class="filtro-btn" data-filtro="cargado" type="button">Cargados</button>
                                <button class="filtro-btn" data-filtro="pendiente" type="button">Pendientes</button>
                            </div>
                        @endif
                    </div>

                    @if ($documentosCatalogo->isEmpty())
                        <p class="mensaje-vacio"><i class="fas fa-circle-info me-1"></i>No hay documentos personales disponibles en este momento.</p>
                    @else
                        <div class="documentos-lista">
                            @foreach ($documentosCatalogo as $documento)
                                @php
                                    $cargado = $documentosCargados->get($documento->id_documento);
                                    $estatusDoc = $cargado ? 'cargado' : 'pendiente';
                                    $fechaVencimiento = $cargado ? $cargado->fecha_registro->copy()->addMonths($documento->vigencia_meses) : null;
                                    $diasRestantes = $fechaVencimiento ? now()->diffInDays($fechaVencimiento, false) : null;
                                    $estatus = $cargado ? (int) $cargado->estatus_documento : null;
                                @endphp
                                <div class="documento-card {{ $cargado ? 'documento-card--cargado' : '' }}" data-estatus="{{ $estatusDoc }}">
                                    <div class="documento-card__icono">
                                        <i class="fas fa-file-lines"></i>
                                    </div>
                                    <div class="documento-card__contenido">
                                        <span class="documento-card__nombre">{{ $documento->nombre_documento }}</span>
                                        <div class="documento-card__meta">
                                            @if (!$cargado || $estatus === 2)
                                                <span class="documento-card__vigencia"><i class="fas fa-hourglass-half me-1"></i>Vigencia: {{ $documento->vigencia_meses }} meses</span>
                                            @endif
                                            @if ($cargado)
                                                <span class="documento-card__fecha"><i class="fas fa-calendar-check me-1"></i>Cargado el {{ $cargado->fecha_registro->format('d/m/Y') }}</span>
                                                @if ($diasRestantes !== null && $diasRestantes <= 0)
                                                    <span class="badge-vigencia badge-vigencia--vencido"><i class="fas fa-triangle-exclamation me-1"></i>Vencido</span>
                                                @elseif ($diasRestantes !== null && $diasRestantes <= 60)
                                                    <span class="badge-vigencia badge-vigencia--por-vencer"><i class="fas fa-clock me-1"></i>Por vencer</span>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                    <div class="documento-card__acciones">
                                        @if ($cargado)
                                            @if ($estatus === 2)
                                                <span class="badge-estatus badge-estatus--aprobado"><i class="fas fa-circle-check me-1"></i>Aprobado</span>
                                            @elseif ($estatus === 1)
                                                <span class="badge-estatus badge-estatus--revision"><i class="fas fa-hourglass-half me-1"></i>En revisión</span>
                                            @else
                                                <span class="badge-estatus badge-estatus--rechazado"><i class="fas fa-circle-xmark me-1"></i>Rechazado</span>
                                            @endif
                                            <a href="{{ route('perfiles.documentos.descargar', $cargado->id_documento) }}"
                                               class="btn-accion btn-accion--ver"
                                               target="_blank"
                                               title="Ver documento">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @else
                                            <span class="badge-estatus badge-estatus--pendiente"><i class="fas fa-circle-exclamation me-1"></i>Pendiente</span>
                                            <form action="{{ route('perfiles.documentos.subir', $documento->id_documento) }}"
                                                  method="POST"
                                                  enctype="multipart/form-data"
                                                  class="form-subir-inline">
                                                @csrf
                                                <input type="file"
                                                       name="archivo"
                                                       class="input-archivo-oculto"
                                                       accept=".pdf"
                                                       aria-label="Seleccionar archivo para {{ $documento->nombre_documento }}">
                                                <button type="button" class="btn-accion btn-accion--cargar btn-trigger-archivo">
                                                    <i class="fas fa-upload me-1"></i>Cargar
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <p class="mensaje-filtro-vacio" hidden><i class="fas fa-circle-info me-1"></i>No hay documentos con este estatus.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script src="{{ asset('js/perfil/indexPerfil.js') }}" defer></script>
@endsection
