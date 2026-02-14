-- =============================================
-- Script de criação das tabelas do módulo financeiro
-- Sistema de Gestão - BHSAC
-- =============================================

USE gestao_funcionarios;

-- =============================================
-- Tabela de Categorias Financeiras
-- =============================================
CREATE TABLE IF NOT EXISTS categorias_financeiras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    tipo ENUM('entrada', 'saida') NOT NULL,
    cor VARCHAR(7) DEFAULT '#6b7280',
    icone VARCHAR(30) DEFAULT 'bi-tag',
    ativo BOOLEAN DEFAULT TRUE,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Tabela de Movimentações Financeiras
-- =============================================
CREATE TABLE IF NOT EXISTS movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('entrada', 'saida') NOT NULL,
    categoria_id INT NOT NULL,
    funcionario_id INT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(12, 2) NOT NULL,
    data_movimentacao DATE NOT NULL,
    observacoes TEXT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (categoria_id) REFERENCES categorias_financeiras(id),
    FOREIGN KEY (funcionario_id) REFERENCES funcionarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Índices para melhorar performance
-- =============================================
CREATE INDEX idx_movimentacoes_tipo ON movimentacoes(tipo);
CREATE INDEX idx_movimentacoes_data ON movimentacoes(data_movimentacao);
CREATE INDEX idx_movimentacoes_categoria ON movimentacoes(categoria_id);
CREATE INDEX idx_movimentacoes_funcionario ON movimentacoes(funcionario_id);

-- =============================================
-- Categorias de Entrada (Receitas)
-- =============================================
INSERT INTO categorias_financeiras (nome, tipo, cor, icone) VALUES
('Vendas', 'entrada', '#10b981', 'bi-cart-check'),
('Serviços', 'entrada', '#06b6d4', 'bi-tools'),
('Recebimentos', 'entrada', '#8b5cf6', 'bi-cash-coin'),
('Outros Recebimentos', 'entrada', '#6366f1', 'bi-plus-circle')
ON DUPLICATE KEY UPDATE nome=nome;

-- =============================================
-- Categorias de Saída (Despesas)
-- =============================================
INSERT INTO categorias_financeiras (nome, tipo, cor, icone) VALUES
('Salários', 'saida', '#ef4444', 'bi-person-badge'),
('Materiais', 'saida', '#f97316', 'bi-box-seam'),
('Equipamentos', 'saida', '#eab308', 'bi-gear'),
('Combustível', 'saida', '#84cc16', 'bi-fuel-pump'),
('Manutenção', 'saida', '#14b8a6', 'bi-wrench'),
('Impostos', 'saida', '#ec4899', 'bi-receipt'),
('Outras Despesas', 'saida', '#6b7280', 'bi-dash-circle')
ON DUPLICATE KEY UPDATE nome=nome;
