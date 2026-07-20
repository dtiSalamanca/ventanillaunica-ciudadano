@extends('layouts.ciudadano')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
@endsection

@section('content')
    <div class="home-container">
        {{-- Hero --}}
        <section class="hero-section">
            <div class="hero-header">
                <div class="hero-bg-pattern"></div>
                <h1 class="hero-title">Sistema de Ventanilla Única</h1>
                <p class="hero-subtitle">H. Ayuntamiento de Salamanca, Guanajuato</p>
            </div>
            <div class="hero-body">
                <img src="{{ asset('images/escudoBlanco.png') }}" alt="Escudo de Salamanca" class="hero-image">
                <p class="hero-welcome">Bienvenido, <strong>{{ auth()->user()?->name ?? 'Usuario' }}</strong></p>
                <p class="hero-description">
                    Este sistema te permite gestionar tus trámites municipales de forma rápida, segura y transparente.
                    Consulta los trámites disponibles, da seguimiento a tus solicitudes y mantén tu información
                    actualizada.
                </p>
            </div>
        </section>

        {{-- Características --}}
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                    </svg>
                </div>
                <h3 class="feature-title">Proceso simplificado</h3>
                <p class="feature-text">Realiza tus trámites desde cualquier lugar, sin filas ni esperas. El proceso es
                    ágil y guiado paso a paso.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>
                <h3 class="feature-title">Seguridad y transparencia</h3>
                <p class="feature-text">Tus datos personales y documentos están protegidos. Puedes dar seguimiento a cada
                    solicitud en tiempo real.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
                <h3 class="feature-title">Documentación digital</h3>
                <p class="feature-text">Sube y administra tus documentos digitalmente. No necesitas presentar copias
                    físicas ni trasladarte.</p>
            </div>
        </div>

        {{-- Documentos Requeridos --}}
        <section class="info-section">
            <h3 class="info-header">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                Documentos Requeridos
            </h3>
            <div class="info-content">
                <p>Estos son los documentos que necesitas tener registrados para poder realizar tus trámites municipales.
                    Revisa el estatus de cada uno y asegúrate de tenerlos actualizados.</p>

                {{-- Progreso documentos personales --}}
                @if ($totalDocumentos > 0)
                    <div class="progress-summary">
                        <div class="progress-summary-label">
                            <i class="fa-solid fa-id-card"></i>
                            Documentos personales:
                            <strong>{{ $documentosCompletados }}/{{ $totalDocumentos }}</strong>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill"
                                style="width: {{ round(($documentosCompletados / $totalDocumentos) * 100) }}%">
                            </div>
                        </div>
                    </div>
                @endif

                <div class="requirements-list">
                    @forelse ($documentosCatalogo as $documento)
                        @php
                            $cargado = $documentosCargados->get($documento->id_documento);
                            $estatus = $cargado ? (int) $cargado->estatus_documento : null;
                            $esAprobado = $cargado && $estatus === 2;
                        @endphp
                        <div class="requirement-item {{ $esAprobado ? 'requirement-item--ok' : '' }}">
                            <div class="requirement-icon">
                                @if ($esAprobado)
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="9 11 12 14 22 4"></polyline>
                                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                                    </svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                @endif
                            </div>
                            <span class="requirement-text">{{ $documento->nombre_documento }}</span>
                            @if ($cargado && $estatus === 2)
                                <span class="requirement-badge requirement-badge--ok">Aprobado</span>
                            @elseif ($cargado && $estatus === 1)
                                <span class="requirement-badge requirement-badge--review">En revisión</span>
                            @elseif ($cargado)
                                <span class="requirement-badge requirement-badge--no">Rechazado</span>
                            @else
                                <span class="requirement-badge requirement-badge--pending">Pendiente</span>
                            @endif
                        </div>
                    @empty
                        <p class="info-empty">No hay documentos personales registrados.</p>
                    @endforelse
                </div>

                {{-- Predios --}}
                @if ($predios->isNotEmpty())
                    <div style="margin-top: 2rem;">
                        <div class="progress-summary">
                            <div class="progress-summary-label">
                                <i class="fa-solid fa-house"></i>
                                Documentos de predios:
                                <strong>{{ $predios->count() }} predio(s) registrado(s)</strong>
                            </div>
                        </div>

                        @foreach ($predios as $predio)
                            @php
                                $documentosPredioCargados = $predio->documentos->keyBy('fk_cat_documento_predio');
                                $completadosPredio = $catalogoPredios
                                    ->filter(fn($doc) => $documentosPredioCargados->has($doc->id_documento_predio))
                                    ->count();
                            @endphp
                            <div class="predio-group">
                                <div class="predio-header">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    <span class="predio-clave">{{ $predio->clave_predio }}</span>
                                    <span
                                        class="predio-count">{{ $completadosPredio }}/{{ $catalogoPredios->count() }}</span>
                                </div>
                                <div class="requirements-list" style="margin-top: 0.5rem;">
                                    @foreach ($catalogoPredios as $documento)
                                        @php
                                            $cargadoPredio = $documentosPredioCargados->has(
                                                $documento->id_documento_predio,
                                            );
                                        @endphp
                                        <div class="requirement-item {{ $cargadoPredio ? 'requirement-item--ok' : '' }}">
                                            <div class="requirement-icon">
                                                @if ($cargadoPredio)
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="9 11 12 14 22 4"></polyline>
                                                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11">
                                                        </path>
                                                    </svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <circle cx="12" cy="12" r="10"></circle>
                                                        <line x1="12" y1="8" x2="12"
                                                            y2="12"></line>
                                                        <line x1="12" y1="16" x2="12.01"
                                                            y2="16"></line>
                                                    </svg>
                                                @endif
                                            </div>
                                            <span class="requirement-text">{{ $documento->nombre_documento }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="info-empty" style="margin-top: 1rem;">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Aún no has registrado predios. Agrégarlos desde tu perfil.</span>
                        <a href="{{ route('indexPerfiles') }}" class="info-link">Ir a mi perfil</a>
                    </div>
                @endif

                <div style="margin-top: 1.5rem; text-align: center;">
                    <a href="{{ route('indexPerfiles') }}" class="btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Administrar documentos en mi perfil
                    </a>
                </div>
            </div>
        </section>

        {{-- Accesos rápidos --}}
        <div class="features-grid" style="margin-bottom: 2rem;">
            <a href="{{ route('indexTramites') }}" class="feature-card" style="text-decoration: none; display: block;">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                </div>
                <h3 class="feature-title">Generar trámite</h3>
                <p class="feature-text">Inicia un nuevo trámite seleccionando la dependencia y el tipo de trámite que
                    necesitas.</p>
            </a>

            <a href="{{ route('misTramites') }}" class="feature-card" style="text-decoration: none; display: block;">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <line x1="8" y1="6" x2="21" y2="6"></line>
                        <line x1="8" y1="12" x2="21" y2="12"></line>
                        <line x1="8" y1="18" x2="21" y2="18"></line>
                        <line x1="3" y1="6" x2="3.01" y2="6"></line>
                        <line x1="3" y1="12" x2="3.01" y2="12"></line>
                        <line x1="3" y1="18" x2="3.01" y2="18"></line>
                    </svg>
                </div>
                <h3 class="feature-title">Mis trámites</h3>
                <p class="feature-text">Consulta el estado de tus solicitudes y da seguimiento a tus trámites en curso.
                </p>
            </a>

            <a href="{{ route('indexPerfiles') }}" class="feature-card" style="text-decoration: none; display: block;">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <h3 class="feature-title">Mi perfil</h3>
                <p class="feature-text">Administra tus datos personales, documentos y predios registrados en el sistema.
                </p>
            </a>
        </div>

        {{-- CTA --}}
        <section class="cta-section">
            <h2 class="cta-title">¿Listo para comenzar?</h2>
            <p class="mb-4">Inicia un nuevo trámite o consulta el estado de tus solicitudes existentes.</p>
            <a href="{{ route('indexTramites') }}" class="btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
                Generar trámite
            </a>
        </section>

        <p class="footer-note">Para cualquier duda o aclaración, puede comunicarse con la Dirección de Trámites
            Municipales de Salamanca.</p>
    </div>

    @if (session('status'))
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __('¡Bienvenido!') }}',
                        text: '{{ session('status') }}',
                        confirmButtonColor: '#1E5C50',
                        confirmButtonText: 'Comenzar',
                        timer: 4000,
                        timerProgressBar: true
                    });
                });
            </script>
        @endpush
    @endif
@endsection
