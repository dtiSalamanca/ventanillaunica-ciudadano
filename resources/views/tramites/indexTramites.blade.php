@extends('layouts.ciudadano')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/tramites/indexTramites.css') }}">
@endsection

@section('content')
    <div class="main-container">
        {{-- Header --}}
        <div class="page-header">
            <div class="header-content">
                <img src="{{ asset('images/escudoBlanco.png') }}" alt="Escudo de Salamanca" class="header-escudo">

                <div class="header-main">
                    <h1 class="page-title">Trámites</h1>
                    <p class="page-subtitle">Consulta los trámites disponibles y los requisitos para cada uno.</p>
                </div>
            </div>
        </div>

        {{-- Card principal --}}
        <div class="card">
            <div class="card-body">
                @if ($dependencias->count() > 1)
                    {{-- Tabs por dependencia --}}
                    <ul class="nav nav-tabs mb-3" id="tabs-tramites" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" type="button" data-dependencia="todas">
                                <i class="fa-solid fa-layer-group me-1"></i> Todas
                            </button>
                        </li>

                        @foreach ($dependencias as $dependencia)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" type="button" data-dependencia="{{ $dependencia->id_dependencia }}">
                                    {{ $dependencia->nombre_dependencia }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif

                {{-- Búsqueda --}}
                <div class="tramites-search">
                    <div class="search-bar">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text"
                               id="tramites-search-input"
                               placeholder="Buscar trámite por nombre..."
                               autocomplete="off">
                        <button type="button"
                                id="tramites-search-clear"
                                class="search-clear"
                                title="Limpiar búsqueda"
                                style="display: none">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                @if ($tramites->isEmpty())
                    <div class="empty-state">
                        <i class="fa-solid fa-inbox"></i>
                        <p>No hay trámites disponibles en este momento.</p>
                    </div>
                @else
                    <div class="tramites-grid">
                        @foreach ($tramites as $tramite)
                            @php
                                $totalRequisitos = $tramite->requisitos->count();
                                $idCuerpo = 'collapse-tramite-' . $tramite->id_tramite;
                            @endphp

                            <div class="tramite-card"
                                 data-dependencia="{{ $tramite->fk_dependencia }}"
                                 data-nombre="{{ mb_strtolower($tramite->nombre_tramite) }}">
                                <div class="tramite-card-header">
                                    <div class="tramite-card-icono">
                                        <i class="fas fa-file-invoice"></i>
                                    </div>

                                    <div class="tramite-card-info">
                                        <div class="tramite-card-nombre">{{ $tramite->nombre_tramite }}</div>
                                        <div class="tramite-card-dependencia">
                                            <i class="fa-solid fa-building me-1"></i>{{ $tramite->dependencia?->nombre_dependencia ?? 'Sin dependencia' }}
                                        </div>
                                    </div>

                                    <span class="badge-requisitos">{{ $totalRequisitos }} requisito(s)</span>
                                </div>

                                <div class="accordion tramite-accordion">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed"
                                                    type="button"
                                                    aria-expanded="false"
                                                    aria-controls="{{ $idCuerpo }}">
                                                <i class="fa-solid fa-folder-open me-2"></i>
                                                Ver requisitos
                                            </button>
                                        </h2>

                                        <div id="{{ $idCuerpo }}"
                                             class="accordion-collapse collapse"
                                             role="region"
                                             aria-label="Requisitos de {{ $tramite->nombre_tramite }}">
                                            <div class="accordion-body">
                                                @if ($tramite->requisitos->isEmpty())
                                                    <div class="requisito-empty">
                                                        <i class="fa-solid fa-circle-info me-1"></i>
                                                        Este trámite no tiene requisitos registrados.
                                                    </div>
                                                @else
                                                    @foreach ($tramite->requisitos as $requisito)
                                                        <div class="requisito-item">
                                                            <div class="requisito-info">
                                                                <span class="requisito-nombre">
                                                                    <i class="fa-solid fa-circle-check requisito-check"></i>
                                                                    {{ $requisito->nombre_requisito }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tramite-card-footer">
                                    <button type="button"
                                            class="btn-iniciar-tramite"
                                            data-nombre="{{ $tramite->nombre_tramite }}">
                                        <i class="fa-solid fa-play"></i> Iniciar trámite
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="empty-state empty-state-filtro" hidden>
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <p>No se encontraron trámites con los filtros aplicados.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/tramites/indexTramites.js') }}" defer></script>
@endsection
