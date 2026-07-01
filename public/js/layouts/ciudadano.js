/**
 * Admin Layout - Scripts del layout administrativo
 * Sidebar toggle, dropdowns, temas, búsqueda, cortinilla.
 * Depende de: window.adminConfig (csrfToken, flashSuccess)
 */

(() => {
    const themeStorageKey = 'admin-theme';
    const legacyThemeStorageKey = 'theme';
    const themeVariablesStorageKey = 'admin-theme-vars';
    const defaultTheme = 'verde-esmeralda-oscuro';
    const rootElement = document.documentElement;

    try {
        const currentTheme = localStorage.getItem(themeStorageKey) || localStorage.getItem(
            legacyThemeStorageKey) || defaultTheme;
        rootElement.setAttribute('data-admin-theme', currentTheme);

        const storedVariables = localStorage.getItem(themeVariablesStorageKey);
        if (!storedVariables) {
            return;
        }

        const parsedVariables = JSON.parse(storedVariables);
        if (!parsedVariables || typeof parsedVariables !== 'object') {
            return;
        }

        Object.keys(parsedVariables).forEach((cssVariable) => {
            if (!cssVariable.startsWith('--theme-')) {
                return;
            }

            rootElement.style.setProperty(cssVariable, parsedVariables[cssVariable]);
        });
    } catch {}
})();

(function () {
    const config = window.adminConfig || {};
    const themeStorageKey = "admin-theme";
    const legacyThemeStorageKey = "theme";
    const themeVariablesStorageKey = "admin-theme-vars";
    const defaultTheme = "verde-esmeralda-oscuro";
    let currentTheme = defaultTheme;

    function getThemes() {
        const themes = window.adminThemes;
        if (!themes || typeof themes !== "object") {
            return {};
        }

        return themes;
    }

    function getStoredTheme() {
        try {
            const themes = getThemes();
            const storedTheme = localStorage.getItem(themeStorageKey);
            if (storedTheme && themes[storedTheme]) {
                return storedTheme;
            }

            const legacyStoredTheme = localStorage.getItem(
                legacyThemeStorageKey,
            );
            if (legacyStoredTheme && themes[legacyStoredTheme]) {
                return legacyStoredTheme;
            }
        } catch {
            return null;
        }

        return null;
    }

    function persistTheme(themeName, colors) {
        try {
            localStorage.setItem(themeStorageKey, themeName);
            localStorage.setItem(legacyThemeStorageKey, themeName);
            if (colors && typeof colors === "object") {
                localStorage.setItem(
                    themeVariablesStorageKey,
                    JSON.stringify(colors),
                );
            }
        } catch {
            // noop
        }
    }

    function hasStoredThemeVariables() {
        try {
            return Boolean(localStorage.getItem(themeVariablesStorageKey));
        } catch {
            return false;
        }
    }

    function markActiveTheme(themeName) {
        document.querySelectorAll(".theme-card").forEach((card) => {
            card.classList.toggle("active", card.dataset.theme === themeName);
        });
    }

    function applyTheme(themeName, options = {}) {
        const { persist = true } = options;
        const themes = getThemes();
        const colors = themes[themeName];

        if (!colors) {
            return false;
        }

        Object.keys(colors).forEach((variable) => {
            document.documentElement.style.setProperty(
                variable,
                colors[variable],
            );
        });

        document.documentElement.setAttribute("data-admin-theme", themeName);
        currentTheme = themeName;

        if (persist) {
            persistTheme(themeName, colors);
        }

        markActiveTheme(themeName);
        return true;
    }

    function showThemeToast(title, text) {
        if (!window.Swal) {
            return;
        }

        Swal.fire({
            icon: "success",
            title: title,
            text: text,
            timer: 1500,
            showConfirmButton: false,
            toast: true,
            position: "top-end",
        });
    }

    function applyStoredTheme() {
        const themes = getThemes();
        if (!Object.keys(themes).length) {
            return;
        }

        const needsThemeDataMigration = !hasStoredThemeVariables();
        const storedTheme = getStoredTheme() || defaultTheme;
        if (!applyTheme(storedTheme, { persist: needsThemeDataMigration })) {
            applyTheme(defaultTheme, { persist: needsThemeDataMigration });
        }
    }

    function applyThemeFromCard(card) {
        const themeName = card.dataset.theme;
        if (!themeName || themeName === currentTheme) {
            markActiveTheme(currentTheme);
            return;
        }

        if (!applyTheme(themeName)) {
            return;
        }

        const themeLabel =
            card.querySelector(".theme-name")?.textContent?.trim() || themeName;
        showThemeToast(
            "Tema aplicado",
            `Se ha aplicado el tema "${themeLabel}"`,
        );
    }

    function initializeThemeSelector() {
        const modalElement = document.getElementById(
            "modalPersonalizarColores",
        );
        if (!modalElement) {
            return;
        }

        modalElement.querySelectorAll(".theme-card").forEach((card) => {
            card.setAttribute("role", "button");
            card.setAttribute("tabindex", "0");
            card.setAttribute(
                "aria-label",
                `Aplicar tema ${card.querySelector(".theme-name")?.textContent?.trim() || ""}`,
            );
        });

        modalElement.addEventListener("click", function (event) {
            const card = event.target.closest(".theme-card");
            if (!card) {
                return;
            }

            applyThemeFromCard(card);
        });

        modalElement.addEventListener("keydown", function (event) {
            const card = event.target.closest(".theme-card");
            if (!card) {
                return;
            }

            if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();
                applyThemeFromCard(card);
            }
        });

        markActiveTheme(currentTheme);
    }

    function normalizeText(value) {
        return String(value || "")
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .toLowerCase()
            .trim();
    }

    function initializeSidebarSearch() {
        const searchInput = document.getElementById("sidebarSectionSearch");
        const clearButton = document.getElementById(
            "sidebarSectionSearchClear",
        );
        const navigationContainer = document.querySelector(
            "#layoutSidenav_nav .sb-sidenav-menu .nav",
        );

        if (!searchInput || !clearButton || !navigationContainer) {
            return;
        }

        const navLinks = Array.from(
            navigationContainer.querySelectorAll("a.nav-link"),
        );
        const headings = Array.from(
            navigationContainer.querySelectorAll(".sb-sidenav-menu-heading"),
        );

        const noResultsElement = document.createElement("div");
        noResultsElement.className = "sb-sidenav-search-no-results";
        noResultsElement.textContent = "No se encontraron secciones.";
        navigationContainer.appendChild(noResultsElement);

        function getHeadingVisibleLinks(headingElement) {
            const siblingElements = Array.from(navigationContainer.children);
            const startIndex = siblingElements.indexOf(headingElement);
            if (startIndex === -1) {
                return [];
            }

            const visibleLinks = [];
            for (
                let index = startIndex + 1;
                index < siblingElements.length;
                index += 1
            ) {
                const currentElement = siblingElements[index];
                if (
                    currentElement.classList.contains("sb-sidenav-menu-heading")
                ) {
                    break;
                }

                if (
                    currentElement.matches("a.nav-link") &&
                    !currentElement.classList.contains("is-search-hidden")
                ) {
                    visibleLinks.push(currentElement);
                }
            }

            return visibleLinks;
        }

        function applyFilter() {
            const query = normalizeText(searchInput.value);
            const hasQuery = query.length > 0;
            clearButton.classList.toggle("is-visible", hasQuery);

            let visibleLinkCount = 0;
            navLinks.forEach((linkElement) => {
                const linkText = normalizeText(linkElement.textContent);
                const isVisible = !hasQuery || linkText.includes(query);

                linkElement.classList.toggle("is-search-hidden", !isVisible);

                if (isVisible) {
                    visibleLinkCount += 1;
                }
            });

            headings.forEach((headingElement) => {
                if (!hasQuery) {
                    headingElement.classList.remove("is-search-hidden");
                    return;
                }

                const hasVisibleLinks =
                    getHeadingVisibleLinks(headingElement).length > 0;
                const isVisible = hasVisibleLinks;
                headingElement.classList.toggle("is-search-hidden", !isVisible);
            });

            noResultsElement.classList.toggle(
                "show",
                hasQuery && visibleLinkCount === 0,
            );
        }

        clearButton.addEventListener("click", function () {
            searchInput.value = "";
            applyFilter();
            searchInput.focus();
        });

        searchInput.addEventListener("input", applyFilter);
        searchInput.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && searchInput.value) {
                searchInput.value = "";
                applyFilter();
            }
        });

        applyFilter();
    }

    /* ========== Limpieza de modales Bootstrap ========== */
    document.addEventListener("hidden.bs.modal", function (event) {
        try {
            const modalEl = event.target;
            if (window.bootstrap?.Modal) {
                const instance = window.bootstrap.Modal.getInstance(modalEl);
                if (instance) {
                    instance.dispose();
                }
            }
            document.body.classList.remove("modal-open");
            document.body.style.removeProperty("padding-right");
            document
                .querySelectorAll(".modal-backdrop")
                .forEach((el) => el.remove());
        } catch {
            // noop
        }
    });

    /* ========== Dropdown de usuario ========== */
    window.toggleUserDropdown = function (event) {
        event.preventDefault();
        event.stopPropagation();

        const dropdownMenu = document.getElementById("userDropdownMenu");
        if (dropdownMenu) {
            document
                .querySelectorAll(".dropdown-menu.show")
                .forEach(function (menu) {
                    if (menu !== dropdownMenu) {
                        menu.classList.remove("show");
                    }
                });

            dropdownMenu.classList.toggle("show");
        }
    };

    document.addEventListener("click", function (e) {
        if (!e.target.closest(".dropdown")) {
            document
                .querySelectorAll(".dropdown-menu.show")
                .forEach(function (menu) {
                    menu.classList.remove("show");
                });
        }
    });

    window.confirmarCierreSesion = function () {
        if (window.Swal) {
            Swal.fire({
                title: "¿Estás seguro?",
                text: "Vas a cerrar tu sesión actual",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Sí, cerrar sesión",
                cancelButtonText: "Cancelar",
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById("logout-form")?.submit();
                }
            });
        } else {
            document.getElementById("logout-form")?.submit();
        }
    };

    window.abrirPersonalizacionColores = function () {
        document
            .querySelectorAll(".dropdown-menu.show")
            .forEach(function (menu) {
                menu.classList.remove("show");
            });

        markActiveTheme(currentTheme);
        const activeCard = document.querySelector(
            `.theme-card[data-theme="${currentTheme}"]`,
        );
        activeCard?.scrollIntoView({ block: "nearest", inline: "nearest" });

        const modalEl = document.getElementById("modalPersonalizarColores");
        if (!modalEl || !window.bootstrap?.Modal) {
            return;
        }

        window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
    };

    window.resetearTema = function () {
        if (!applyTheme(defaultTheme)) {
            return;
        }

        showThemeToast(
            "Tema restaurado",
            "Se ha restaurado el tema predeterminado",
        );
    };

    applyStoredTheme();

    /* ========== Cortinilla de transición ========== */
    function getPageLoader() {
        return document.getElementById("pageLoader");
    }

    function hidePageLoader() {
        const loader = getPageLoader();
        if (!loader) {
            return;
        }

        loader.classList.add("is-hidden");
    }

    function showPageLoader() {
        const loader = getPageLoader();
        if (!loader) {
            return;
        }

        loader.classList.remove("is-hidden");
    }

    function isNavigationLink(anchor) {
        if (!anchor || !anchor.href) {
            return false;
        }

        if (
            anchor.target === "_blank" ||
            anchor.hasAttribute("download") ||
            anchor.href.startsWith("javascript:") ||
            anchor.href.startsWith("#") ||
            anchor.href.startsWith("mailto:") ||
            anchor.href.startsWith("tel:")
        ) {
            return false;
        }

        // Anchors marcados como acciones de exportación/descarga no navegan:
        // el navegador recibe un attachment y la página actual permanece,
        // por lo que la cortinilla nunca recibiría el evento `load`.
        const action = (anchor.dataset?.action || "").toLowerCase();
        if (action.startsWith("export") || action.startsWith("download")) {
            return false;
        }

        if (anchor.closest(".dropdown-menu, .modal")) {
            return false;
        }

        try {
            const linkUrl = new URL(anchor.href, window.location.origin);
            return linkUrl.origin === window.location.origin;
        } catch {
            return false;
        }
    }

    document.addEventListener("click", function (event) {
        const anchor = event.target.closest("a");
        if (!anchor || event.defaultPrevented) {
            return;
        }

        if (isNavigationLink(anchor)) {
            showPageLoader();
        }
    });

    window.addEventListener("load", hidePageLoader);

    window.addEventListener("pageshow", function (event) {
        if (event.persisted) {
            hidePageLoader();
        }
    });

    /* ========== Flash success (mensaje de sesión) ========== */
    if (config.flashSuccess && window.Swal) {
        Swal.fire({
            icon: "success",
            title: "¡Éxito!",
            text: config.flashSuccess,
            confirmButtonColor: "#094264",
        });
    }

    /* ========== Sidebar toggle ========== */
    function initializeSidebarToggle() {
        const sidebarToggle = document.body.querySelector("#sidebarToggle");
        if (!sidebarToggle) {
            return;
        }

        sidebarToggle.addEventListener("click", function (event) {
            event.preventDefault();
            document.body.classList.toggle("sb-sidenav-toggled");
            localStorage.setItem(
                "sb|sidebar-toggle",
                document.body.classList.contains("sb-sidenav-toggled"),
            );
        });
    }

    /* ========== Dropdowns generales (excluye dropdown de usuario) ========== */
    function setupManualDropdown() {
        const dropdownToggles = document.querySelectorAll(
            ".dropdown-toggle:not(#navbarDropdown)",
        );

        dropdownToggles.forEach(function (dropdownToggle) {
            const dropdownMenu = dropdownToggle.nextElementSibling;

            if (
                !dropdownToggle ||
                !dropdownMenu ||
                !dropdownMenu.classList.contains("dropdown-menu")
            ) {
                return;
            }

            dropdownToggle.removeEventListener(
                "click",
                dropdownToggle._manualClickHandler,
            );

            dropdownToggle._manualClickHandler = function (e) {
                e.preventDefault();
                e.stopPropagation();

                document
                    .querySelectorAll(".dropdown-menu.show")
                    .forEach(function (menu) {
                        if (menu !== dropdownMenu) {
                            menu.classList.remove("show");
                        }
                    });

                dropdownMenu.classList.toggle("show");
            };

            dropdownToggle.addEventListener(
                "click",
                dropdownToggle._manualClickHandler,
            );
        });

        document.removeEventListener("click", document._dropdownOutsideHandler);
        document._dropdownOutsideHandler = function (e) {
            if (!e.target.closest(".dropdown")) {
                document
                    .querySelectorAll(".dropdown-menu.show")
                    .forEach(function (menu) {
                        menu.classList.remove("show");
                    });
            }
        };
        document.addEventListener("click", document._dropdownOutsideHandler);
    }

    function initializeGeneralDropdowns() {
        if (typeof bootstrap === "undefined") {
            setupManualDropdown();
            return;
        }

        const dropdownElementList = document.querySelectorAll(
            ".dropdown-toggle:not(#navbarDropdown)",
        );

        dropdownElementList.forEach(function (dropdownToggleEl) {
            if (
                dropdownToggleEl.getAttribute("data-bs-initialized") === "true"
            ) {
                return;
            }

            try {
                const existingDropdown =
                    bootstrap.Dropdown.getInstance(dropdownToggleEl);
                if (existingDropdown) {
                    existingDropdown.dispose();
                }

                new bootstrap.Dropdown(dropdownToggleEl, {
                    autoClose: true,
                    boundary: "viewport",
                });

                dropdownToggleEl.setAttribute("data-bs-initialized", "true");
            } catch {
                setupManualDropdown();
            }
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        initializeSidebarToggle();
        applyStoredTheme();
        initializeThemeSelector();
        initializeSidebarSearch();
        setTimeout(function () {
            initializeGeneralDropdowns();
        }, 100);
    });
})();
