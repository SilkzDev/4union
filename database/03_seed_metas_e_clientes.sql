-- Sprint 10: Dashboard Executivo — Seed de Dados
-- Executar no banco "financeiro" antes de abrir executivo.php

-- Metas estratégicas (targets fixos; valor_realizado calculado em tempo real pelo PHP)
INSERT INTO `metas` (`descricao`, `tipo`, `valor_meta`, `valor_realizado`, `percentual`, `data_inicio`, `data_fim`, `status`) VALUES
('Meta de Receita Mensal',  'Receita', 5000.00, 0.00, 0.00, DATE_FORMAT(NOW(), '%Y-%m-01'), LAST_DAY(NOW()), 'Em Andamento'),
('Limite de Despesas',      'Despesa', 3000.00, 0.00, 0.00, DATE_FORMAT(NOW(), '%Y-%m-01'), LAST_DAY(NOW()), 'Em Andamento'),
('Meta de Lucro Líquido',   'Receita', 2000.00, 0.00, 0.00, DATE_FORMAT(NOW(), '%Y-%m-01'), LAST_DAY(NOW()), 'Em Andamento');

-- Clientes demo para alimentar o ranking (tabela existia mas estava vazia)
INSERT INTO `clientes` (`nome`, `cpf_cnpj`, `telefone`, `email`, `cidade`, `endereco`, `data_cadastro`, `ativo`) VALUES
('Alpha Solutions Ltda',    '12.345.678/0001-90', '(11) 9 9999-0001', 'contato@alpha.com.br',  'São Paulo',        'Av. Paulista, 1000',         CURDATE(), 'S'),
('Beta Comércio e Serv.',   '98.765.432/0001-10', '(21) 9 8888-0002', 'contato@beta.com.br',   'Rio de Janeiro',   'Rua do Comércio, 250',       CURDATE(), 'S'),
('Gama Tecnologia ME',      '11.222.333/0001-44', '(31) 9 7777-0003', 'gama@tech.com.br',      'Belo Horizonte',   'Rua da Inovação, 50',        CURDATE(), 'S'),
('Delta Consultoria EPP',   '44.555.666/0001-77', '(41) 9 6666-0004', 'delta@consult.com.br',  'Curitiba',         'Av. das Flores, 320',        CURDATE(), 'S'),
('Épsilon Serviços',        '77.888.999/0001-11', '(51) 9 5555-0005', 'epsilon@serv.com.br',   'Porto Alegre',     'Rua Farroupilha, 120',       CURDATE(), 'S');
