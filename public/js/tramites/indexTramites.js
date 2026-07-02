document.addEventListener("DOMContentLoaded", function () {
    initFiltroDependencias();
    initBusquedaTramites();
    initAcordeonesRequisitos();
    initBotonesIniciarTramite();
});

let filtroDependenciaActual = "todas";
let terminoBusquedaActual = "";

/* ── Filtrado por dependencia (nav-tabs) ── */
function initFiltroDependencias() {
    const botones = document.querySelectorAll(".nav-tabs .nav-link");
    if (!botones.length) return;

    botones.forEach((btn) => {
        btn.addEventListener("click", () => {
            botones.forEach((b) => b.classList.remove("active"));
            btn.classList.add("active");
            filtroDependenciaActual = btn.dataset.dependencia;
            aplicarFiltros();
        });
    });
}

/* ── Búsqueda por nombre ── */
function initBusquedaTramites() {
    const input = document.getElementById("tramites-search-input");
    const clear = document.getElementById("tramites-search-clear");
    if (!input) return;

    input.addEventListener("input", () => {
        terminoBusquedaActual = input.value.trim().toLowerCase();
        if (clear) clear.style.display = terminoBusquedaActual ? "block" : "none";
        aplicarFiltros();
    });

    if (clear) {
        clear.addEventListener("click", () => {
            input.value = "";
            terminoBusquedaActual = "";
            clear.style.display = "none";
            input.focus();
            aplicarFiltros();
        });
    }
}

/* ── Combina filtro de dependencia + búsqueda ── */
function aplicarFiltros() {
    const cards = document.querySelectorAll(".tramite-card");
    let visibles = 0;

    cards.forEach((card) => {
        const coincideDependencia =
            filtroDependenciaActual === "todas" ||
            card.dataset.dependencia === filtroDependenciaActual;
        const coincideBusqueda =
            terminoBusquedaActual === "" ||
            card.dataset.nombre.includes(terminoBusquedaActual);

        const mostrar = coincideDependencia && coincideBusqueda;
        card.style.display = mostrar ? "" : "none";
        if (mostrar) visibles++;
    });

    const vacio = document.querySelector(".empty-state-filtro");
    if (vacio) vacio.hidden = visibles > 0;
}

/* ── Expandir / contraer requisitos (acordeón) ── */
function initAcordeonesRequisitos() {
    const botones = document.querySelectorAll(".tramite-accordion .accordion-button");
    if (!botones.length) return;

    botones.forEach((btn) => {
        btn.addEventListener("click", () => {
            const cuerpoId = btn.getAttribute("aria-controls");
            const cuerpo = cuerpoId ? document.getElementById(cuerpoId) : null;
            if (!cuerpo) return;

            const expandido = !btn.classList.contains("collapsed");

            btn.classList.toggle("collapsed", expandido);
            btn.setAttribute("aria-expanded", String(!expandido));
            cuerpo.classList.toggle("show", !expandido);
        });
    });
}

/* ── Botón Iniciar trámite (placeholder) ── */
function initBotonesIniciarTramite() {
    const botones = document.querySelectorAll(".btn-iniciar-tramite");
    if (!botones.length) return;

    botones.forEach((btn) => {
        btn.addEventListener("click", () => {
            const nombre = btn.dataset.nombre;

            if (window.Swal) {
                Swal.fire({
                    icon: "info",
                    title: "Próximamente",
                    html: `El inicio del trámite <strong>${nombre}</strong> estará disponible pronto.`,
                    confirmButtonColor: "#1e5c50",
                });
            }
        });
    });
}
