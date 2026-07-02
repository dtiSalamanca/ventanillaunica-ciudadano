@extends('layouts.ciudadano')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/tramites/iniciarTramite.css') }}">
@endsection

@section('content')
    @php
        $requisitos = $tramite->requisitos;
        $totalRequisitos = $requisitos->count();

        // Placeholder: trámites previos finalizados. Sustituir cuando se definan los estados de Solicitud.
        $tramitesPrevios = [
            (object) ['id' => 'demo-001', 'nombre' => 'Licencia de Uso de Suelo #2024-0123', 'fecha' => '15/01/2024'],
            (object) ['id' => 'demo-002', 'nombre' => 'Constancia de Clave Catastral #2023-0456', 'fecha' => '03/06/2023'],
        ];
    @endphp

    <div class="main-container">
        {{-- Header --}}
        <div class="page-header">
            <div class="header-content">
                <img src="{{ asset('images/escudoBlanco.png') }}" alt="Escudo de Salamanca" class="header-escudo">

                <div class="header-main">
                    <h1 class="page-title">Iniciar trámite</h1>
                    <p class="page-subtitle">{{ $tramite->nombre_tramite }}</p>
                </div>
            </div>
        </div>

        {{-- Volver --}}
        <a href="{{ route('indexTramites') }}" class="btn-volver">
            <i class="fa-solid fa-arrow-left"></i> Volver a trámites
        </a>

        {{-- Resumen del trámite --}}
        <div class="tramite-resumen">
            <div class="tramite-resumen__info">
                <div class="tramite-resumen__dependencia">
                    <i class="fa-solid fa-building"></i>
                    {{ $tramite->dependencia?->nombre_dependencia ?? 'Sin dependencia' }}
                </div>
                <span class="badge-requisitos">
                    <i class="fa-solid fa-list-check me-1"></i>{{ $totalRequisitos }} requisito(s)
                </span>
            </div>

            @if ($totalRequisitos > 0)
                <div class="progreso-tramite">
                    <div class="progreso-tramite__header">
                        <span class="progreso-tramite__label">Requisitos cumplidos</span>
                        <span class="progreso-tramite__valor">
                            <span id="progreso-actual">0</span> de {{ $totalRequisitos }}
                        </span>
                    </div>
                    <div class="progreso-tramite__barra">
                        <div id="progreso-fill" class="progreso-tramite__fill" data-progreso="0"></div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Card principal --}}
        <div class="card">
            <div class="card-body">
                @if ($requisitos->isEmpty())
                    <div class="empty-state">
                        <i class="fa-solid fa-inbox"></i>
                        <p>Este trámite no tiene requisitos registrados.</p>
                    </div>
                @else
                    <p class="instrucciones">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Para cada requisito, elige cómo quieres cumplirlo: usando un documento personal aprobado,
                        subiendo un archivo o indicando un trámite previamente finalizado.
                    </p>

                    <div class="requisitos-cumplimiento-lista">
                        @foreach ($requisitos as $requisito)
                            @php
                                $idRequisito = $requisito->id_requisito;
                                $nombreRadio = "requisito_{$idRequisito}";
                            @endphp

                            <div class="requisito-cumplimiento" data-requisito="{{ $idRequisito }}">
                                <div class="requisito-cumplimiento__cabecera">
                                    <span class="requisito-cumplimiento__icono">
                                        <i class="fa-solid fa-circle-dot"></i>
                                    </span>
                                    <span class="requisito-cumplimiento__nombre">{{ $requisito->nombre_requisito }}</span>
                                    <span class="badge-estado badge-estado--pendiente">
                                        <i class="fa-solid fa-circle-exclamation me-1"></i>Pendiente
                                    </span>
                                </div>

                                {{-- Opciones de cumplimiento --}}
                                <div class="requisito-opciones" role="radiogroup" aria-label="Forma de cumplir {{ $requisito->nombre_requisito }}">
                                    <label class="requisito-opcion" data-metodo="documento">
                                        <input type="radio"
                                               name="{{ $nombreRadio }}"
                                               value="documento"
                                               class="requisito-opcion__radio">
                                        <i class="fa-solid fa-id-card"></i> Documento personal
                                    </label>

                                    <label class="requisito-opcion" data-metodo="subir">
                                        <input type="radio"
                                               name="{{ $nombreRadio }}"
                                               value="subir"
                                               class="requisito-opcion__radio">
                                        <i class="fa-solid fa-upload"></i> Subir archivo
                                    </label>

                                    <label class="requisito-opcion" data-metodo="tramite">
                                        <input type="radio"
                                               name="{{ $nombreRadio }}"
                                               value="tramite"
                                               class="requisito-opcion__radio">
                                        <i class="fa-solid fa-file-circle-check"></i> Trámite previo
                                    </label>
                                </div>

                                {{-- Controles dinámicos --}}
                                <div class="requisito-controles">
                                    {{-- Documento personal aprobado --}}
                                    <div class="requisito-control" data-control="documento" hidden>
                                        <label class="requisito-control__label" for="documento-{{ $idRequisito }}">
                                            Selecciona un documento aprobado
                                        </label>
                                        <select id="documento-{{ $idRequisito }}"
                                                class="requisito-control__select"
                                                disabled>
                                            @if ($documentosAprobados->isEmpty())
                                                <option value="" disabled selected>Sin documentos aprobados</option>
                                            @else
                                                <option value="" disabled selected>Elige un documento...</option>
                                                @foreach ($documentosAprobados as $documento)
                                                    <option value="{{ $documento->id_documento }}">
                                                        {{ $documento->catalogoDocumento?->nombre_documento ?? 'Documento' }} — Aprobado
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        @if ($documentosAprobados->isEmpty())
                                            <p class="mensaje-ayuda">
                                                <i class="fa-solid fa-circle-info me-1"></i>
                                                No tienes documentos aprobados.
                                                <a href="{{ route('indexPerfiles') }}">Súbelos desde Mi perfil</a>.
                                            </p>
                                        @endif
                                    </div>

                                    {{-- Subir archivo --}}
                                    <div class="requisito-control" data-control="subir" hidden>
                                        <label class="requisito-control__label" for="archivo-{{ $idRequisito }}">
                                            Selecciona el archivo a subir
                                        </label>
                                        <input type="file"
                                               id="archivo-{{ $idRequisito }}"
                                               class="requisito-control__archivo"
                                               accept=".pdf"
                                               disabled>
                                        <p class="mensaje-ayuda">
                                            <i class="fa-solid fa-file-pdf me-1"></i>PDF, máximo 10 MB.
                                        </p>
                                    </div>

                                    {{-- Trámite previo finalizado --}}
                                    <div class="requisito-control" data-control="tramite" hidden>
                                        <label class="requisito-control__label" for="tramite-{{ $idRequisito }}">
                                            Selecciona un trámite finalizado
                                        </label>
                                        <select id="tramite-{{ $idRequisito }}"
                                                class="requisito-control__select"
                                                disabled>
                                            <option value="" disabled selected>Elige un trámite...</option>
                                            @foreach ($tramitesPrevios as $tramitePrevio)
                                                <option value="{{ $tramitePrevio->id }}">
                                                    {{ $tramitePrevio->nombre }} — {{ $tramitePrevio->fecha }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <p class="mensaje-ayuda">
                                            <i class="fa-solid fa-circle-info me-1"></i>
                                            Trámites finalizados que entregan un documento oficial.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Acción final --}}
                    <div class="acciones-finales">
                        <button type="button" id="btn-enviar-solicitud" class="btn-enviar-solicitud">
                            <i class="fa-solid fa-paper-plane"></i> Enviar solicitud
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/tramites/iniciarTramite.js') }}" defer></script>
@endsection
