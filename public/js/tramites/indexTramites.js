document.addEventListener("DOMContentLoaded", function () {
    initFiltroDependencias();
    initBusquedaTramites();
    initAcordeonesRequisitos();
});

let filtroDependenciaActual = "todas";
let terminoBusquedaActual = "";

/* ── Filtrado por dependencia (sidebar + tabs móviles) ── */
function initFiltroDependencias() {
    const sidebarItems = document.querySelectorAll(".tramites-sidebar-item");
    const tabItems = document.querySelectorAll(".tramites-tab-item");

    sidebarItems.forEach((btn) => {
        btn.addEventListener("click", () => {
            filtroDependenciaActual = btn.dataset.dependencia;
            activarFiltro(sidebarItems, tabItems, filtroDependenciaActual);
        });
    });

    tabItems.forEach((btn) => {
        btn.addEventListener("click", () => {
            filtroDependenciaActual = btn.dataset.dependencia;
            activarFiltro(sidebarItems, tabItems, filtroDependenciaActual);
        });
    });
}

function activarFiltro(sidebarItems, tabItems, dependencia) {
    sidebarItems.forEach((b) => {
        b.classList.toggle("active", b.dataset.dependencia === dependencia);
    });
    tabItems.forEach((b) => {
        b.classList.toggle("active", b.dataset.dependencia === dependencia);
    });
    animarItemActivo(dependencia);
    aplicarFiltros();
}

/* ── Animación del item de dependencia seleccionado ── */
function animarItemActivo(dependencia) {
    if (typeof anime === "undefined" || prefiereMovimientoReducido()) return;

    const itemsActivos = document.querySelectorAll(
        `.tramites-sidebar-item[data-dependencia="${dependencia}"], .tramites-tab-item[data-dependencia="${dependencia}"]`
    );

    anime({
        targets: itemsActivos,
        scale: [0.94, 1],
        duration: 300,
        easing: "easeOutBack",
    });
}

function prefiereMovimientoReducido() {
    return window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
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
    const cardsVisibles = [];

    cards.forEach((card) => {
        const coincideDependencia =
            filtroDependenciaActual === "todas" ||
            card.dataset.dependencia === filtroDependenciaActual;
        const coincideBusqueda =
            terminoBusquedaActual === "" ||
            card.dataset.nombre.includes(terminoBusquedaActual);

        const mostrar = coincideDependencia && coincideBusqueda;
        card.style.display = mostrar ? "" : "none";
        if (mostrar) cardsVisibles.push(card);
    });

    const vacio = document.querySelector(".empty-state-filtro");
    if (vacio) vacio.hidden = cardsVisibles.length > 0;

    animarEntradaTarjetas(cardsVisibles);
}

/* ── Animación de entrada (fade + slide escalonado) para las tarjetas y sus elementos internos ── */
const SELECTOR_ELEMENTOS_INTERNOS_TARJETA =
    ".tramite-card-icono, .tramite-card-info, .badge-requisitos, .tramite-card-descripcion, .tramite-accordion, .tramite-card-footer";

function animarEntradaTarjetas(cards) {
    if (!cards.length) return;

    if (typeof anime === "undefined" || prefiereMovimientoReducido()) {
        cards.forEach((card) => {
            card.style.opacity = "";
            card.querySelectorAll(SELECTOR_ELEMENTOS_INTERNOS_TARJETA).forEach((el) => (el.style.opacity = ""));
        });
        return;
    }

    cards.forEach((card, index) => {
        const inicioCard = index * 60;

        anime({
            targets: card,
            opacity: [0, 1],
            translateY: [18, 0],
            duration: 420,
            delay: inicioCard,
            easing: "easeOutQuad",
        });

        anime({
            targets: card.querySelectorAll(SELECTOR_ELEMENTOS_INTERNOS_TARJETA),
            opacity: [0, 1],
            translateY: [10, 0],
            duration: 350,
            delay: anime.stagger(45, { start: inicioCard + 90 }),
            easing: "easeOutQuad",
        });
    });
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
