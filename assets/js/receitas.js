function abrirModalNovaReceita() {
    document.getElementById('formReceita').reset();
    document.getElementById('receita_id').value = '';
    document.getElementById('modalTitle').innerText = 'Cadastrar Receita';
    document.getElementById('modalReceita').style.display = 'flex';
}

function fecharModal() {
    document.getElementById('modalReceita').style.display = 'none';
}

function editarReceita(dados) {
    document.getElementById('receita_id').value = dados.id;
    document.getElementById('descricao').value = dados.descricao;
    document.getElementById('cliente_id').value = dados.cliente_id;
    document.getElementById('categoria_id').value = dados.categoria_id;
    document.getElementById('valor').value = parseFloat(dados.valor).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
    document.getElementById('data_recebimento').value = dados.data_recebimento;
    document.getElementById('forma_pagamento_id').value = dados.forma_pagamento_id;
    document.getElementById('conta_id').value = dados.conta_id;
    document.getElementById('status').value = dados.status;
    document.getElementById('observacao').value = dados.observacao || '';

    document.getElementById('modalTitle').innerText = 'Editar Receita';
    document.getElementById('modalReceita').style.display = 'flex';
}

function mascaraMoeda(input) {
    let value = input.value.replace(/\D/g, '');
    value = (value / 100).toFixed(2) + '';
    value = value.replace('.', ',');
    value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    input.value = value;
}

function validarFormulario() {
    const valorCampo = document.getElementById('valor').value;
    const valorClean = parseFloat(valorCampo.replace(/\./g, '').replace(',', '.'));

    if (isNaN(valorClean) || valorClean <= 0) {
        alert('O valor da receita deve ser maior do que zero!');
        return false;
    }
    return true;
}

window.onclick = function(event) {
    const modal = document.getElementById('modalReceita');
    if (event.target === modal) {
        fecharModal();
    }
}