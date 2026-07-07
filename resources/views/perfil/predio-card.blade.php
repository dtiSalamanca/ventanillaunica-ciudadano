@php
    $estatusPredio = (int) $predio->estatus_predio;
    $documentosPredioCargados = $predio->documentos->keyBy('fk_cat_documento_predio');
    $totalDocumentosPredio = $catalogoPredios->count();
    $completadosPredio = $catalogoPredios->filter(
        fn ($doc) => $documentosPredioCargados->has($doc->id_documento_predio)
    )->count();
    $tieneErrorCorreccion = $errors->has('clave_predio_'.$predio->id_predio);
@endphp
<div class="predio-card" data-predio-id="{{ $predio->id_predio }}" data-estatus-predio="{{ $estatusPredio }}">
    <button class="predio-card__header" type="button" aria-expanded="{{ $tieneErrorCorreccion ? 'true' : 'false' }}">
        <div class="predio-card__info">
            <span class="predio-card__icono"><i class="fas fa-house"></i></span>
            <span class="predio-card__textos">
                <span class="predio-card__clave">{{ $predio->clave_predio }}</span>
                <span class="predio-card__resumen">{{ $completadosPredio }} de {{ $totalDocumentosPredio }} documentos cargados</span>
            </span>
        </div>
        <div class="predio-card__acciones-header">
            @if ($estatusPredio === 2)
                <span class="badge-estatus badge-estatus--aprobado predio-card__badge"><i class="fas fa-circle-check me-1"></i>Aprobado</span>
            @elseif ($estatusPredio === 1)
                <span class="badge-estatus badge-estatus--revision predio-card__badge"><i class="fas fa-hourglass-half me-1"></i>En revisión</span>
            @else
                <span class="badge-estatus badge-estatus--rechazado predio-card__badge"><i class="fas fa-circle-xmark me-1"></i>Rechazado</span>
            @endif
            <i class="fas fa-chevron-down predio-card__chevron"></i>
        </div>
    </button>

    <div class="predio-card__body" @if (!$tieneErrorCorreccion) hidden @endif>
        @if ($estatusPredio === 0)
            <form action="{{ route('actualizarPredio', $predio->id_predio) }}"
                  method="POST"
                  class="form-agregar-predio form-corregir-predio">
                @csrf
                <div class="form-agregar-predio__campo">
                    <label for="clave_predio_{{ $predio->id_predio }}">Corregir clave catastral</label>
                    <input type="text"
                           name="clave_predio_{{ $predio->id_predio }}"
                           id="clave_predio_{{ $predio->id_predio }}"
                           value="{{ old('clave_predio_'.$predio->id_predio, $predio->clave_predio) }}"
                           placeholder="Ej. 123-456-789-000"
                           maxlength="255"
                           required>
                    @error('clave_predio_'.$predio->id_predio)
                        <span class="form-agregar-predio__error">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-agregar-predio__acciones">
                    <button type="submit" class="btn-accion btn-accion--cargar">
                        <i class="fas fa-rotate-right me-1"></i>Corregir y reenviar
                    </button>
                </div>
            </form>
        @endif

        @if ($estatusPredio !== 2)
            <form action="{{ route('eliminarPredio', $predio->id_predio) }}"
                  method="POST"
                  class="form-eliminar-predio">
                @csrf
                <button type="submit" class="btn-accion btn-accion--eliminar">
                    <i class="fas fa-trash me-1"></i>Eliminar predio
                </button>
            </form>
        @endif

        @if ($catalogoPredios->isEmpty())
            <p class="mensaje-vacio"><i class="fas fa-circle-info me-1"></i>No hay documentos de predios disponibles en este momento.</p>
        @else
            @if ($estatusPredio !== 2)
                <p class="mensaje-vacio">
                    <i class="fas fa-circle-info me-1"></i>
                    @if ($estatusPredio === 0)
                        Este predio fue rechazado. Corrige y reenvía la clave para que sea validado antes de cargar sus documentos.
                    @elseif ($estatusPredio === 1)
                        Este predio está en revisión. Una vez aprobado podrás cargar sus documentos.
                    @endif
                </p>
            @endif
            <div class="documentos-lista">
                @foreach ($catalogoPredios as $documento)
                    @php
                        $cargado = $documentosPredioCargados->get($documento->id_documento_predio);
                        $estatusDoc = $cargado ? 'cargado' : 'pendiente';
                        $fechaVencimiento = $cargado ? $cargado->created_at->copy()->addMonths($documento->vigencia_meses) : null;
                        $diasRestantes = $fechaVencimiento ? now()->diffInDays($fechaVencimiento, false) : null;
                        $estatus = $cargado ? (int) $cargado->estatus_documento : null;
                    @endphp
                    <div class="documento-card {{ $cargado ? 'documento-card--cargado' : '' }}"
                         data-estatus="{{ $estatusDoc }}"
                         data-catalogo-id="{{ $documento->id_documento_predio }}"
                         data-predio-id="{{ $predio->id_predio }}"
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
                                    <span class="documento-card__fecha"><i class="fas fa-calendar-check me-1"></i>Cargado el {{ $cargado->created_at->format('d/m/Y') }}</span>
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
                                <a href="{{ route('descargarDocumentoPredio', $cargado->id_documento_predio) }}"
                                   class="btn-accion btn-accion--ver"
                                   target="_blank"
                                   title="Ver documento">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if ($estatus === 0 && $estatusPredio === 2)
                                    <form action="{{ route('subirDocumentoPredio', [$predio->id_predio, $documento->id_documento_predio]) }}"
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
                                @if ($estatusPredio === 2)
                                    <form action="{{ route('subirDocumentoPredio', [$predio->id_predio, $documento->id_documento_predio]) }}"
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
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>