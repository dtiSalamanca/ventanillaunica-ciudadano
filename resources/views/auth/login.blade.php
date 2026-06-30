@extends('layouts.login')

@section('head')
    <link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">

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
                        <!-- <span class="auth-badge">Iniciar sesión</span> -->
                        <img src="{{ asset('images/escudoArma.png') }}" alt="Escudo de armas" class="auth-logo">
                        <h1 class="auth-title">VENTANILLA ÚNICA DE SALAMANCA, GUANAJUATO</h1>
                        <p class="auth-subtitle">Accede a la plataforma de la Ventanilla Única de
                            Salamanca, Guanajuato.
                        </p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success" role="status">
                            <span class="alert-icon" aria-hidden="true">&check;</span>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    @error('ad')
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

                    <form id="loginForm" method="POST" action="{{ route('login') }}" novalidate
                        data-recaptcha-enabled='@json((bool) config('services.recaptcha_v3.enabled') && filled(config('services.recaptcha_v3.site_key')))'
                        data-recaptcha-site-key="{{ (string) config('services.recaptcha_v3.site_key') }}"
                        data-recaptcha-action="{{ (string) config('services.recaptcha_v3.action', 'login') }}">
                        @csrf

                        <!-- Campo oculto: Rol de Usuario (valor por defecto: administrador) -->
                        <input type="hidden" name="role" value="administrador">
                        <input id="recaptcha_token" type="hidden" name="recaptcha_token" value="">

                        <!-- Campo: Correo Electrónico -->
                        <div class="form-field form-field--float">
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                                autocomplete="email" autofocus class="input-control input-control--float" placeholder=" "
                                aria-describedby="emailError" aria-invalid="false">
                            <label for="email" class="float-label">Correo electrónico</label>
                            <small id="emailError" class="error-text" role="alert"></small>
                        </div>

                        <!-- Campo: Contraseña (label integrado) -->
                        <div class="form-field form-field--float">
                            <input id="password" type="password" name="password" required minlength="6"
                                autocomplete="current-password" class="input-control input-control--float input-has-suffix"
                                placeholder=" " aria-describedby="passwordError capsHint" aria-invalid="false">
                            <label for="password" class="float-label">Contraseña</label>
                            <button type="button" id="togglePassword" class="input-toggle"
                                aria-label="Mostrar u ocultar contraseña">Ver</button>
                            <small id="passwordError" class="error-text" role="alert"></small>
                            <div id="capsHint" class="caps-hint">Bloq Mayús activado</div>
                        </div>

                        <div class="auth-row" style="justify-content: flex-end;">
                            <a href="{{ route('password.request') }}" class="link">¿Olvidaste tu contraseña?</a>
                        </div>

                        <div class="auth-row" style="justify-content: flex-end;">
                            <a href="{{ route('verification.resend.form') }}" class="link">¿No verificaste tu cuenta? Reenviar correo</a>
                        </div>

                        <!-- Botón de envío -->
                        <div class="form-field form-field--submit">
                            <button type="submit" class="btn-primary-login">Iniciar sesión</button>
                        </div>

                        <h5 style="text-align: center; font-weight: bold;">ó</h5>

                        <div class="form-field form-field--submit">
                            <a href="{{ route('register') }}" class="btn-register">Registrate</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Columna derecha: ilustración a pantalla completa -->
            <div class="auth-illustration auth-illustration--login"></div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/auth/login.js') }}" defer></script>
@endsection
