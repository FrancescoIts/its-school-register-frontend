
document.addEventListener('DOMContentLoaded', function () {
    // Mostra/Nascondi password
    const togglePasswordBtn = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');

    if (togglePasswordBtn && passwordField) {
        togglePasswordBtn.addEventListener('click', function () {
            const icon = this.querySelector('i');
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }

    // Genera una password sicura
    const generatePasswordBtn = document.getElementById('generatePassword');
    if (generatePasswordBtn && passwordField) {
        generatePasswordBtn.addEventListener('click', function () {
            const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+{}|:<>?";
            let password = '';

            // Genera una password finché non rispetta i criteri richiesti
            do {
                password = Array.from({ length: 12 }, () => charset.charAt(Math.floor(Math.random() * charset.length))).join('');
            } while (!/(?=.*\d.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]).{8,}/.test(password));

            // Copia la password negli appunti
            navigator.clipboard.writeText(password).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Password Generata!',
                    text: 'La password è stata generata e copiata negli appunti.',
                    footer: `<strong>${password}</strong>`
                });
            });

            // Inserisce automaticamente la password nel campo
            passwordField.value = password;
        });
    }
});
