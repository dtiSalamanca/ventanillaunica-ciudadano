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
                        <img src="{{ asset('images/escudoArma.png') }}" alt="Escudo de armas" class="auth-logo">
                        <h1 class="auth-title">SISTEMA DE ADMINISTRACIÓN DE LA VENTANILLA ÚNICA</h1>
                        <p class="auth-subtitle">Reenvía el correo de verificación de tu cuenta.</p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success" role="status">
                            <span class="alert-icon" aria-hidden="true">&check;</span>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    @error('email')
                        <div class="alert alert-danger" role="alert">{{ $message }}</div>
                    @enderror

                    <div id="recaptchaErrorContainer"
                        class="alert alert-danger @unless ($errors->has('recaptcha_token')) is-hidden @endunless" role="alert">
                        <span id="recaptchaError">{{ $errors->first('recaptcha_token') }}</span>
                    </div>

                    <form id="resendVerificationForm" method="POST" action="{{ route('verification.resend') }}" novalidate
                        data-recaptcha-enabled='@json((bool) config('services.recaptcha_v3.enabled') && filled(config('services.recaptcha_v3.site_key')))'
                        data-recaptcha-site-key="{{ (string) config('services.recaptcha_v3.site_key') }}"
                        data-recaptcha-action="verification_resend">
                        @csrf
                        <input id="recaptcha_token" type="hidden" name="recaptcha_token" value="">

                        <!-- Campo: Correo Electrónico -->
                        <div class="form-field form-field--float">
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                                autocomplete="email" autofocus class="input-control input-control--float" placeholder=" "
                                aria-describedby="emailError" aria-invalid="false">
                            <label for="email" class="float-label">Correo electrónico</label>
                            <small id="emailError" class="error-text" role="alert"></small>
                        </div>

                        <!-- Botón de envío -->
                        <div class="form-field form-field--submit">
                            <button type="submit" class="btn-primary-login">Reenviar correo de verificación</button>
                        </div>

                        <div class="form-field form-field--submit">
                            <a href="{{ route('login') }}" class="btn-register">Volver a iniciar sesión</a>
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
    <script src="{{ asset('js/auth/resend-verification.js') }}" defer></script>
@endsection
