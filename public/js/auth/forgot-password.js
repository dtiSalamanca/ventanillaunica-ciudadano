document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("forgotPasswordForm");

    if (!form) {
        return;
    }

    const emailInput = document.getElementById("email");
    const emailError = document.getElementById("emailError");
    const recaptchaTokenInput = document.getElementById("recaptcha_token");
    const recaptchaError = document.getElementById("recaptchaError");
    const recaptchaErrorContainer = document.getElementById(
        "recaptchaErrorContainer",
    );
    const submitButton = form.querySelector('button[type="submit"]');
    const defaultSubmitLabel = submitButton ? submitButton.textContent : "";
    const recaptchaEnabled = form.dataset.recaptchaEnabled === "true";
    const recaptchaSiteKey = form.dataset.recaptchaSiteKey ?? "";
    const recaptchaAction = form.dataset.recaptchaAction ?? "password_email";

    if (!emailInput || !emailError) {
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

    form.addEventListener("submit", async function (event) {
        const emailIsValid = validateEmail();

        if (!emailIsValid) {
            event.preventDefault();
            emailInput.focus();
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
