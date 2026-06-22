document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("loginForm");

    if (!form) {
        return;
    }

    const emailInput = document.getElementById("email");
    const passwordInput = document.getElementById("password");
    const emailError = document.getElementById("emailError");
    const passwordError = document.getElementById("passwordError");
    const togglePassword = document.getElementById("togglePassword");
    const capsHint = document.getElementById("capsHint");
    const recaptchaTokenInput = document.getElementById("recaptcha_token");
    const recaptchaError = document.getElementById("recaptchaError");
    const recaptchaErrorContainer = document.getElementById(
        "recaptchaErrorContainer",
    );
    const submitButton = form.querySelector('button[type="submit"]');
    const defaultSubmitLabel = submitButton ? submitButton.textContent : "";
    const recaptchaEnabled = form.dataset.recaptchaEnabled === "true";
    const recaptchaSiteKey = form.dataset.recaptchaSiteKey ?? "";
    const recaptchaAction = form.dataset.recaptchaAction ?? "login";

    if (!emailInput || !passwordInput || !emailError || !passwordError) {
        return;
    }

    function setError(input, errorElement, message) {
        errorElement.textContent = message || "";
        input.setAttribute("aria-invalid", message ? "true" : "false");
        input.style.borderColor = message ? "#ef4444" : "#e5e7eb";
    }

    function setRecaptchaError(message) {
        if (!recaptchaError || !recaptchaErrorContainer) {
            return;
        }

        recaptchaError.textContent = message || "";
        recaptchaErrorContainer.classList.toggle("is-hidden", !message);
    }

    function validateEmail() {
        const value = emailInput.value.trim();

        if (!value) {
            setError(
                emailInput,
                emailError,
                "El correo electrónico es obligatorio.",
            );
            return false;
        }

        setError(emailInput, emailError, "");
        return true;
    }

    function validatePassword() {
        const value = passwordInput.value;

        if (!value) {
            setError(
                passwordInput,
                passwordError,
                "La contraseña es obligatoria.",
            );
            return false;
        }

        if (value.length < 6) {
            setError(
                passwordInput,
                passwordError,
                "La contraseña debe tener al menos 6 caracteres.",
            );
            return false;
        }

        setError(passwordInput, passwordError, "");
        return true;
    }

    function executeRecaptcha() {
        return new Promise(function (resolve, reject) {
            if (typeof window.grecaptcha === "undefined") {
                reject(new Error("reCAPTCHA no esta disponible."));
                return;
            }

            window.grecaptcha.ready(function () {
                window.grecaptcha
                    .execute(recaptchaSiteKey, {
                        action: recaptchaAction,
                    })
                    .then(resolve)
                    .catch(reject);
            });
        });
    }

    emailInput.addEventListener("input", validateEmail);
    passwordInput.addEventListener("input", validatePassword);

    togglePassword?.addEventListener("click", function () {
        const isPassword = passwordInput.getAttribute("type") === "password";

        passwordInput.setAttribute("type", isPassword ? "text" : "password");
        togglePassword.textContent = isPassword ? "Ocultar" : "Ver";
    });

    passwordInput.addEventListener("keydown", function (event) {
        const capsLockEnabled =
            event.getModifierState && event.getModifierState("CapsLock");

        if (capsHint) {
            capsHint.style.display = capsLockEnabled ? "block" : "none";
        }
    });

    passwordInput.addEventListener("blur", function () {
        if (capsHint) {
            capsHint.style.display = "none";
        }
    });

    form.addEventListener("submit", async function (event) {
        const emailIsValid = validateEmail();
        const passwordIsValid = validatePassword();

        if (!emailIsValid || !passwordIsValid) {
            event.preventDefault();
            (emailIsValid ? passwordInput : emailInput).focus();
            return;
        }

        setRecaptchaError("");

        if (!recaptchaEnabled) {
            return;
        }

        event.preventDefault();

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = "Validando...";
        }

        try {
            const token = await executeRecaptcha();

            if (!token || !recaptchaTokenInput) {
                throw new Error("No se genero el token de seguridad.");
            }

            recaptchaTokenInput.value = token;
            form.submit();
        } catch (error) {
            setRecaptchaError(
                "No fue posible completar la validacion de seguridad. Intenta nuevamente.",
            );

            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = defaultSubmitLabel;
            }
        }
    });
});
