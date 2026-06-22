@extends('layouts.login')

@section('head')
    <link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth/register.css') }}">

    @if (config('services.recaptcha_v3.enabled') && filled(config('services.recaptcha_v3.site_key')))
        <script
            src="https://www.google.com/recaptcha/api.js?render={{ urlencode((string) config('services.recaptcha_v3.site_key')) }}"
            async defer></script>
    @endif
@endsection

@section('content')
    <div class="auth-page">
        <div class="auth-grid">
            <!-- Columna izquierda: panel con el formulario -->
            <div class="auth-panel">
                <div class="auth-card">
                    <div class="auth-header">
                        <img src="{{ asset('images/escudoArma.png') }}" alt="Escudo de armas" class="auth-logo">
                        <h1 class="auth-title">SISTEMA DE ADMINISTRACIÓN DE LA VENTANILLA ÚNICA</h1>
                        <p class="auth-subtitle">Crea tu cuenta en la Ventanilla Única de Salamanca, Guanajuato.</p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
                    @endif

                    @error('name')
                        <div class="alert alert-danger" role="alert">{{ $message }}</div>
                    @enderror

                    @error('email')
                        <div class="alert alert-danger" role="alert">{{ $message }}</div>
                    @enderror

                    @error('password')
                        <div class="alert alert-danger" role="alert">{{ $message }}</div>
                    @enderror

                    <div id="recaptchaErrorContainer"
                        class="alert alert-danger @unless ($errors->has('recaptcha_token')) is-hidden @endunless" role="alert">
                        <span id="recaptchaError">{{ $errors->first('recaptcha_token') }}</span>
                    </div>

                    <form id="registerForm" method="POST" action="{{ route('register') }}" novalidate
                        data-recaptcha-enabled='@json((bool) config('services.recaptcha_v3.enabled') && filled(config('services.recaptcha_v3.site_key')))'
                        data-recaptcha-site-key="{{ (string) config('services.recaptcha_v3.site_key') }}"
                        data-recaptcha-action="{{ (string) config('services.recaptcha_v3.action', 'register') }}">
                        @csrf

                        <input id="recaptcha_token" type="hidden" name="recaptcha_token" value="">

                        <!-- Campo: Nombre -->
                        <div class="form-field form-field--float">
                            <input id="name" type="text" name="name" value="{{ old('name') }}" required
                                autocomplete="name" autofocus class="input-control input-control--float" placeholder=" "
                                aria-describedby="nameError" aria-invalid="false">
                            <label for="name" class="float-label">Nombre completo</label>
                            <small id="nameError" class="error-text" role="alert"></small>
                        </div>

                        <!-- Campo: Correo Electrónico -->
                        <div class="form-field form-field--float">
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                                autocomplete="email" class="input-control input-control--float" placeholder=" "
                                aria-describedby="emailError" aria-invalid="false">
                            <label for="email" class="float-label">Correo electrónico</label>
                            <small id="emailError" class="error-text" role="alert"></small>
                        </div>

                        <!-- Campo: Contraseña -->
                        <div class="form-field form-field--float">
                            <input id="password" type="password" name="password" required minlength="8"
                                autocomplete="new-password" class="input-control input-control--float input-has-suffix"
                                placeholder=" " aria-describedby="passwordError capsHint" aria-invalid="false">
                            <label for="password" class="float-label">Contraseña</label>
                            <button type="button" id="togglePassword" class="input-toggle"
                                aria-label="Mostrar u ocultar contraseña">Ver</button>
                            <small id="passwordError" class="error-text" role="alert"></small>
                            <div id="capsHint" class="caps-hint">Bloq Mayús activado</div>
                        </div>

                        <!-- Campo: Confirmar Contraseña -->
                        <div class="form-field form-field--float">
                            <input id="password-confirm" type="password" name="password_confirmation" required
                                minlength="8" autocomplete="new-password"
                                class="input-control input-control--float" placeholder=" "
                                aria-describedby="passwordConfirmError" aria-invalid="false">
                            <label for="password-confirm" class="float-label">Confirmar contraseña</label>
                            <small id="passwordConfirmError" class="error-text" role="alert"></small>
                        </div>

                        <!-- Botón de envío -->
                        <div class="form-field form-field--submit">
                            <button type="submit" class="btn-primary-login">Crear cuenta</button>
                        </div>

                        <h5 style="text-align: center;">ó</h5>

                        <div class="form-field form-field--submit">
                            <a href="{{ route('login') }}" class="btn-register">Iniciar sesión</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Columna derecha: ilustración a pantalla completa -->
            <div class="auth-illustration auth-illustration--register"></div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/auth/register.js') }}" defer></script>
@endsection
