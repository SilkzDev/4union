-- Seed de dados de exemplo pro Dashboard (Sprint 09)
-- Rodar DEPOIS de importar o 01_schema_e_dados_iniciais.sql.
-- Sem isso o banco fica sem receitas/despesas e o dashboard aparece
-- todo zerado ("Sem receitas no periodo selecionado").

-- Categoria de despesa que ainda nao existia (so tinha categoria de receita
-- pro usuario Admin: Wi-fi e Agua)
INSERT INTO `categorias` (`usuario_id`, `nome`, `tipo`, `cor`, `icone`, `ativo`) VALUES
(4, 'Aluguel', 'Despesa', '#e74c3c', 'fa-building', 'S');

-- Cliente padrao (tabela estava vazia e receitas.cliente_id e obrigatorio)
INSERT INTO `clientes` (`nome`, `cpf_cnpj`, `telefone`, `email`, `cidade`, `endereco`, `data_cadastro`, `ativo`) VALUES
('Cliente Padrao', NULL, NULL, NULL, NULL, NULL, CURDATE(), 'S');

-- Receitas (usuario Admin, conta principal)
INSERT INTO `receitas` (`usuario_id`, `cliente_id`, `categoria_id`, `forma_pagamento_id`, `conta_id`, `descricao`, `valor`, `data_recebimento`, `status`) VALUES
(4, LAST_INSERT_ID(), 25, 1, 1, 'Cobranca de Agua Compartilhada', 1200.00, '2026-07-20', 'Recebido'),
(4, LAST_INSERT_ID(), 24, 3, 1, 'Venda de Wi-fi Corporativo', 5000.00, '2026-07-10', 'Recebido'),
(4, LAST_INSERT_ID(), 24, 5, 1, 'Receita Junho Teste', 3000.00, '2026-06-15', 'Recebido');

-- Despesas (categoria Aluguel criada acima, fornecedores ja existentes no dump)
INSERT INTO `despesas` (`fornecedor_id`, `categoria_id`, `forma_pagamento_id`, `conta_id`, `usuario_id`, `descricao`, `valor`, `data_pagamento`, `status`) VALUES
(1, (SELECT id FROM `categorias` WHERE nome = 'Aluguel' AND usuario_id = 4 LIMIT 1), 6, 1, 4, 'Conta de Energia Eletrica', 850.00, '2026-07-15', 'Pago'),
(2, (SELECT id FROM `categorias` WHERE nome = 'Aluguel' AND usuario_id = 4 LIMIT 1), 1, 1, 4, 'Aluguel do Escritorio', 2200.00, '2026-07-05', 'Pago');
