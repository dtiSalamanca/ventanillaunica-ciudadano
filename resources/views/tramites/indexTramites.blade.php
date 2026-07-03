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

        {{-- Layout de dos columnas --}}
        <div class="tramites-layout">
            {{-- Sidebar de dependencias --}}
            <aside class="tramites-sidebar">
                <div class="tramites-sidebar-header">
                    <h2>
                        <i class="fa-solid fa-building-columns"></i>
                        Dependencias
                    </h2>
                </div>

                <div class="tramites-sidebar-list">
                    <button class="tramites-sidebar-item active" type="button" data-dependencia="todas">
                        <span class="tramites-sidebar-item-label">
                            <i class="fa-solid fa-layer-group"></i>
                            <span>Todas</span>
                        </span>
                        <span class="tramites-sidebar-count">{{ $tramites->count() }}</span>
                    </button>

                    @foreach ($dependencias as $dependencia)
                        <button class="tramites-sidebar-item" type="button" data-dependencia="{{ $dependencia->id_dependencia }}">
                            <span class="tramites-sidebar-item-label">
                                <i class="fa-solid fa-building"></i>
                                <span>{{ $dependencia->nombre_dependencia }}</span>
                            </span>
                            <span class="tramites-sidebar-count">{{ $tramites->where('fk_dependencia', $dependencia->id_dependencia)->count() }}</span>
                        </button>
                    @endforeach
                </div>
            </aside>

            {{-- Contenido principal --}}
            <div class="tramites-content">
                {{-- Tabs de dependencias en móvil --}}
                <div class="tramites-tabs-mobile">
                    <button class="tramites-tab-item active" type="button" data-dependencia="todas">
                        <i class="fa-solid fa-layer-group"></i> Todas
                    </button>
                    @foreach ($dependencias as $dependencia)
                        <button class="tramites-tab-item" type="button" data-dependencia="{{ $dependencia->id_dependencia }}">
                            {{ $dependencia->nombre_dependencia }}
                        </button>
                    @endforeach
                </div>

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
                                            <i class="fa-solid fa-building me-1"></i>
                                            <span class="tramite-card-dependencia-label">Organiza:</span>
                                            {{ $tramite->dependencia?->nombre_dependencia ?? 'Sin dependencia' }}
                                        </div>
                                    </div>

                                    <span class="badge-requisitos">{{ $totalRequisitos }} requisito(s)</span>
                                </div>

                                @if ($tramite->descripcion_tramite)
                                    <div class="tramite-card-descripcion">
                                        <i class="fa-solid fa-quote-left tramite-card-descripcion-icono"></i>
                                        <p>{{ $tramite->descripcion_tramite }}</p>
                                    </div>
                                @endif

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
                                    <div class="tramite-card-precio">
                                        <span class="precio-label">Precio del trámite</span>
                                        <span class="precio-monto">${{ number_format($tramite->precio_tramite, 2) }}</span>
                                    </div>

                                    <a href="{{ route('iniciarTramite', $tramite) }}"
                                       class="btn-iniciar-tramite"
                                       data-nombre="{{ $tramite->nombre_tramite }}">
                                        <i class="fa-solid fa-play"></i> Iniciar trámite
                                    </a>
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
    <script src="https://cdn.jsdelivr.net/npm/animejs@3.2.2/lib/anime.min.js"></script>
    <script src="{{ asset('js/tramites/indexTramites.js') }}" defer></script>
@endsection
