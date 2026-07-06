document.addEventListener("DOMContentLoaded", function () {
    initBarraProgreso();
    initFiltroDocumentos();
    initTabs();
    initSubirDocumento();
    initPollingEstatus();
    initPollingEstatusPredios();
    initFormAgregarPredio();
    initAcordeonPredios();
    initEliminarPredio();
});

const INTERVALO_POLLING_MS = 15000;

function initPollingEstatus() {
    const url = window.perfilConfig?.urlEstatusDocumentos;
    if (!url) return;

    setInterval(() => consultarEstatusDocumentos(url), INTERVALO_POLLING_MS);
}

async function consultarEstatusDocumentos(url) {
    try {
        const respuesta = await fetch(url, {
            headers: { Accept: "application/json" },
        });
        if (!respuesta.ok) return;

        const datos = await respuesta.json();
        let huboCambios = false;

        datos.documentos.forEach((documento) => {
            const card = document.querySelector(
                `#panel-documentos .documento-card[data-catalogo-id="${documento.fk_documento_personal}"]`
            );
            if (!card) return;

            const estatusActual = card.dataset.estatusNum;
            if (estatusActual !== undefined && Number(estatusActual) === documento.estatus) {
                return;
            }

            actualizarCard(card, documento);
            huboCambios = true;
        });

        if (huboCambios) {
            actualizarContadores();
        }
    } catch {
        // Silencioso: se reintenta en el siguiente ciclo de polling.
    }
}

function initPollingEstatusPredios() {
    const url = window.perfilConfig?.urlEstatusPredios;
    if (!url) return;

    setInterval(() => consultarEstatusPredios(url), INTERVALO_POLLING_MS);
}

async function consultarEstatusPredios(url) {
    try {
        const respuesta = await fetch(url, {
            headers: { Accept: "application/json" },
        });
        if (!respuesta.ok) return;

        const datos = await respuesta.json();

        datos.predios.forEach((predio) => {
            const predioCard = document.querySelector(
                `.predio-card[data-predio-id="${predio.id_predio}"]`
            );
            if (!predioCard) return;

            if (Number(predioCard.dataset.estatusPredio) !== predio.estatus) {
                actualizarBadgePredio(predioCard, predio.estatus);
            }

            let huboCambios = false;

            predio.documentos.forEach((documento) => {
                const card = predioCard.querySelector(
                    `.documento-card[data-catalogo-id="${documento.fk_cat_documento_predio}"]`
                );
                if (!card) return;

                const estatusActual = card.dataset.estatusNum;
                if (estatusActual !== undefined && Number(estatusActual) === documento.estatus) {
                    return;
                }

                actualizarCard(card, documento);
                huboCambios = true;
            });

            if (huboCambios) {
                actualizarResumenPredio(predioCard);
            }
        });
    } catch {
        // Silencioso: se reintenta en el siguiente ciclo de polling.
    }
}

function actualizarBadgePredio(predioCard, estatus) {
    predioCard.dataset.estatusPredio = estatus;

    const badge = predioCard.querySelector(".predio-card__badge");
    if (badge) {
        badge.outerHTML = badgeEstatus(estatus).replace(
            'class="badge-estatus',
            'class="predio-card__badge badge-estatus'
        );
    }
}

function initBarraProgreso() {
    document.querySelectorAll(".progreso-barra-fill").forEach((barra) => {
        const porcentaje = barra.dataset.progreso || 0;
        requestAnimationFrame(() => {
            barra.style.width = `${porcentaje}%`;
        });
    });
}

function initFiltroDocumentos() {
    const botones = document.querySelectorAll(".filtro-btn");
    const cards = document.querySelectorAll(".documento-card");
    const mensajeVacio = document.querySelector(".mensaje-filtro-vacio");
    if (!botones.length || !cards.length) return;

    botones.forEach((btn) => {
        btn.addEventListener("click", () => {
            botones.forEach((b) => b.classList.remove("filtro-btn--active"));
            btn.classList.add("filtro-btn--active");

            const filtro = btn.dataset.filtro;
            let visibles = 0;

            cards.forEach((card) => {
                const mostrar =
                    filtro === "todos" || card.dataset.estatus === filtro;
                card.style.display = mostrar ? "" : "none";
                if (mostrar) visibles++;
            });

            if (mensajeVacio) {
                mensajeVacio.hidden = visibles > 0;
            }
        });
    });
}

function initTabs() {
    const sidebarItems = document.querySelectorAll(".perfil-sidebar__item");
    const tabItems = document.querySelectorAll(".perfil-tab-mobile");
    if (!sidebarItems.length && !tabItems.length) return;

    const activar = (seccion) => {
        sidebarItems.forEach((b) => {
            const activo = b.dataset.seccion === seccion;
            b.classList.toggle("perfil-sidebar__item--active", activo);
            b.setAttribute("aria-selected", String(activo));
        });
        tabItems.forEach((b) => {
            b.classList.toggle("perfil-tab-mobile--active", b.dataset.seccion === seccion);
        });

        document.querySelectorAll(".profile-tab-content").forEach((panel) => {
            panel.hidden = panel.id !== `panel-${seccion}`;
        });
    };

    sidebarItems.forEach((btn) => {
        btn.addEventListener("click", () => activar(btn.dataset.seccion));
    });
    tabItems.forEach((btn) => {
        btn.addEventListener("click", () => activar(btn.dataset.seccion));
    });

    const tablist = document.querySelector(".perfil-sidebar__list");
    if (!tablist) return;

    tablist.addEventListener("keydown", (e) => {
        if (e.key !== "ArrowLeft" && e.key !== "ArrowRight") return;

        const enabledTabs = Array.from(sidebarItems);
        const current = enabledTabs.findIndex(
            (t) => t.getAttribute("aria-selected") === "true"
        );
        if (current === -1) return;

        let next;
        if (e.key === "ArrowRight") {
            next = (current + 1) % enabledTabs.length;
        } else {
            next = (current - 1 + enabledTabs.length) % enabledTabs.length;
        }

        enabledTabs[next].focus();
        enabledTabs[next].click();
    });
}

function initSubirDocumento() {
    document.querySelectorAll(".form-subir-inline").forEach(bindFormSubir);
}

function bindFormSubir(form) {
    const input = form.querySelector(".input-archivo-oculto");
    const btn   = form.querySelector(".btn-trigger-archivo");
    const card  = form.closest(".documento-card");

    if (!input || !btn || !card) return;

    btn.addEventListener("click", () => input.click());

    input.addEventListener("change", async () => {
        if (input.files.length === 0) return;

        const archivo = input.files[0];
        const maxBytes = 10 * 1024 * 1024;
        const extension = archivo.name.split(".").pop().toLowerCase();

        if (extension !== "pdf") {
            mostrarError("Solo se permiten archivos PDF.");
            input.value = "";
            return;
        }

        if (archivo.size > maxBytes) {
            mostrarError("El archivo no puede superar los 10 MB.");
            input.value = "";
            return;
        }

        const nombreDocumento = card.querySelector(".documento-card__nombre")?.textContent?.trim() ?? "el documento";
        const confirmado = await confirmarSubida(archivo.name, nombreDocumento);
        if (!confirmado) {
            input.value = "";
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Subiendo…';

        const formData = new FormData(form);

        try {
            const respuesta = await fetch(form.action, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": window.adminConfig?.csrfToken ?? "",
                },
                body: formData,
            });

            const datos = await respuesta.json();

            if (!respuesta.ok) {
                const mensaje = datos.errors?.archivo?.[0]
                    ?? datos.message
                    ?? "Ocurrió un error al subir el documento.";
                mostrarError(mensaje);
                return;
            }

            actualizarCard(card, datos);

            const predioCard = card.closest(".predio-card");
            if (predioCard) {
                actualizarResumenPredio(predioCard);
            } else {
                actualizarContadores();
            }

        } catch {
            mostrarError("No se pudo conectar con el servidor. Intenta de nuevo.");
        } finally {
            input.value = "";
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-upload me-1"></i>Cargar';
        }
    });
}

function actualizarCard(card, datos) {
    // Icono verde
    card.classList.add("documento-card--cargado");
    card.dataset.estatus = "cargado";
    card.dataset.estatusNum = datos.estatus;

    // Ocultar vigencia hasta aprobación y agregar fecha de carga
    const meta = card.querySelector(".documento-card__meta");
    if (meta) {
        const vigencia = meta.querySelector(".documento-card__vigencia");
        if (vigencia && datos.estatus !== 2) vigencia.remove();

        if (!meta.querySelector(".documento-card__fecha")) {
            const fecha = document.createElement("span");
            fecha.className = "documento-card__fecha";
            fecha.innerHTML = `<i class="fas fa-calendar-check me-1"></i>Cargado el ${datos.fecha_registro}`;
            meta.appendChild(fecha);
        }
    }

    // Reemplazar sección de acciones
    const acciones = card.querySelector(".documento-card__acciones");
    if (!acciones) return;

    const badgeHtml = badgeEstatus(datos.estatus);
    const reenviarHtml = datos.estatus === 0
        ? `
        <form action="${datos.url_subir}"
              method="POST"
              enctype="multipart/form-data"
              class="form-subir-inline">
            <input type="hidden" name="_token" value="${window.adminConfig?.csrfToken ?? ""}">
            <input type="file"
                   name="archivo"
                   class="input-archivo-oculto"
                   accept=".pdf"
                   aria-label="Volver a subir documento">
            <button type="button" class="btn-accion btn-accion--cargar btn-trigger-archivo" title="Volver a subir">
                <i class="fas fa-rotate-right me-1"></i>Reenviar
            </button>
        </form>
        `
        : "";

    acciones.innerHTML = `
        ${badgeHtml}
        <a href="${datos.url_descargar}"
           class="btn-accion btn-accion--ver"
           target="_blank"
           title="Ver documento">
            <i class="fas fa-eye"></i>
        </a>
        ${reenviarHtml}
    `;

    const formReenviar = acciones.querySelector(".form-subir-inline");
    if (formReenviar) {
        bindFormSubir(formReenviar);
    }
}

function badgeEstatus(estatus) {
    if (estatus === 2) {
        return '<span class="badge-estatus badge-estatus--aprobado"><i class="fas fa-circle-check me-1"></i>Aprobado</span>';
    }
    if (estatus === 1) {
        return '<span class="badge-estatus badge-estatus--revision"><i class="fas fa-hourglass-half me-1"></i>En revisión</span>';
    }
    return '<span class="badge-estatus badge-estatus--rechazado"><i class="fas fa-circle-xmark me-1"></i>Rechazado</span>';
}

function actualizarContadores() {
    const total     = document.querySelectorAll("#panel-documentos .documento-card").length;
    const cargados  = document.querySelectorAll("#panel-documentos .documento-card--cargado").length;
    const pendientes = total - cargados;
    const porcentaje = total > 0 ? Math.round((cargados / total) * 100) : 0;

    const elTotal     = document.querySelector(".profile-stat:nth-child(1) .profile-stat__valor");
    const elCargados  = document.querySelector(".profile-stat--ok .profile-stat__valor");
    const elPendientes = document.querySelector(".profile-stat--pendiente .profile-stat__valor");
    const elPorcentaje = document.querySelector("#panel-documentos .documents-card__progreso-valor");
    const barra       = document.querySelector("#panel-documentos .progreso-barra-fill");

    if (elTotal)      elTotal.textContent      = total;
    if (elCargados)   elCargados.textContent   = cargados;
    if (elPendientes) elPendientes.textContent = pendientes;
    if (elPorcentaje) elPorcentaje.textContent = porcentaje + "%";
    if (barra)        barra.style.width        = porcentaje + "%";
}

function actualizarResumenPredio(predioCard) {
    const resumen = predioCard.querySelector(".predio-card__resumen");
    if (!resumen) return;

    const total = predioCard.querySelectorAll(".documento-card").length;
    const cargados = predioCard.querySelectorAll(".documento-card--cargado").length;

    resumen.textContent = `${cargados} de ${total} documentos cargados`;

    actualizarBarraPredios();
}

function actualizarBarraPredios() {
    const totalCards = document.querySelectorAll("#panel-predios .documento-card").length;
    const cargadosCards = document.querySelectorAll("#panel-predios .documento-card--cargado").length;
    const porcentaje = totalCards > 0 ? Math.round((cargadosCards / totalCards) * 100) : 0;

    const elPorcentaje = document.querySelector("#panel-predios .documents-card__progreso-valor");
    const barra = document.querySelector("#panel-predios .progreso-barra-fill");

    if (elPorcentaje) elPorcentaje.textContent = porcentaje + "%";
    if (barra)        barra.style.width        = porcentaje + "%";
}

function initFormAgregarPredio() {
    const form = document.getElementById("form-agregar-predio");
    const btnMostrar = document.getElementById("btn-mostrar-form-predio");
    const btnCancelar = document.getElementById("btn-cancelar-form-predio");
    if (!form || !btnMostrar || !btnCancelar) return;

    btnMostrar.addEventListener("click", () => {
        form.classList.remove("form-agregar-predio--oculto");
        form.querySelector("#clave_predio")?.focus();
    });

    btnCancelar.addEventListener("click", () => {
        form.reset();
        form.classList.add("form-agregar-predio--oculto");
    });
}

function initAcordeonPredios() {
    document.querySelectorAll(".predio-card__header").forEach((header) => {
        header.addEventListener("click", () => {
            const body = header.nextElementSibling;
            if (!body) return;

            const abierto = header.getAttribute("aria-expanded") === "true";
            header.setAttribute("aria-expanded", String(!abierto));
            body.hidden = abierto;
        });
    });
}

function initEliminarPredio() {
    document.querySelectorAll(".form-eliminar-predio").forEach((form) => {
        form.addEventListener("submit", (e) => {
            if (form.dataset.confirmado) return;
            e.preventDefault();

            const clave = form.closest(".predio-card")?.querySelector(".predio-card__clave")?.textContent ?? "este predio";

            if (window.Swal) {
                Swal.fire({
                    icon: "warning",
                    title: "¿Eliminar predio?",
                    text: `Se eliminará «${clave}» junto con sus documentos cargados.`,
                    showCancelButton: true,
                    confirmButtonText: "Eliminar",
                    cancelButtonText: "Cancelar",
                    confirmButtonColor: "#dc2626",
                }).then((resultado) => {
                    if (resultado.isConfirmed) {
                        form.dataset.confirmado = "1";
                        form.submit();
                    }
                });
            } else if (confirm(`¿Eliminar «${clave}» junto con sus documentos cargados?`)) {
                form.dataset.confirmado = "1";
                form.submit();
            }
        });
    });
}

function confirmarSubida(nombreArchivo, nombreDocumento) {
    if (!window.Swal) {
        return Promise.resolve(confirm(`¿Subir «${nombreArchivo}» para ${nombreDocumento}?`));
    }

    return Swal.fire({
        icon: "warning",
        title: "Confirmar envío",
        html: `¿Deseas subir el archivo <strong>${escapeHtml(nombreArchivo)}</strong> para <strong>${escapeHtml(nombreDocumento)}</strong>?<br><span class="swal2-confirm-subtitle">Una vez enviado, el documento quedará en revisión.</span>`,
        showCancelButton: true,
        confirmButtonText: "Sí, subir",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#601028",
        cancelButtonColor: "#64748b",
        reverseButtons: true,
        focusCancel: true,
    }).then((resultado) => resultado.isConfirmed);
}

function escapeHtml(texto) {
    const div = document.createElement("div");
    div.textContent = texto;
    return div.innerHTML;
}

function mostrarError(mensaje) {
    if (window.Swal) {
        Swal.fire({
            icon: "error",
            title: "Error al subir",
            text: mensaje,
            confirmButtonColor: "#dc2626",
        });
    } else {
        alert(mensaje);
    }
}
