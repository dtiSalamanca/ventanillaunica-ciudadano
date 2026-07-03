@extends('layouts.ciudadano')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/perfil/cambiarContrasena.css') }}">
@endsection

@section('content')
    <div class="main-container">
        {{-- Header --}}
        <div class="page-header">
            <div class="header-content">
                <img src="{{ asset('images/escudoBlanco.png') }}" alt="Escudo de Salamanca" class="header-escudo">

                <div class="header-main">
                    <h1 class="page-title">Cambiar contraseña</h1>
                    <p class="page-subtitle">Actualiza la contraseña de acceso a tu cuenta ciudadana.</p>
                </div>
            </div>
        </div>

        {{-- Tarjeta del formulario --}}
        <div class="cambiar-contrasena-layout">
            <div class="cambiar-contrasena-content">
                <div class="cambiar-contrasena-card">
                    <div class="cambiar-contrasena-card__header">
                        <div class="cambiar-contrasena-card__icono">
                            <i class="fa-solid fa-key"></i>
                        </div>
                        <div class="cambiar-contrasena-card__titulo">
                            <h2>Nueva contraseña</h2>
                            <p>Ingresa tu contraseña actual y define una nueva de al menos 8 caracteres.</p>
                        </div>
                    </div>

                    <form id="form-cambiar-contrasena"
                          action="{{ route('actualizarContrasena') }}"
                          method="POST"
                          novalidate>
                        @csrf

                        {{-- Contraseña actual --}}
                        <div class="campo campo--contrasena">
                            <label for="current_password" class="campo__label">
                                <i class="fa-solid fa-lock"></i>
                                Contraseña actual
                            </label>
                            <div class="campo__control">
                                <input type="password"
                                       id="current_password"
                                       name="current_password"
                                       class="campo__input"
                                       autocomplete="current-password"
                                       required
                                       minlength="1">
                                <button type="button"
                                        class="campo__toggle"
                                        aria-label="Mostrar contraseña"
                                        data-target="current_password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <p class="campo__error" data-error="current_password" hidden></p>
                        </div>

                        {{-- Nueva contraseña --}}
                        <div class="campo campo--contrasena">
                            <label for="password" class="campo__label">
                                <i class="fa-solid fa-lock-open"></i>
                                Nueva contraseña
                            </label>
                            <div class="campo__control">
                                <input type="password"
                                       id="password"
                                       name="password"
                                       class="campo__input"
                                       autocomplete="new-password"
                                       required
                                       minlength="8">
                                <button type="button"
                                        class="campo__toggle"
                                        aria-label="Mostrar contraseña"
                                        data-target="password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <p class="campo__error" data-error="password" hidden></p>

                            {{-- Criterios de fortaleza en tiempo real --}}
                            <div class="fortaleza" id="fortaleza" hidden>
                                <span class="fortaleza__label">La contraseña debe contener:</span>
                                <ul class="fortaleza__lista">
                                    <li class="criterio" data-criterio="longitud">
                                        <i class="fa-solid fa-circle"></i>
                                        <span>Al menos 8 caracteres</span>
                                    </li>
                                    <li class="criterio" data-criterio="mayuscula">
                                        <i class="fa-solid fa-circle"></i>
                                        <span>Una letra mayúscula</span>
                                    </li>
                                    <li class="criterio" data-criterio="minuscula">
                                        <i class="fa-solid fa-circle"></i>
                                        <span>Una letra minúscula</span>
                                    </li>
                                    <li class="criterio" data-criterio="numero">
                                        <i class="fa-solid fa-circle"></i>
                                        <span>Un número</span>
                                    </li>
                                    <li class="criterio" data-criterio="simbolo">
                                        <i class="fa-solid fa-circle"></i>
                                        <span>Un símbolo</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        {{-- Confirmar contraseña --}}
                        <div class="campo campo--contrasena">
                            <label for="password_confirmation" class="campo__label">
                                <i class="fa-solid fa-shield-halved"></i>
                                Confirmar contraseña
                            </label>
                            <div class="campo__control">
                                <input type="password"
                                       id="password_confirmation"
                                       name="password_confirmation"
                                       class="campo__input"
                                       autocomplete="new-password"
                                       required
                                       minlength="8">
                                <button type="button"
                                        class="campo__toggle"
                                        aria-label="Mostrar contraseña"
                                        data-target="password_confirmation">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <p class="campo__error" data-error="password_confirmation" hidden></p>
                            <p class="campo__coincidencia" id="coincidencia-msg" hidden>
                                <i class="fa-solid fa-circle-check"></i> Las contraseñas coinciden
                            </p>
                        </div>

                        <div class="cambiar-contrasena-card__acciones">
                            <a href="{{ route('indexPerfiles') }}" class="btn-cancelar">
                                <i class="fa-solid fa-arrow-left"></i> Cancelar
                            </a>
                            <button type="submit" class="btn-guardar" id="btn-guardar-contrasena">
                                <span class="btn-guardar__texto"><i class="fa-solid fa-floppy-disk"></i> Cambiar contraseña</span>
                                <span class="btn-guardar__spinner" hidden>
                                    <i class="fa-solid fa-circle-notch fa-spin"></i> Guardando...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Tarjeta de recomendaciones --}}
                <aside class="cambiar-contrasena-aside">
                    <div class="recomendaciones-card">
                        <h3><i class="fa-solid fa-lightbulb"></i> Recomendaciones</h3>
                        <ul>
                            <li><i class="fa-solid fa-circle-check"></i> Usa al menos 8 caracteres.</li>
                            <li><i class="fa-solid fa-circle-check"></i> Combina letras, números y símbolos.</li>
                            <li><i class="fa-solid fa-circle-check"></i> No reutilices la contraseña actual.</li>
                            <li><i class="fa-solid fa-circle-check"></i> Evita datos personales obvios.</li>
                        </ul>
                        <p class="recomendaciones-card__nota">
                            <i class="fa-solid fa-circle-info"></i>
                            Por seguridad, se cerrará tu sesión al actualizar la contraseña.
                        </p>
                    </div>
                </aside>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        window.cambiarContrasenaConfig = @json(['url' => route('actualizarContrasena'), 'csrfToken' => csrf_token(), 'redirectLogin' => route('login')]);
    </script>
    <script src="{{ asset('js/perfil/cambiarContrasena.js') }}" defer></script>
@endsection