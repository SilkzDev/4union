-- Migração: Módulo de Despesas (Sprint 07)
-- OBSOLETO: já está toda incorporada em 01_schema_e_dados_iniciais.sql
-- (a tabela despesas já nasce com a coluna `ativo`, e os seeds de contas e
-- fornecedores abaixo já vêm no INSERT do dump). NÃO rode este script numa
-- instalação nova — o ALTER TABLE falha (coluna já existe) e os INSERTs
-- duplicam linhas. Mantido só como registro histórico da migração original.

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
