document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("registerForm");

    if (!form) {
        return;
    }

    const nameInput = document.getElementById("name");
    const emailInput = document.getElementById("email");
    const passwordInput = document.getElementById("password");
    const passwordConfirmInput = document.getElementById("password-confirm");
    const nameError = document.getElementById("nameError");
    const emailError = document.getElementById("emailError");
    const passwordError = document.getElementById("passwordError");
    const passwordConfirmError = document.getElementById("passwordConfirmError");
    const togglePassword = document.getElementById("togglePassword");
    const capsHint = document.getElementById("capsHint");
    const recaptchaTokenInput = document.getElementById("recaptcha_token");
    const recaptchaError = document.getElementById("recaptchaError");
    const recaptchaErrorContainer = document.getElementById(
        "recaptchaErrorContainer",
    );
    const submitButton = form.querySelector('button[type="submit"]');
    const defaultSubmitContent = submitButton ? submitButton.innerHTML : "";
    const recaptchaEnabled = form.dataset.recaptchaEnabled === "true";
    const recaptchaSiteKey = form.dataset.recaptchaSiteKey ?? "";
    const recaptchaAction = form.dataset.recaptchaAction ?? "register";

    if (!nameInput || !emailInput || !passwordInput || !passwordConfirmInput) {
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

    function validateName() {
        const value = nameInput.value.trim();

        if (!value) {
            setError(
                nameInput,
                nameError,
                "El nombre completo es obligatorio.",
            );
            return false;
        }

        setError(nameInput, nameError, "");
        return true;
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

        if (value.length < 8) {
            setError(
                passwordInput,
                passwordError,
                "La contraseña debe tener al menos 8 caracteres.",
            );
            return false;
        }

        setError(passwordInput, passwordError, "");
        return true;
    }

    function validatePasswordConfirm() {
        const value = passwordConfirmInput.value;
        const passwordValue = passwordInput.value;

        if (!value) {
            setError(
                passwordConfirmInput,
                passwordConfirmError,
                "La confirmación de contraseña es obligatoria.",
            );
            return false;
        }

        if (value !== passwordValue) {
            setError(
                passwordConfirmInput,
                passwordConfirmError,
                "Las contraseñas no coinciden.",
            );
            return false;
        }

        setError(passwordConfirmInput, passwordConfirmError, "");
        return true;
    }

    function setSubmitting(isSubmitting, label) {
        if (!submitButton) {
            return;
        }

        submitButton.disabled = isSubmitting;
        submitButton.innerHTML = isSubmitting
            ? '<span class="btn-spinner" aria-hidden="true"></span>' + label
            : defaultSubmitContent;
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

    nameInput.addEventListener("input", validateName);
    emailInput.addEventListener("input", validateEmail);
    passwordInput.addEventListener("input", function () {
        validatePassword();
        if (passwordConfirmInput.value) {
            validatePasswordConfirm();
        }
    });
    passwordConfirmInput.addEventListener("input", validatePasswordConfirm);

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
        const nameIsValid = validateName();
        const emailIsValid = validateEmail();
        const passwordIsValid = validatePassword();
        const passwordConfirmIsValid = validatePasswordConfirm();

        if (!nameIsValid || !emailIsValid || !passwordIsValid || !passwordConfirmIsValid) {
            event.preventDefault();

            if (!nameIsValid) {
                nameInput.focus();
            } else if (!emailIsValid) {
                emailInput.focus();
            } else if (!passwordIsValid) {
                passwordInput.focus();
            } else {
                passwordConfirmInput.focus();
            }

            return;
        }

        setRecaptchaError("");

        if (!recaptchaEnabled) {
            setSubmitting(true, "Creando cuenta...");
            return;
        }

        event.preventDefault();

        setSubmitting(true, "Validando...");

        try {
            const token = await executeRecaptcha();

            if (!token || !recaptchaTokenInput) {
                throw new Error("No se genero el token de seguridad.");
            }

            recaptchaTokenInput.value = token;
            setSubmitting(true, "Creando cuenta...");
            form.submit();
        } catch (error) {
            setRecaptchaError(
                "No fue posible completar la validacion de seguridad. Intenta nuevamente.",
            );

            setSubmitting(false);
        }
    });
});
