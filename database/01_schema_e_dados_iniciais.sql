-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 25/07/2026 às 02:25
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `financeiro`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo` enum('Receita','Despesa') NOT NULL,
  `cor` varchar(20) DEFAULT NULL,
  `icone` varchar(100) DEFAULT NULL,
  `ativo` char(1) DEFAULT 'S'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `usuario_id`, `nome`, `tipo`, `cor`, `icone`, `ativo`) VALUES
(14, 3, 'sala', 'Despesa', '#2b4c7e', 'fa-wallet', 'S'),
(17, 5, 'Venda de Produtos', 'Receita', '#7aafff', 'fa-heartbeat', 'N'),
(18, 5, 'Prestação de Serviços', 'Receita', '#be00ff', 'fa-film', 'S'),
(24, 4, 'Wi-fi', 'Receita', '#2b4c7e', 'fa-wifi', 'S'),
(25, 4, 'Água', 'Receita', '#be00ff', 'fa-tag', 'S');

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `cpf_cnpj` varchar(20) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `endereco` varchar(200) DEFAULT NULL,
  `data_cadastro` date DEFAULT NULL,
  `ativo` char(1) DEFAULT 'S'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `id` int(11) NOT NULL,
  `empresa` varchar(150) DEFAULT NULL,
  `cnpj` varchar(20) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `endereco` varchar(200) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `logotipo` varchar(200) DEFAULT NULL,
  `moeda` varchar(10) DEFAULT NULL,
  `tema` varchar(20) DEFAULT NULL,
  `data_backup` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `contas`
--

CREATE TABLE `contas` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `banco` varchar(100) DEFAULT NULL,
  `agencia` varchar(20) DEFAULT NULL,
  `numero_conta` varchar(30) DEFAULT NULL,
  `tipo` enum('Caixa','ContaCorrente','Poupança') DEFAULT NULL,
  `saldo_inicial` decimal(12,2) DEFAULT NULL,
  `saldo_atual` decimal(12,2) DEFAULT NULL,
  `ativo` char(1) DEFAULT 'S'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `contas`
--

INSERT INTO `contas` (`id`, `nome`, `banco`, `agencia`, `numero_conta`, `tipo`, `saldo_inicial`, `saldo_atual`, `ativo`) VALUES
(1, 'Conta Principal', NULL, NULL, NULL, 'ContaCorrente', 0.00, 0.00, 'S'),
(2, 'Caixa Físico', NULL, NULL, NULL, 'Caixa', 0.00, 0.00, 'S');

-- --------------------------------------------------------

--
-- Estrutura para tabela `despesas`
--

CREATE TABLE `despesas` (
  `id` int(11) NOT NULL,
  `fornecedor_id` int(11) DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `forma_pagamento_id` int(11) DEFAULT NULL,
  `conta_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `descricao` varchar(200) DEFAULT NULL,
  `valor` decimal(12,2) DEFAULT NULL,
  `data_pagamento` date DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `status` enum('Pago','Pendente') DEFAULT NULL,
  `ativo` char(1) NOT NULL DEFAULT 'S'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `formas_pagamento`
--

CREATE TABLE `formas_pagamento` (
  `id` int(11) NOT NULL,
  `descricao` varchar(50) NOT NULL,
  `ativo` char(1) DEFAULT 'S'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `formas_pagamento`
--

INSERT INTO `formas_pagamento` (`id`, `descricao`, `ativo`) VALUES
(1, 'PIX', 'S'),
(2, 'Dinheiro', 'S'),
(3, 'Cartão de Crédito', 'S'),
(4, 'Cartão de Débito', 'S'),
(5, 'Transferência', 'S'),
(6, 'Boleto', 'S');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fornecedores`
--

CREATE TABLE `fornecedores` (
  `id` int(11) NOT NULL,
  `razao_social` varchar(150) DEFAULT NULL,
  `nome_fantasia` varchar(150) DEFAULT NULL,
  `cnpj` varchar(20) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `endereco` varchar(200) DEFAULT NULL,
  `contato` varchar(100) DEFAULT NULL,
  `ativo` char(1) DEFAULT 'S'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `fornecedores`
--

INSERT INTO `fornecedores` (`id`, `razao_social`, `nome_fantasia`, `cnpj`, `telefone`, `email`, `cidade`, `endereco`, `contato`, `ativo`) VALUES
(1, 'Energia MA Distribuidora', 'Energia MA', NULL, NULL, NULL, NULL, NULL, NULL, 'S'),
(2, 'Fornecedor Padrão', 'Fornecedor Padrão', NULL, NULL, NULL, NULL, NULL, NULL, 'S');

-- --------------------------------------------------------

--
-- Estrutura para tabela `metas`
--

CREATE TABLE `metas` (
  `id` int(11) NOT NULL,
  `descricao` varchar(150) DEFAULT NULL,
  `tipo` enum('Receita','Despesa') DEFAULT NULL,
  `valor_meta` decimal(12,2) DEFAULT NULL,
  `valor_realizado` decimal(12,2) DEFAULT NULL,
  `percentual` decimal(5,2) DEFAULT NULL,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `status` enum('Em Andamento','Concluída','Atrasada') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacoes`
--

CREATE TABLE `movimentacoes` (
  `id` int(11) NOT NULL,
  `tipo` enum('Receita','Despesa') DEFAULT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `conta_id` int(11) DEFAULT NULL,
  `descricao` varchar(200) DEFAULT NULL,
  `entrada` decimal(12,2) DEFAULT NULL,
  `saida` decimal(12,2) DEFAULT NULL,
  `saldo` decimal(12,2) DEFAULT NULL,
  `data_movimento` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `perfis`
--

CREATE TABLE `perfis` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descricao` varchar(150) DEFAULT NULL,
  `ativo` char(1) DEFAULT 'S'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `perfis`
--

INSERT INTO `perfis` (`id`, `nome`, `descricao`, `ativo`) VALUES
(1, 'Administrador', 'Acesso total ao sistema', 'S'),
(2, 'Gerente', 'Gerencia financeira', 'S'),
(3, 'Financeiro', 'Controle financeiro', 'S'),
(4, 'Diretor', 'Visualização geral', 'S'),
(5, 'Funcionário', 'Acesso básico', 'S'),
(6, 'Contador', 'Relatórios contábeis', 'S'),
(7, 'Admin', NULL, 'S'),
(8, 'Operador', NULL, 'S'),
(9, 'Editor', NULL, 'S');

-- --------------------------------------------------------

--
-- Estrutura para tabela `receitas`
--

CREATE TABLE `receitas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `forma_pagamento_id` int(11) NOT NULL,
  `conta_id` int(11) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_recebimento` date NOT NULL,
  `status` enum('Recebido','Pendente') NOT NULL DEFAULT 'Pendente',
  `observacao` text DEFAULT NULL,
  `ativo` char(1) NOT NULL DEFAULT 'S',
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `perfil_id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `senha` varchar(255) DEFAULT NULL,
  `foto` varchar(200) DEFAULT NULL,
  `ativo` char(1) DEFAULT 'S',
  `ultimo_acesso` datetime DEFAULT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp(),
  `token_recuperacao` varchar(64) DEFAULT NULL,
  `token_expiracao` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `perfil_id`, `nome`, `cpf`, `telefone`, `email`, `senha`, `foto`, `ativo`, `ultimo_acesso`, `data_cadastro`, `token_recuperacao`, `token_expiracao`) VALUES
(1, 7, 'Ana Silva', NULL, NULL, 'ana.silva@exemplo.com', '$2y$10$6I2dEA29Gcp39DbQcG3brubfQ114PQZPODpyxCeaBFQVceDKIcPFK', NULL, '1', NULL, '2026-07-15 19:41:16', '4ec3f63e88dc417c46e3d13d671d0da925bc3f8300e06938cd3211d9ca8c5320', '2026-07-16 01:45:08'),
(2, 8, 'Pedro Santos', NULL, NULL, 'pedro.santos@tech.corp', '$2y$10$2f2F2mTqcVDs2VIaGicqJOQRMM3z0rqXPhWKPts3hTVkP35hRoYM6', NULL, '0', NULL, '2026-07-15 19:41:16', NULL, NULL),
(3, 9, 'Maria Oliveira', NULL, NULL, 'maria.oliveira@web.net', '$2y$10$UCqWXpPOQALkSYPy1/hBvuuQla8uPE8mW2CQC24RYDJ3eBUWlz.ty', NULL, '1', NULL, '2026-07-15 19:41:16', NULL, NULL),
(4, 8, 'Admin', NULL, NULL, 'admin@email.com', '$2y$10$28Vc37vFa5OG2TOk8AaxleTX68H1JkXUWr1Maq3zzfUUfc0yNsrhK', NULL, '1', NULL, '2026-07-21 19:09:27', NULL, NULL),
(5, 8, 'sim', NULL, NULL, 'sim@gmail.com', '$2y$10$2zOm.l7e2XlqyHGQtBp.SekI/9aaqkMOOk8MfM0Hs3uwxjmGhnj72', NULL, '1', NULL, '2026-07-21 20:13:03', NULL, NULL);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `contas`
--
ALTER TABLE `contas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `despesas`
--
ALTER TABLE `despesas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fornecedor_id` (`fornecedor_id`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `forma_pagamento_id` (`forma_pagamento_id`),
  ADD KEY `conta_id` (`conta_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `formas_pagamento`
--
ALTER TABLE `formas_pagamento`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `metas`
--
ALTER TABLE `metas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `movimentacoes`
--
ALTER TABLE `movimentacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conta_id` (`conta_id`);

--
-- Índices de tabela `perfis`
--
ALTER TABLE `perfis`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `receitas`
--
ALTER TABLE `receitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_receitas_usuario` (`usuario_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf` (`cpf`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_usuario_perfil` (`perfil_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `contas`
--
ALTER TABLE `contas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `despesas`
--
ALTER TABLE `despesas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `formas_pagamento`
--
ALTER TABLE `formas_pagamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `metas`
--
ALTER TABLE `metas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `movimentacoes`
--
ALTER TABLE `movimentacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `perfis`
--
ALTER TABLE `perfis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `receitas`
--
ALTER TABLE `receitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `despesas`
--
ALTER TABLE `despesas`
  ADD CONSTRAINT `despesas_ibfk_1` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`),
  ADD CONSTRAINT `despesas_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`),
  ADD CONSTRAINT `despesas_ibfk_3` FOREIGN KEY (`forma_pagamento_id`) REFERENCES `formas_pagamento` (`id`),
  ADD CONSTRAINT `despesas_ibfk_4` FOREIGN KEY (`conta_id`) REFERENCES `contas` (`id`),
  ADD CONSTRAINT `despesas_ibfk_5` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `movimentacoes`
--
ALTER TABLE `movimentacoes`
  ADD CONSTRAINT `movimentacoes_ibfk_1` FOREIGN KEY (`conta_id`) REFERENCES `contas` (`id`);

--
-- Restrições para tabelas `receitas`
--
ALTER TABLE `receitas`
  ADD CONSTRAINT `fk_receitas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuario_perfil` FOREIGN KEY (`perfil_id`) REFERENCES `perfis` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
