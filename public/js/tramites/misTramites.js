document.addEventListener("DOMContentLoaded", function () {
    initBusqueda();
    initAnimacionEntrada();
    initBotonesOrdenPago();
});

/* ── Búsqueda en tiempo real ── */
function initBusqueda() {
    const input = document.getElementById("misTramites-search-input");
    const clearBtn = document.getElementById("misTramites-search-clear");
    const cards = document.querySelectorAll(".solicitud-card");
    const emptyFiltro = document.querySelector(".empty-state-filtro");
    const grid = document.querySelector(".mis-tramites-grid");

    if (!input || !cards.length) return;

    function filtrar() {
        const termino = input.value.trim().toLowerCase();
        let visibles = 0;

        cards.forEach(function (card) {
            const nombre = (card.dataset.nombre || "").toLowerCase();
            const coincide = !termino || nombre.includes(termino);
            card.hidden = !coincide;
            if (coincide) visibles++;
        });

        if (emptyFiltro) {
            emptyFiltro.hidden = visibles > 0;
        }
        if (grid) {
            grid.hidden = visibles === 0;
        }

        clearBtn.style.display = termino ? "flex" : "none";
    }

    input.addEventListener("input", filtrar);

    clearBtn?.addEventListener("click", function () {
        input.value = "";
        input.focus();
        filtrar();
    });
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
        targets: [".page-header", ".mis-tramites-search"],
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
