document.addEventListener("DOMContentLoaded", function () {
    initAnimacionEntrada();
    initMatchingDocumentosPersonales();
    initSelectorPredio();
    initActualizacionProgreso();
    initEnvioSolicitud();
    initAcordeonRequisitos();
});

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

    // 2) Para requisitos NO cumplidos, mostrar aviso-perfil (dirige a Mi Perfil)
    requisitos.forEach(function (requisito) {
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
            agregarAvisoPerfil(requisito, "revision");
        } else {
            agregarAvisoPerfil(requisito, "faltante");
        }
    });

    actualizarProgresoGlobal();
}

/**
 * Agrega un mensaje al requisito indicando que debe completarse desde el perfil.
 * @param {HTMLElement} requisito - Elemento del requisito
 * @param {string} tipo - "revision" (subido pero no aprobado) o "faltante" (no subido)
 */
function agregarAvisoPerfil(requisito, tipo) {
    // Evitar duplicados
    if (requisito.querySelector(".aviso-perfil")) return;

    const cuerpo = requisito.querySelector(
        ".requisito-cumplimiento__cuerpo-inner",
    );
    if (!cuerpo) return;

    const aviso = document.createElement("div");
    aviso.className = "aviso-perfil";

    if (tipo === "revision") {
        aviso.innerHTML =
            '<i class="fa-solid fa-circle-exclamation me-1"></i>' +
            "Ya subiste este documento pero aún está en revisión o fue rechazado. " +
            '<a href="' +
            baseUrlPerfil +
            '" class="aviso-perfil__link">Revisa su estado en Mi Perfil</a>.';
    } else {
        aviso.innerHTML =
            '<i class="fa-solid fa-circle-info me-1"></i>' +
            "Este requisito se cubre desde tu perfil. " +
            '<a href="' +
            baseUrlPerfil +
            '" class="aviso-perfil__link">Ve a Mi Perfil</a> para cargar el documento faltante.';
    }

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

        // 3) Aviso-perfil para los que no cubrió ningún documento aprobado
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
                agregarAvisoPerfil(requisito, "revision");
            } else {
                agregarAvisoPerfil(requisito, "faltante");
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

    // Eliminar aviso-perfil si existe (se volverá a evaluar)
    const aviso = requisito.querySelector(".aviso-perfil");
    if (aviso) aviso.remove();

    const badge = requisito.querySelector(".badge-estado");
    if (badge) {
        badge.className = "badge-estado badge-estado--pendiente";
        badge.innerHTML =
            '<i class="fa-solid fa-circle-exclamation me-1"></i>Pendiente';
    }

    expandirRequisito(requisito);
}

/* ── Estado de cumplimiento y barra de progreso ── */
function initActualizacionProgreso() {
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
