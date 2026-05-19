const pwToggle = document.getElementById('pwToggle');
const pwInput = document.getElementById('password');

if (pwToggle && pwInput) {
    pwToggle.addEventListener('click', () => {
        const show = pwInput.type === 'password';
        pwInput.type = show ? 'text' : 'password';
        pwToggle.textContent = show ? 'Hide' : 'Show';
    });
}
