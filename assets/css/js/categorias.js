// assets/js/categorias.js

document.addEventListener('DOMContentLoaded', function () {

    // --- 1. ALTERNAR PAINEL DE FILTROS ---
    const btnToggleFiltros = document.getElementById('btnToggleFiltros');
    const painelFiltros = document.getElementById('painelFiltros');

    if (btnToggleFiltros && painelFiltros) {
        btnToggleFiltros.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            // Detecta o estado real de visibilidade (Inline + CSS externo)
            const displayAtual = window.getComputedStyle(painelFiltros).display;

            if (displayAtual === 'none' || painelFiltros.style.display === 'none') {
                painelFiltros.style.display = 'block';
            } else {
                painelFiltros.style.display = 'none';
            }
        });
    } else {
        console.warn('Elemento #btnToggleFiltros ou #painelFiltros não foi encontrado no DOM.');
    }

    // --- 2. CONFIRMAÇÃO DE EXCLUSÃO ---
    const linksDelete = document.querySelectorAll('.link-delete');
    linksDelete.forEach(function (link) {
        link.addEventListener('click', function (event) {
            if (!confirm('Tem certeza que deseja excluir esta categoria?')) {
                event.preventDefault();
            }
        });
    });

    // --- 3. VALIDAÇÃO DE FORMULÁRIO (CADASTRAR/EDITAR) ---
    const formCategoria = document.getElementById('formCategoria');
    if (formCategoria) {
        formCategoria.addEventListener('submit', function (event) {
            const inputNome = document.getElementById('nome');
            if (inputNome && inputNome.value.trim() === '') {
                event.preventDefault();
                alert('Preencha o nome da categoria.');
                inputNome.focus();
            }
        });
    }

});