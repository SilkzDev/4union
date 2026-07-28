-- Migração: Módulo de Despesas (Sprint 07)
-- Rode este script no phpMyAdmin, no banco `financeiro`, antes de usar o módulo de despesas.

-- 1. Coluna de exclusão lógica (RF03), no mesmo padrão já usado em categorias/receitas/clientes/etc.
ALTER TABLE despesas ADD COLUMN ativo CHAR(1) NOT NULL DEFAULT 'S' AFTER status;

-- 2. Contas financeiras de exemplo (tabela existia mas estava vazia)
INSERT INTO contas (nome, banco, tipo, saldo_inicial, saldo_atual, ativo) VALUES
('Conta Principal', NULL, 'ContaCorrente', 0.00, 0.00, 'S'),
('Caixa Físico', NULL, 'Caixa', 0.00, 0.00, 'S');

-- 3. Fornecedores de exemplo (tabela existia mas estava vazia)
INSERT INTO fornecedores (razao_social, nome_fantasia, ativo) VALUES
('Energia MA Distribuidora', 'Energia MA', 'S'),
('Fornecedor Padrão', 'Fornecedor Padrão', 'S');
