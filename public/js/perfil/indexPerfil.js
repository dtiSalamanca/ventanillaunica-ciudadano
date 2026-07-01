document.addEventListener("DOMContentLoaded", function () {
    initBarraProgreso();
    initFiltroDocumentos();
    initTabs();
});

function initBarraProgreso() {
    const barra = document.querySelector(".progreso-barra-fill");
    if (!barra) return;

    const porcentaje = barra.dataset.progreso || 0;
    requestAnimationFrame(() => {
        barra.style.width = `${porcentaje}%`;
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
    const tabs = document.querySelectorAll('.profile-tabs__tab:not(.profile-tabs__tab--disabled)');
    if (tabs.length <= 1) return;

    tabs.forEach((tab) => {
        tab.addEventListener("click", () => {
            const panelId = tab.getAttribute("aria-controls");
            if (!panelId) return;

            document.querySelectorAll(".profile-tabs__tab").forEach((t) => {
                t.classList.remove("profile-tabs__tab--active");
                t.setAttribute("aria-selected", "false");
            });

            tab.classList.add("profile-tabs__tab--active");
            tab.setAttribute("aria-selected", "true");
        });
    });

    const tablist = document.querySelector(".profile-tabs");
    if (!tablist) return;

    tablist.addEventListener("keydown", (e) => {
        if (e.key !== "ArrowLeft" && e.key !== "ArrowRight") return;

        const enabledTabs = Array.from(tabs);
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
