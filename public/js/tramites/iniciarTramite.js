document.addEventListener("DOMContentLoaded", function () {
    initAnimacionEntrada();
    initMatchingDocumentosPersonales();
    initSelectorPredio();
    initOpcionesRequisitos();
    initActualizacionProgreso();
    initValidacionArchivos();
    initEnvioSolicitud();
    initAcordeonRequisitos();
});

const MAX_BYTES_ARCHIVO = 10 * 1024 * 1024;
const baseUrlPerfil = "/perfiles/mi-perfil";

/* ── Entrada escalonada de la vista (fade + slide) ── */
function initAnimacionEntrada() {
    if (typeof anime === "undefined") return;

    anime({
        targets: [".page-header", ".btn-volver", ".tramite-resumen", ".card"],
        opacity: [0, 1],
        translateY: [18, 0],
        duration: 550,
        delay: anime.stagger(120),
        easing: "easeOutQuad",
    });

    const requisitos = document.querySelectorAll(".requisito-cumplimiento");
    const inicioRequisitos = 360;

    if (requisitos.length) {
        anime({
            targets: requisitos,
            opacity: [0, 1],
            translateY: [18, 0],
            duration: 550,
            delay: anime.stagger(90, { start: inicioRequisitos }),
            easing: "easeOutQuad",
        });
    }

    const acciones = document.querySelector(".acciones-finales");
    if (acciones) {
        anime({
            targets: acciones,
            opacity: [0, 1],
            translateY: [10, 0],
            duration: 450,
            delay: inicioRequisitos + requisitos.length * 90 + 160,
            easing: "easeOutQuad",
        });
    }
}

/* ── Matching automático: documentos personales aprobados vs requisitos ── */
function initMatchingDocumentosPersonales() {
    const lista = document.querySelector(".requisitos-cumplimiento-lista");
    if (!lista) return;

    let nombresPersonales = [];
    let nombresNoAprobados = [];
    try {
        nombresPersonales = JSON.parse(
            lista.dataset.personalDocumentos || "[]",
        );
        nombresNoAprobados = JSON.parse(
            lista.dataset.personalNoAprobados || "[]",
        );
    } catch (e) {
        return;
    }

    // 1) Marcar como cumplidos los que coinciden con docs aprobados
    const requisitos = document.querySelectorAll(".requisito-cumplimiento");

    requisitos.forEach(function (requisito) {
        const nombreRequisito = (requisito.dataset.nombreRequisito || "")
            .trim()
            .toLowerCase();
        if (!nombreRequisito) return;

        const coincide = nombresPersonales.some(function (nombreDoc) {
            return (
                nombreDoc === nombreRequisito ||
                nombreRequisito.includes(nombreDoc) ||
                nombreDoc.includes(nombreRequisito)
            );
        });

        if (coincide) {
            marcarPrecumplidoPersonal(requisito, "Documento personal");
        }
    });

    // 2) Para requisitos que coinciden con documentos NO aprobados, mostrar aviso
    requisitos.forEach(function (requisito) {
        // Si ya está cumplido (por doc aprobado), no hacer nada
        if (requisito.classList.contains("requisito-cumplimiento--cumplido"))
            return;

        const nombreRequisito = (requisito.dataset.nombreRequisito || "")
            .trim()
            .toLowerCase();
        if (!nombreRequisito) return;

        const coincideNoAprobado = nombresNoAprobados.some(
            function (nombreDoc) {
                return (
                    nombreDoc === nombreRequisito ||
                    nombreRequisito.includes(nombreDoc) ||
                    nombreDoc.includes(nombreRequisito)
                );
            },
        );

        if (coincideNoAprobado) {
            agregarAvisoPerfil(requisito);
        }
    });

    actualizarProgresoGlobal();
}

/**
 * Agrega un mensaje al requisito indicando que el documento está en revisión
 * o rechazado, con un link para ir al perfil.
 */
function agregarAvisoPerfil(requisito) {
    // Evitar duplicados
    if (requisito.querySelector(".aviso-perfil")) return;

    const cuerpo = requisito.querySelector(
        ".requisito-cumplimiento__cuerpo-inner",
    );
    if (!cuerpo) return;

    const aviso = document.createElement("div");
    aviso.className = "aviso-perfil";
    aviso.innerHTML =
        '<i class="fa-solid fa-circle-exclamation me-1"></i>' +
        "Ya subiste este documento pero aún está en revisión o fue rechazado. " +
        '<a href="' +
        baseUrlPerfil +
        '" class="aviso-perfil__link">Revisa su estado en Mi Perfil</a>.';
    cuerpo.appendChild(aviso);
}

/* ── Selector de predio (trámites prediales) ── */

/**
 * Marca un requisito como pre-cumplido por tener documento personal aprobado.
 */
function marcarPrecumplidoPersonal(requisito, tipo) {
    // Si ya está pre-cumplido por predio, no sobreescribir
    if (requisito.classList.contains("requisito-cumplimiento--precumplido"))
        return;

    requisito.classList.add(
        "requisito-cumplimiento--cumplido",
        "requisito-cumplimiento--precumplido",
    );

    const opciones = requisito.querySelector(".requisito-opciones");
    const controles = requisito.querySelector(".requisito-controles");
    if (opciones) opciones.hidden = true;
    if (controles) controles.hidden = true;

    const badge = requisito.querySelector(".badge-estado");
    if (badge) {
        badge.className = "badge-estado badge-estado--cumplido";
        badge.innerHTML =
            '<i class="fa-solid fa-circle-check me-1"></i>Cumplido (' +
            tipo +
            ")";
    }

    colapsarRequisito(requisito);
}

/* ── Selector de predio (trámites prediales) ── */
function initSelectorPredio() {
    const selectPredio = document.getElementById("selector-predio");
    const seccionRequisitos = document.getElementById("seccion-requisitos");

    if (!selectPredio || !seccionRequisitos) return;

    selectPredio.addEventListener("change", function () {
        const opcionSeleccionada =
            selectPredio.options[selectPredio.selectedIndex];
        if (!opcionSeleccionada || !opcionSeleccionada.value) return;

        // Mostrar la sección de requisitos
        seccionRequisitos.hidden = false;

        // Obtener los nombres de documentos aprobados del predio (normalizados)
        let documentosPredio = [];
        try {
            documentosPredio = JSON.parse(
                opcionSeleccionada.dataset.documentos || "[]",
            );
        } catch (e) {
            documentosPredio = [];
        }

        // Resetear todos los requisitos que estaban pre-cumplidos
        document
            .querySelectorAll(".requisito-cumplimiento--precumplido")
            .forEach(function (req) {
                resetearPrecumplido(req);
            });

        // 1) Matching por documentos del predio
        const requisitos = document.querySelectorAll(".requisito-cumplimiento");

        requisitos.forEach(function (requisito) {
            const nombreRequisito = (requisito.dataset.nombreRequisito || "")
                .trim()
                .toLowerCase();

            if (!nombreRequisito) return;

            const coincidePredio = documentosPredio.some(function (nombreDoc) {
                return (
                    nombreDoc === nombreRequisito ||
                    nombreRequisito.includes(nombreDoc) ||
                    nombreDoc.includes(nombreRequisito)
                );
            });

            if (coincidePredio) {
                marcarPrecumplidoPersonal(requisito, "Predio");
            }
        });

        // 2) Matching por documentos personales (para los que no cubrió el predio)
        const listaReqs = document.querySelector(
            ".requisitos-cumplimiento-lista",
        );
        let nombresPersonales = [];
        try {
            nombresPersonales = JSON.parse(
                listaReqs?.dataset.personalDocumentos || "[]",
            );
        } catch (e) {
            nombresPersonales = [];
        }

        requisitos.forEach(function (requisito) {
            if (
                requisito.classList.contains(
                    "requisito-cumplimiento--precumplido",
                )
            )
                return;

            const nombreRequisito = (requisito.dataset.nombreRequisito || "")
                .trim()
                .toLowerCase();

            if (!nombreRequisito) return;

            const coincidePersonal = nombresPersonales.some(
                function (nombreDoc) {
                    return (
                        nombreDoc === nombreRequisito ||
                        nombreRequisito.includes(nombreDoc) ||
                        nombreDoc.includes(nombreRequisito)
                    );
                },
            );

            if (coincidePersonal) {
                marcarPrecumplidoPersonal(requisito, "Documento personal");
            }
        });

        // 3) Aviso de documentos no aprobados (para los que no cubrió nada)
        let nombresNoAprobados = [];
        try {
            nombresNoAprobados = JSON.parse(
                listaReqs?.dataset.personalNoAprobados || "[]",
            );
        } catch (e) {
            nombresNoAprobados = [];
        }

        requisitos.forEach(function (requisito) {
            if (
                requisito.classList.contains("requisito-cumplimiento--cumplido")
            )
                return;

            const nombreRequisito = (requisito.dataset.nombreRequisito || "")
                .trim()
                .toLowerCase();

            if (!nombreRequisito) return;

            const coincideNoAprobado = nombresNoAprobados.some(
                function (nombreDoc) {
                    return (
                        nombreDoc === nombreRequisito ||
                        nombreRequisito.includes(nombreDoc) ||
                        nombreDoc.includes(nombreRequisito)
                    );
                },
            );

            if (coincideNoAprobado) {
                agregarAvisoPerfil(requisito);
            }
        });

        actualizarProgresoGlobal();

        // Animar la entrada de los requisitos
        if (typeof anime !== "undefined") {
            anime({
                targets: ".requisito-cumplimiento",
                opacity: [0, 1],
                translateY: [18, 0],
                duration: 550,
                delay: anime.stagger(90),
                easing: "easeOutQuad",
            });

            const acciones = document.querySelector(".acciones-finales");
            if (acciones) {
                anime({
                    targets: acciones,
                    opacity: [0, 1],
                    translateY: [10, 0],
                    duration: 450,
                    delay: requisitos.length * 90 + 160,
                    easing: "easeOutQuad",
                });
            }
        }
    });
}

/**
 * Marca un requisito como pre-cumplido por tener documento de predio aprobado.
 * Lo deja fijo (solo lectura visual), sin opciones ni controles editables.
 */
/**
 * Revierte un requisito del estado pre-cumplido a su estado normal (pendiente).
 */
function resetearPrecumplido(requisito) {
    requisito.classList.remove(
        "requisito-cumplimiento--cumplido",
        "requisito-cumplimiento--precumplido",
    );

    const opciones = requisito.querySelector(".requisito-opciones");
    const controles = requisito.querySelector(".requisito-controles");

    if (opciones) opciones.hidden = false;
    if (controles) controles.hidden = false;

    const badge = requisito.querySelector(".badge-estado");
    if (badge) {
        badge.className = "badge-estado badge-estado--pendiente";
        badge.innerHTML =
            '<i class="fa-solid fa-circle-exclamation me-1"></i>Pendiente';
    }

    expandirRequisito(requisito);
}

/* ── Opciones de cumplimiento (radio + control dinámico) ── */
function initOpcionesRequisitos() {
    const requisitos = document.querySelectorAll(".requisito-cumplimiento");

    requisitos.forEach((requisito) => {
        const radios = requisito.querySelectorAll(".requisito-opcion__radio");
        const controles = requisito.querySelectorAll(".requisito-control");

        radios.forEach((radio) => {
            radio.addEventListener("change", () => {
                if (!radio.checked) return;

                // Habilitar y mostrar sólo el control del método elegido.
                controles.forEach((control) => {
                    const coincide = control.dataset.control === radio.value;
                    control.hidden = !coincide;

                    const input = control.querySelector("select, input");
                    if (input) input.disabled = !coincide;
                });

                actualizarEstadoRequisito(requisito);
            });
        });
    });
}

/* ── Estado de cumplimiento y barra de progreso ── */
function initActualizacionProgreso() {
    const requisitos = document.querySelectorAll(".requisito-cumplimiento");

    requisitos.forEach((requisito) => {
        const selects = requisito.querySelectorAll(
            ".requisito-control__select",
        );
        const archivo = requisito.querySelector(".requisito-control__archivo");

        selects.forEach((select) =>
            select.addEventListener("change", () =>
                actualizarEstadoRequisito(requisito),
            ),
        );

        if (archivo) {
            archivo.addEventListener("change", () =>
                actualizarEstadoRequisito(requisito),
            );
        }
    });

    actualizarProgresoGlobal();
}

function actualizarEstadoRequisito(requisito) {
    const radioActivo = requisito.querySelector(
        ".requisito-opcion__radio:checked",
    );
    const badge = requisito.querySelector(".badge-estado");

    let cumplido = false;

    if (radioActivo) {
        const control = requisito.querySelector(
            `.requisito-control[data-control="${radioActivo.value}"]`,
        );

        if (control) {
            const select = control.querySelector(".requisito-control__select");
            const archivo = control.querySelector(
                ".requisito-control__archivo",
            );

            if (select) {
                cumplido = select.value !== "" && select.value !== null;
            } else if (archivo) {
                cumplido = archivo.files.length > 0;
            }
        }
    }

    const yaCumplido = requisito.classList.contains(
        "requisito-cumplimiento--cumplido",
    );

    requisito.classList.toggle("requisito-cumplimiento--cumplido", cumplido);

    if (badge) {
        if (cumplido) {
            badge.className = "badge-estado badge-estado--cumplido";
            badge.innerHTML =
                '<i class="fa-solid fa-circle-check me-1"></i>Cumplido';
        } else {
            badge.className = "badge-estado badge-estado--pendiente";
            badge.innerHTML =
                '<i class="fa-solid fa-circle-exclamation me-1"></i>Pendiente';
        }
    }

    const botonReabrir = requisito.querySelector(
        ".requisito-cumplimiento__reabrir",
    );
    if (botonReabrir) botonReabrir.hidden = !cumplido;

    if (cumplido && !yaCumplido) {
        colapsarRequisito(requisito);
    } else if (!cumplido) {
        expandirRequisito(requisito);
    }

    actualizarProgresoGlobal();
}

/* ── Acordeón: colapsar el requisito al cumplirse, reabrir para editar ── */
function initAcordeonRequisitos() {
    const requisitos = document.querySelectorAll(".requisito-cumplimiento");

    requisitos.forEach((requisito) => {
        const cabecera = requisito.querySelector("[data-toggle-acordeon]");
        const botonReabrir = requisito.querySelector(
            ".requisito-cumplimiento__reabrir",
        );

        if (cabecera) {
            cabecera.addEventListener("click", () => {
                if (
                    requisito.classList.contains(
                        "requisito-cumplimiento--colapsado",
                    )
                ) {
                    expandirRequisito(requisito);
                }
            });
        }

        if (botonReabrir) {
            botonReabrir.addEventListener("click", (evento) => {
                evento.stopPropagation();
                expandirRequisito(requisito);
            });
        }
    });
}

function colapsarRequisito(requisito) {
    requisito.classList.add("requisito-cumplimiento--colapsado");
}

function expandirRequisito(requisito) {
    requisito.classList.remove("requisito-cumplimiento--colapsado");
}

function actualizarProgresoGlobal() {
    const requisitos = document.querySelectorAll(".requisito-cumplimiento");
    const total = requisitos.length;
    const cumplidos = Array.from(requisitos).filter((r) =>
        r.classList.contains("requisito-cumplimiento--cumplido"),
    ).length;

    const elActual = document.getElementById("progreso-actual");
    const elFill = document.getElementById("progreso-fill");

    if (elActual) elActual.textContent = cumplidos;

    // Habilitar/deshabilitar botón de envío
    const boton = document.getElementById("btn-enviar-solicitud");
    if (boton) {
        boton.disabled = cumplidos < total;
    }

    if (elFill) {
        const porcentaje =
            total > 0 ? Math.round((cumplidos / total) * 100) : 0;
        elFill.dataset.progreso = porcentaje;
        requestAnimationFrame(() => {
            elFill.style.width = `${porcentaje}%`;
        });
    }
}

/* ── Validación de archivos (PDF, máx. 10 MB) ── */
function initValidacionArchivos() {
    const archivos = document.querySelectorAll(".requisito-control__archivo");

    archivos.forEach((input) => {
        input.addEventListener("change", () => {
            if (input.files.length === 0) return;

            const archivo = input.files[0];
            const extension = archivo.name.split(".").pop().toLowerCase();

            if (extension !== "pdf") {
                mostrarError("Solo se permiten archivos PDF.");
                input.value = "";
                actualizarNombreArchivo(input);
                return;
            }

            if (archivo.size > MAX_BYTES_ARCHIVO) {
                mostrarError("El archivo no puede superar los 10 MB.");
                input.value = "";
                actualizarNombreArchivo(input);
                return;
            }

            actualizarNombreArchivo(input);

            const requisito = input.closest(".requisito-cumplimiento");
            if (requisito) actualizarEstadoRequisito(requisito);
        });
    });
}

/* ── Nombre del archivo seleccionado (selector personalizado) ── */
function actualizarNombreArchivo(input) {
    const nombreEl = input
        .closest(".archivo-selector")
        ?.querySelector(".archivo-selector__nombre");
    if (!nombreEl) return;

    const archivo = input.files[0];

    if (archivo) {
        nombreEl.textContent = archivo.name;
        nombreEl.classList.add("archivo-selector__nombre--seleccionado");
    } else {
        nombreEl.textContent = nombreEl.dataset.placeholder;
        nombreEl.classList.remove("archivo-selector__nombre--seleccionado");
    }
}

/* ── Envío de solicitud (concepto: Swal informativo) ── */
function initEnvioSolicitud() {
    const boton = document.getElementById("btn-enviar-solicitud");
    if (!boton) return;

    boton.addEventListener("click", () => {
        mostrarAviso(
            "Próximamente",
            "El envío de la solicitud aún no está disponible. Esta vista es un concepto de la interfaz.",
        );
    });
}

/* ── Helpers de alertas ── */
function mostrarError(mensaje) {
    if (window.Swal) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: mensaje,
            confirmButtonColor: "#dc2626",
        });
    } else {
        alert(mensaje);
    }
}

function mostrarAviso(titulo, texto) {
    if (window.Swal) {
        Swal.fire({
            icon: "info",
            title: titulo,
            text: texto,
            confirmButtonColor: "#1e5c50",
        });
    } else {
        alert(`${titulo}\n\n${texto}`);
    }
}
