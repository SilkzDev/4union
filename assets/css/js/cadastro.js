document.addEventListener('DOMContentLoaded', function () {
    const cadastroForm = document.getElementById('cadastroForm');

    if (cadastroForm) {
        cadastroForm.addEventListener('submit', function (e) {
            const senha = document.getElementById('senha').value;
            const confirma = document.getElementById('confirma_senha').value;

            if (senha.length < 6) {
                e.preventDefault();
                alert("A sua senha deve conter pelo menos 6 caracteres.");
                return;
            }

            if (senha !== confirma) {
                e.preventDefault();
                alert("As senhas inseridas não coincidem!");
                return;
            }
        });
    }
});