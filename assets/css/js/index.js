document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('loginForm');

    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            const email = document.getElementById('email').value.trim();
            const senha = document.getElementById('senha').value;

            if (email === "" || senha === "") {
                e.preventDefault();
                alert("Por favor, preencha todos os campos antes de enviar.");
            }
        });
    }
});