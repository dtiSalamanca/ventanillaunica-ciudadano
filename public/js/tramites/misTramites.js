document.addEventListener("DOMContentLoaded", function () {
    initAcordeon();
    initBusqueda();
    initFiltrosEstatus();
    initAnimacionEntrada();
    initBotonesOrdenPago();
    initResaltarNueva();
});

/* ── Resaltar solicitud recién creada (?nueva=ID) ── */
function initResaltarNueva() {
    const params = new URLSearchParams(window.location.search);
    const idNueva = params.get("nueva");
    if (!idNueva) return;

    const card = document.getElementById("solicitud-" + idNueva);
    if (!card) return;

    card.classList.add("solicitud-card--nueva");

    // Esperar a que termine la animación de entrada antes de hacer scroll
    // y abrir automáticamente el detalle de la solicitud recién creada.
    setTimeout(function () {
        card.scrollIntoView({ behavior: "smooth", block: "center" });
        abrirCard(card);
    }, 700);
}

/* ── Estado compartido de búsqueda y filtros ── */
let terminoBusqueda = "";
let estatusActivo = "todos";

/* ── Acordeón: expandir/colapsar (uno a la vez) ── */
function initAcordeon() {
    const cards = document.querySelectorAll(".solicitud-card");
    if (!cards.length) return;

    cards.forEach(function (card) {
        const header = card.querySelector(".solicitud-card-header");
        if (!header) return;

        header.addEventListener("click", function () {
            const estaAbierta = card.classList.contains("is-open");
            cerrarCards();
            if (!estaAbierta) {
                abrirCard(card);
            }
        });
    });
}

function abrirCard(card) {
    card.classList.add("is-open");
    const wrap = card.querySelector(".solicitud-card-detalle-wrap");
    if (wrap) wrap.classList.add("is-open");
    const header = card.querySelector(".solicitud-card-header");
    if (header) header.setAttribute("aria-expanded", "true");
}

function cerrarCards() {
    document
        .querySelectorAll(".solicitud-card.is-open")
        .forEach(function (card) {
            card.classList.remove("is-open");
            const wrap = card.querySelector(".solicitud-card-detalle-wrap");
            if (wrap) wrap.classList.remove("is-open");
            const header = card.querySelector(".solicitud-card-header");
            if (header) header.setAttribute("aria-expanded", "false");
        });
}

/* ── Búsqueda en tiempo real ── */
function initBusqueda() {
    const input = document.getElementById("misTramites-search-input");
    const clearBtn = document.getElementById("misTramites-search-clear");

    if (!input) return;

    input.addEventListener("input", function () {
        terminoBusqueda = input.value.trim();
        aplicarFiltros();
    });

    clearBtn?.addEventListener("click", function () {
        input.value = "";
        terminoBusqueda = "";
        input.focus();
        aplicarFiltros();
    });
}

/* ── Filtros por estatus ── */
function initFiltrosEstatus() {
    const chips = document.querySelectorAll(".filtro-chip");
    if (!chips.length) return;

    actualizarConteos();

    chips.forEach(function (chip) {
        chip.addEventListener("click", function () {
            estatusActivo = chip.dataset.estatusFiltro;
            chips.forEach(function (c) {
                c.classList.toggle("is-active", c === chip);
            });
            aplicarFiltros();
        });
    });
}

/* Conteos por estatus para cada chip */
function actualizarConteos() {
    const cards = document.querySelectorAll(".solicitud-card");
    const conteos = { todos: cards.length };

    cards.forEach(function (card) {
        const estatus = card.dataset.estatus || "";
        conteos[estatus] = (conteos[estatus] || 0) + 1;
    });

    document.querySelectorAll(".filtro-count").forEach(function (el) {
        el.textContent = conteos[el.dataset.conteo] || 0;
    });
}

/* Aplica búsqueda + filtro de estatus combinados */
function aplicarFiltros() {
    const cards = document.querySelectorAll(".solicitud-card");
    const emptyFiltro = document.querySelector(".empty-state-filtro");
    const grid = document.querySelector(".mis-tramites-grid");
    const clearBtn = document.getElementById("misTramites-search-clear");
    const termino = terminoBusqueda.toLowerCase();
    let visibles = 0;

    cards.forEach(function (card) {
        const nombre = (card.dataset.nombre || "").toLowerCase();
        const estatus = card.dataset.estatus || "";
        const coincideBusqueda = !termino || nombre.includes(termino);
        const coincideEstatus =
            estatusActivo === "todos" || estatus === estatusActivo;
        const visible = coincideBusqueda && coincideEstatus;

        card.hidden = !visible;
        if (visible) visibles++;
    });

    if (emptyFiltro) emptyFiltro.hidden = visibles > 0;
    if (grid) grid.hidden = visibles === 0;
    if (clearBtn) clearBtn.style.display = terminoBusqueda ? "flex" : "none";
}

/* ── Botón "Generar orden de pago" ── */
function initBotonesOrdenPago() {
    const botones = document.querySelectorAll(".btn-orden-pago");
    if (!botones.length) return;

    botones.forEach(function (boton) {
        boton.addEventListener("click", function () {
            if (typeof Swal === "undefined") return;
            Swal.fire({
                icon: "info",
                title: "Generar orden de pago",
                text: "Esta opción estará disponible próximamente. Podrás generar tu orden de pago para este trámite una vez esté habilitada.",
                confirmButtonText: "Entendido",
                confirmButtonColor: "#601028",
            });
        });
    });
}

/* ── Animación de entrada ── */
function initAnimacionEntrada() {
    if (typeof anime === "undefined") return;

    anime({
        targets: [".page-header", ".mis-tramites-toolbar"],
        opacity: [0, 1],
        translateY: [18, 0],
        duration: 550,
        delay: anime.stagger(120),
        easing: "easeOutQuad",
    });

    const cards = document.querySelectorAll(".solicitud-card");
    if (cards.length) {
        anime({
            targets: cards,
            opacity: [0, 1],
            translateY: [18, 0],
            duration: 550,
            delay: anime.stagger(90, { start: 360 }),
            easing: "easeOutQuad",
        });
    }
}
