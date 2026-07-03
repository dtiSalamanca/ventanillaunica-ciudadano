document.addEventListener("DOMContentLoaded", function () {
    initAnimacionEntrada();
    initOpcionesRequisitos();
    initActualizacionProgreso();
    initValidacionArchivos();
    initEnvioSolicitud();
    initAcordeonRequisitos();
});

const MAX_BYTES_ARCHIVO = 10 * 1024 * 1024;

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
        const selects = requisito.querySelectorAll(".requisito-control__select");
        const archivo = requisito.querySelector(".requisito-control__archivo");

        selects.forEach((select) =>
            select.addEventListener("change", () => actualizarEstadoRequisito(requisito))
        );

        if (archivo) {
            archivo.addEventListener("change", () => actualizarEstadoRequisito(requisito));
        }
    });

    actualizarProgresoGlobal();
}

function actualizarEstadoRequisito(requisito) {
    const radioActivo = requisito.querySelector(".requisito-opcion__radio:checked");
    const badge = requisito.querySelector(".badge-estado");

    let cumplido = false;

    if (radioActivo) {
        const control = requisito.querySelector(
            `.requisito-control[data-control="${radioActivo.value}"]`
        );

        if (control) {
            const select = control.querySelector(".requisito-control__select");
            const archivo = control.querySelector(".requisito-control__archivo");

            if (select) {
                cumplido = select.value !== "" && select.value !== null;
            } else if (archivo) {
                cumplido = archivo.files.length > 0;
            }
        }
    }

    const yaCumplido = requisito.classList.contains("requisito-cumplimiento--cumplido");

    requisito.classList.toggle("requisito-cumplimiento--cumplido", cumplido);

    if (badge) {
        if (cumplido) {
            badge.className = "badge-estado badge-estado--cumplido";
            badge.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i>Cumplido';
        } else {
            badge.className = "badge-estado badge-estado--pendiente";
            badge.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i>Pendiente';
        }
    }

    const botonReabrir = requisito.querySelector(".requisito-cumplimiento__reabrir");
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
        const botonReabrir = requisito.querySelector(".requisito-cumplimiento__reabrir");

        if (cabecera) {
            cabecera.addEventListener("click", () => {
                if (requisito.classList.contains("requisito-cumplimiento--colapsado")) {
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
        r.classList.contains("requisito-cumplimiento--cumplido")
    ).length;

    const elActual = document.getElementById("progreso-actual");
    const elFill = document.getElementById("progreso-fill");

    if (elActual) elActual.textContent = cumplidos;

    if (elFill) {
        const porcentaje = total > 0 ? Math.round((cumplidos / total) * 100) : 0;
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
    const nombreEl = input.closest(".archivo-selector")?.querySelector(".archivo-selector__nombre");
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
        const total = document.querySelectorAll(".requisito-cumplimiento").length;
        const cumplidos = document.querySelectorAll(
            ".requisito-cumplimiento--cumplido"
        ).length;

        if (total > 0 && cumplidos < total) {
            mostrarAviso(
                "Aún faltan requisitos por cumplir",
                `Has completado ${cumplidos} de ${total} requisitos. Completa todos antes de enviar.`
            );
            return;
        }

        mostrarAviso(
            "Próximamente",
            "El envío de la solicitud aún no está disponible. Esta vista es un concepto de la interfaz."
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
