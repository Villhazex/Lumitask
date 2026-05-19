document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('regForm');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const pwInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    const matchHint = document.getElementById('matchHint');

    function makePwToggle(toggleId, inputId) {
        const btn = document.getElementById(toggleId);
        const input = document.getElementById(inputId);

        if (!btn || !input) return;

        btn.textContent = 'Show';
        btn.addEventListener('click', () => {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Hide' : 'Show';
        });
    }

    function passwordOk(value) {
        return value.length >= 8 && /[A-Za-z]/.test(value) && /\d/.test(value);
    }

    function emailOk(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    }

    function usernameOk(value) {
        return value.length >= 3 && !/\s/.test(value);
    }

    function setState(input, ok) {
        input.classList.toggle('valid', ok && input.value.length > 0);
        input.classList.toggle('invalid', !ok && input.value.length > 0);
    }

    function validate(showMessage = false) {
        const unameValid = usernameOk(usernameInput.value.trim());
        const emailValid = emailOk(emailInput.value.trim());
        const passwordValid = passwordOk(pwInput.value);
        const matchValid = confirmInput.value === pwInput.value && confirmInput.value.length > 0;

        setState(usernameInput, unameValid);
        setState(emailInput, emailValid);
        setState(pwInput, passwordValid);
        setState(confirmInput, matchValid);

        if (!confirmInput.value) {
            matchHint.textContent = '';
        } else {
            matchHint.textContent = matchValid ? 'Password cocok' : 'Password tidak cocok';
            matchHint.style.color = matchValid ? '#006b38' : '#c10000';
        }

        if (showMessage && !passwordValid) {
            pwInput.setCustomValidity('Password minimal 8 karakter dan harus berisi huruf serta angka.');
        } else {
            pwInput.setCustomValidity('');
        }

        if (showMessage && !matchValid) {
            confirmInput.setCustomValidity('Konfirmasi password tidak cocok.');
        } else {
            confirmInput.setCustomValidity('');
        }

        return unameValid && emailValid && passwordValid && matchValid;
    }

    makePwToggle('pwToggle1', 'password');
    makePwToggle('pwToggle2', 'confirm_password');

    [usernameInput, emailInput, pwInput, confirmInput].forEach((input) => {
        input.addEventListener('input', () => validate(false));
    });

    form.addEventListener('submit', (event) => {
        if (!validate(true)) {
            event.preventDefault();
            form.reportValidity();
        }
    });

    validate(false);
});
