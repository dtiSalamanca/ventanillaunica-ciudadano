@extends('layouts.ciudadano')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/perfil/indexPerfil.css') }}">
@endsection

@section('content')
    <div class="main-container">
        <div class="page-header">
            <div class="header-content">
                <img src="{{ asset('images/escudoBlanco.png') }}" alt="Escudo de Salamanca" class="header-escudo">
                <div class="header-main">
                    <h1 class="page-title">Mi perfil</h1>
                    <p class="page-subtitle">Consulta tus datos personales y el estatus de tus documentos.</p>
                </div>
            </div>
        </div>

        @php
            $prediosConErrorCorreccion = collect($predios)->filter(
                fn ($predioTmp) => $errors->has('clave_predio_'.$predioTmp->id_predio)
            );
            $abrirTabPredios = $errors->has('clave_predio') || $prediosConErrorCorreccion->isNotEmpty();
            $tabActiva = $abrirTabPredios ? 'predios' : 'documentos';

            $porcentaje = $totalDocumentos > 0 ? (int) round(($documentosCompletados / $totalDocumentos) * 100) : 0;

            $totalDocumentosPrediosGlobal = $predios->count() * $catalogoPredios->count();
            $documentosPrediosCompletadosGlobal = 0;
            foreach ($predios as $predioTmp) {
                $documentosPredioCargadosTmp = $predioTmp->documentos->keyBy('fk_cat_documento_predio');
                $documentosPrediosCompletadosGlobal += $catalogoPredios->filter(
                    fn ($doc) => $documentosPredioCargadosTmp->has($doc->id_documento_predio)
                )->count();
            }
            $porcentajePredios = $totalDocumentosPrediosGlobal > 0
                ? (int) round(($documentosPrediosCompletadosGlobal / $totalDocumentosPrediosGlobal) * 100)
                : 0;
        @endphp

        <div class="profile-card">
            <div class="profile-card__avatar" aria-hidden="true">
                {{ Str::of(auth()->user()->name)->explode(' ')->map(fn ($palabra) => Str::substr($palabra, 0, 1))->take(2)->implode('') }}
            </div>

            <div class="profile-card__datos">
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
            </div>

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

        <div class="perfil-layout">
            <aside class="perfil-sidebar">
                <div class="perfil-sidebar__header">
                    <h2><i class="fa-solid fa-user-gear"></i> Secciones</h2>
                </div>
                <div class="perfil-sidebar__list" role="tablist" aria-label="Secciones del perfil">
                    <button class="perfil-sidebar__item @if ($tabActiva === 'documentos') perfil-sidebar__item--active @endif"
                            role="tab"
                            aria-selected="{{ $tabActiva === 'documentos' ? 'true' : 'false' }}"
                            aria-controls="panel-documentos"
                            id="tab-documentos"
                            data-seccion="documentos"
                            type="button">
                        <span class="perfil-sidebar__item-label">
                            <i class="fas fa-id-card"></i>
                            <span>Documentos personales</span>
                        </span>
                        @if ($totalDocumentos > 0)
                            <span class="perfil-sidebar__count">{{ $documentosCompletados }}/{{ $totalDocumentos }}</span>
                        @endif
                    </button>
                    <button class="perfil-sidebar__item @if ($tabActiva === 'predios') perfil-sidebar__item--active @endif"
                            role="tab"
                            aria-selected="{{ $tabActiva === 'predios' ? 'true' : 'false' }}"
                            aria-controls="panel-predios"
                            id="tab-predios"
                            data-seccion="predios"
                            type="button">
                        <span class="perfil-sidebar__item-label">
                            <i class="fas fa-house"></i>
                            <span>Predios</span>
                        </span>
                        <span class="perfil-sidebar__count">{{ $predios->count() }}</span>
                    </button>
                </div>
            </aside>

            <div class="perfil-content">
                <div class="perfil-tabs-mobile">
                    <button class="perfil-tab-mobile @if ($tabActiva === 'documentos') perfil-tab-mobile--active @endif" data-seccion="documentos" type="button">
                        <i class="fas fa-id-card me-1"></i>Documentos
                    </button>
                    <button class="perfil-tab-mobile @if ($tabActiva === 'predios') perfil-tab-mobile--active @endif" data-seccion="predios" type="button">
                        <i class="fas fa-house me-1"></i>Predios
                    </button>
                </div>

                <div class="profile-tab-content" role="tabpanel" id="panel-documentos" aria-labelledby="tab-documentos" @if ($tabActiva !== 'documentos') hidden @endif>
            <div class="bento-grid">
                <div class="documents-card documents-card--full">
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

                    @if ($totalDocumentos > 0)
                        <div class="documents-card__progreso" data-seccion="documentos">
                            <div class="documents-card__progreso-header">
                                <span class="documents-card__progreso-label"><i class="fas fa-circle-check me-1"></i>Documentos completados</span>
                                <span class="documents-card__progreso-valor">{{ $porcentaje }}%</span>
                            </div>
                            <div class="progreso-barra">
                                <div class="progreso-barra-fill" data-progreso="{{ $porcentaje }}"></div>
                            </div>
                        </div>
                    @endif

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
                                <div class="documento-card {{ $cargado ? 'documento-card--cargado' : '' }}"
                                     data-estatus="{{ $estatusDoc }}"
                                     data-catalogo-id="{{ $documento->id_documento }}"
                                     @if ($cargado) data-estatus-num="{{ $estatus }}" @endif>
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
                                            <a href="{{ route('descargarDocumento', $cargado->id_documento) }}"
                                               class="btn-accion btn-accion--ver"
                                               target="_blank"
                                               title="Ver documento">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if ($estatus === 0)
                                                <form action="{{ route('subirDocumento', $documento->id_documento) }}"
                                                      method="POST"
                                                      enctype="multipart/form-data"
                                                      class="form-subir-inline">
                                                    @csrf
                                                    <input type="file"
                                                           name="archivo"
                                                           class="input-archivo-oculto"
                                                           accept=".pdf"
                                                           aria-label="Volver a subir {{ $documento->nombre_documento }}">
                                                    <button type="button" class="btn-accion btn-accion--cargar btn-trigger-archivo" title="Volver a subir">
                                                        <i class="fas fa-rotate-right me-1"></i>Reenviar
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <span class="badge-estatus badge-estatus--pendiente"><i class="fas fa-circle-exclamation me-1"></i>Pendiente</span>
                                            <form action="{{ route('subirDocumento', $documento->id_documento) }}"
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

        <div class="profile-tab-content" role="tabpanel" id="panel-predios" aria-labelledby="tab-predios" @if ($tabActiva !== 'predios') hidden @endif>
            <div class="bento-grid">
                <div class="documents-card documents-card--full">
                    <div class="documents-card__header">
                        <h2 class="documents-card__title">Mis predios</h2>
                        <button class="btn-accion btn-accion--cargar" id="btn-mostrar-form-predio" type="button">
                            <i class="fas fa-plus me-1"></i>Agregar predio
                        </button>
                    </div>

                    <div class="aviso-predios" role="note">
                        <i class="fas fa-circle-info"></i>
                        <span>Para cargar los documentos de un predio, es necesario que su clave catastral haya sido <strong>validada previamente</strong>.</span>
                    </div>

                    @if ($predios->isNotEmpty() && $catalogoPredios->isNotEmpty())
                        <div class="documents-card__progreso" data-seccion="predios">
                            <div class="documents-card__progreso-header">
                                <span class="documents-card__progreso-label"><i class="fas fa-circle-check me-1"></i>Documentos completados</span>
                                <span class="documents-card__progreso-valor">{{ $porcentajePredios }}%</span>
                            </div>
                            <div class="progreso-barra">
                                <div class="progreso-barra-fill" data-progreso="{{ $porcentajePredios }}"></div>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('agregarPredio') }}"
                          method="POST"
                          id="form-agregar-predio"
                          class="form-agregar-predio @if (!$errors->has('clave_predio')) form-agregar-predio--oculto @endif">
                        @csrf
                        <div class="form-agregar-predio__titulo">
                            <i class="fas fa-house-circle-plus"></i>
                            <span>Nuevo predio</span>
                        </div>
                        <div class="form-agregar-predio__contenido">
                            <div class="form-agregar-predio__campo">
                                <label for="clave_predio">Clave catastral del predio</label>
                                <div class="form-agregar-predio__input-wrap">
                                    <i class="fas fa-location-dot form-agregar-predio__input-icono"></i>
                                    <input type="text"
                                           name="clave_predio"
                                           id="clave_predio"
                                           value="{{ old('clave_predio') }}"
                                           placeholder="Ej. 123-456-789-000"
                                           maxlength="255"
                                           required>
                                </div>
                                <span class="form-agregar-predio__ayuda">Captura la clave tal como aparece en tu recibo predial o boleta.</span>
                                @error('clave_predio')
                                    <span class="form-agregar-predio__error">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-agregar-predio__acciones">
                                <button type="submit" class="btn-accion btn-accion--cargar">
                                    <i class="fas fa-floppy-disk me-1"></i>Guardar
                                </button>
                                <button type="button" class="btn-accion btn-accion--cancelar" id="btn-cancelar-form-predio">
                                    <i class="fas fa-xmark me-1"></i>Cancelar
                                </button>
                            </div>
                        </div>
                    </form>

                    @if ($predios->isEmpty())
                        <p class="mensaje-vacio"><i class="fas fa-circle-info me-1"></i>Aún no has agregado predios a tu perfil.</p>
                    @else
                        <div class="predios-lista" id="predios-lista">
                            @foreach ($predios as $predio)
                                @include('perfil.predio-card', ['predio' => $predio])
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        window.perfilConfig = @json([
            'urlEstatusDocumentos' => route('estatusDocumentos'),
            'urlEstatusPredios' => route('estatusPredios'),
        ]);
    </script>
    <script src="{{ asset('js/perfil/indexPerfil.js') }}" defer></script>
@endsection
