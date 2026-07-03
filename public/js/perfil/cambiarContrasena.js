/**
 * Cambiar contraseña
 * Maneja el envío AJAX del formulario, validación del servidor,
 * toggle de visibilidad de los campos y feedback con SweetAlert.
 * Depende de: window.cambiarContrasenaConfig (url, csrfToken, redirectLogin)
 */
document.addEventListener("DOMContentLoaded", function () {
    initToggleContrasena();
    initFortalezaContrasena();
    initEnvioFormulario();
});

/**
 * Reglas de validación visual de la contraseña en tiempo real.
 * Deben coincidir con las reglas del servidor
 * (Password::min(8)->mixedCase()->numbers()->symbols()).
 */
const REGLAS_FORTALEZA = {
    longitud: (v) => v.length >= 8,
    mayuscula: (v) => /[A-Z]/.test(v),
    minuscula: (v) => /[a-z]/.test(v),
    numero: (v) => /\d/.test(v),
    simbolo: (v) => /[^A-Za-z0-9]/.test(v),
};

/**
 * Evalúa en tiempo real los criterios de la nueva contraseña,
 * la coincidencia con la confirmación y habilita/deshabilita el botón de envío.
 */
function initFortalezaContrasena() {
    const inputPassword = document.getElementById("password");
    const inputConfirmacion = document.getElementById("password_confirmation");
    const btn = document.getElementById("btn-guardar-contrasena");
    if (!inputPassword || !btn) return;

    const fortaleza = document.getElementById("fortaleza");
    const coincidenciaMsg = document.getElementById("coincidencia-msg");

    const evaluar = () => {
        const valor = inputPassword.value;
        const confirmacion = inputConfirmacion?.value ?? "";

        const todosOk = evaluarCriterios(valor);
        const coinciden = evaluarCoincidencia(valor, confirmacion);

        btn.disabled = !(todosOk && coinciden);
    };

    inputPassword.addEventListener("input", () => {
        if (fortaleza) fortaleza.hidden = inputPassword.value.length === 0;
        evaluar();
    });

    if (inputConfirmacion) {
        inputConfirmacion.addEventListener("input", evaluar);
    }

    evaluar();
}

/**
 * Marca cada criterio de fortaleza como cumplido o no cumplido.
 *
 * @param {string} valor
 * @returns {boolean} true si todos los criterios se cumplen.
 */
function evaluarCriterios(valor) {
    let todosOk = true;

    Object.entries(REGLAS_FORTALEZA).forEach(([clave, cumple]) => {
        const item = document.querySelector(`.criterio[data-criterio="${clave}"]`);
        if (!item) return;

        const ok = cumple(valor);
        item.classList.toggle("criterio--ok", ok);

        const icono = item.querySelector("i");
        if (icono) {
            icono.classList.toggle("fa-circle", !ok);
            icono.classList.toggle("fa-circle-check", ok);
        }

        if (!ok) todosOk = false;
    });

    return todosOk;
}

/**
 * Muestra u oculta el mensaje de coincidencia entre contraseñas.
 *
 * @param {string} valor
 * @param {string} confirmacion
 * @returns {boolean} true si coinciden y no están vacías.
 */
function evaluarCoincidencia(valor, confirmacion) {
    const msg = document.getElementById("coincidencia-msg");
    const inputConfirmacion = document.getElementById("password_confirmation");

    const coinciden = valor.length > 0 && valor === confirmacion;

    if (msg) msg.hidden = !coinciden;
    if (inputConfirmacion) {
        inputConfirmacion.classList.toggle("has-success", coinciden);
    }

    return coinciden;
}

/**
 * Alterna la visibilidad (texto / contraseña) de cada campo mediante
 * el botón con clase `.campo__toggle` y su atributo `data-target`.
 */
function initToggleContrasena() {
    document.querySelectorAll(".campo__toggle").forEach((boton) => {
        boton.addEventListener("click", () => {
            const id = boton.dataset.target;
            const input = document.getElementById(id);
            if (!input) return;

            const esPassword = input.type === "password";
            input.type = esPassword ? "text" : "password";

            const icono = boton.querySelector("i");
            if (icono) {
                icono.classList.toggle("fa-eye", !esPassword);
                icono.classList.toggle("fa-eye-slash", esPassword);
            }

            boton.setAttribute(
                "aria-label",
                esPassword ? "Ocultar contraseña" : "Mostrar contraseña",
            );
        });
    });
}

/**
 * Gestiona el envío del formulario vía fetch, mostrando los errores
 * de validación por campo y los mensajes de éxito/error con SweetAlert.
 */
function initEnvioFormulario() {
    const config = window.cambiarContrasenaConfig || {};
    const form = document.getElementById("form-cambiar-contrasena");
    if (!form) return;

    const btn = document.getElementById("btn-guardar-contrasena");
    const textoBtn = btn?.querySelector(".btn-guardar__texto");
    const spinnerBtn = btn?.querySelector(".btn-guardar__spinner");

    form.addEventListener("submit", async (evento) => {
        evento.preventDefault();
        limpiarErrores();

        if (typeof Swal === "undefined" || !config.url) return;

        setBtnCargando(true);

        try {
            const respuesta = await fetch(config.url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": config.csrfToken,
                },
                body: JSON.stringify(Object.fromEntries(new FormData(form))),
            });

            const datos = await respuesta.json().catch(() => ({}));

            if (respuesta.ok && datos.success) {
                await mostrarExito(datos.message);
                window.location.href = datos.redirect || config.redirectLogin;
                return;
            }

            if (respuesta.status === 422 && datos.errors) {
                mostrarErroresValidacion(datos.errors);
                return;
            }

            mostrarError(
                datos.message ||
                    "Ocurrió un error al actualizar la contraseña. Inténtalo de nuevo.",
            );
        } catch {
            mostrarError(
                "No se pudo conectar con el servidor. Verifica tu conexión e inténtalo de nuevo.",
            );
        } finally {
            setBtnCargando(false);
        }
    });

    function setBtnCargando(cargando) {
        if (!btn) return;
        btn.disabled = cargando;
        if (textoBtn) textoBtn.hidden = cargando;
        if (spinnerBtn) spinnerBtn.hidden = !cargando;
    }
}

/**
 * Limpia todos los mensajes de error y el estado visual de los campos.
 */
function limpiarErrores() {
    document.querySelectorAll(".campo__error").forEach((parrafo) => {
        parrafo.textContent = "";
        parrafo.hidden = true;
    });
    document.querySelectorAll(".campo__input").forEach((input) => {
        input.classList.remove("has-error");
    });
}

/**
 * Muestra los errores de validación devueltos por el servidor (422)
 * bajo cada campo correspondiente.
 *
 * @param {Record<string, string|string[]>} errores
 */
function mostrarErroresValidacion(errores) {
    Object.entries(errores).forEach(([campo, mensajes]) => {
        const parrafo = document.querySelector(
            `.campo__error[data-error="${campo}"]`,
        );
        const input = document.querySelector(`[name="${campo}"]`);
        const mensaje = Array.isArray(mensajes) ? mensajes[0] : mensajes;

        if (parrafo) {
            parrafo.textContent = mensaje || "";
            parrafo.hidden = !mensaje;
        }
        if (input) input.classList.add("has-error");
    });
}

function mostrarExito(mensaje) {
    return Swal.fire({
        icon: "success",
        title: "Contraseña actualizada",
        text: mensaje || "Tu contraseña se actualizó correctamente.",
        confirmButtonText: "Entendido",
        confirmButtonColor: "#1e5c50",
    });
}

function mostrarError(mensaje) {
    return Swal.fire({
        icon: "error",
        title: "No se pudo actualizar",
        text: mensaje,
        confirmButtonText: "Cerrar",
        confirmButtonColor: "#a62346",
    });
}