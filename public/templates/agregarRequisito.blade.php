@extends('layouts.admin')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/requisitos/agregarRequisito.css') }}">

    <div class="main-container">
        <div class="page-header">
            <div class="header-content">
                <img src="{{ asset('images/escudoBlanco.png') }}" alt="Escudo de Salamanca" class="header-escudo">
                <div class="header-main">
                    <h1 class="page-title">Agregar requisito</h1>
                    <p class="page-subtitle">Registra un nuevo requisito con su nombre.</p>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-times-circle me-2"></i>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-form-header">
                <div class="card-form-header-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div>
                    <p class="card-form-header-title">Datos del requisito</p>
                    <p class="card-form-header-sub">Todos los campos son obligatorios.</p>
                </div>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('registrarRequisito') }}" id="form-agregar-requisito" novalidate>
                    @csrf

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nombre" class="form-label">
                                <i class="fas fa-font me-1"></i>Nombre del requisito
                            </label>
                            <input type="text" name="nombre" id="nombre"
                                class="form-control @error('nombre') is-invalid @enderror"
                                value="{{ old('nombre') }}" maxlength="255" required
                                autocomplete="off" placeholder="Ej. Acta de nacimiento">
                            <div class="field-footer">
                                <span class="field-message">
                                    @if ($errors->has('nombre'))
                                        <span class="field-error"><i class="fas fa-circle-exclamation me-1"></i>{{ $errors->first('nombre') }}</span>
                                    @else
                                        <span class="field-hint">Debe ser un nombre único</span>
                                    @endif
                                </span>
                                <span class="char-counter" id="counter-nombre"></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('indexRequisitos') }}" class="btn-accion btn-regresar">
                            <i class="fas fa-arrow-left"></i>Regresar
                        </a>
                        <button type="submit" class="btn-accion btn-guardar" id="btn-guardar-requisito">
                            <i class="fas fa-floppy-disk"></i>Guardar requisito
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/requisitos/agregarRequisito.js') }}"></script>
@endsection
