-- =============================================
-- Script de criação das tabelas do módulo de produção e serviços
-- Sistema de Gestão - BHSAC
-- =============================================

USE gestao_funcionarios;

-- =============================================
-- Tabela de Itens (Produtos, Serviços e Materiais)
-- =============================================
CREATE TABLE IF NOT EXISTS itens_gestao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    categoria ENUM('Artefato', 'Serviço', 'Material') NOT NULL,
    unidade VARCHAR(20) NOT NULL, -- un, h, m3, kg, viagem
    preco_referencia DECIMAL(12, 2) DEFAULT 0.00,
    ativo BOOLEAN DEFAULT TRUE,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Tabela de Registros Operacionais
-- =============================================
CREATE TABLE IF NOT EXISTS registros_operacionais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    tipo_operacao ENUM('Produção', 'Venda', 'Consumo', 'Prestação de Serviço') NOT NULL,
    quantidade DECIMAL(12, 2) NOT NULL,
    valor_total DECIMAL(12, 2) DEFAULT 0.00,
    data_operacao DATE NOT NULL,
    observacoes TEXT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES itens_gestao(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Inserção de Itens Iniciais (Baseado na Planilha)
-- =============================================

-- Artefatos
INSERT INTO itens_gestao (nome, categoria, unidade) VALUES
('Bloco 09x19x39', 'Artefato', 'un'),
('Bloco 14x19x39', 'Artefato', 'un'),
('Bloco 19x19x39', 'Artefato', 'un'),
('Canaleta 14x19x39', 'Artefato', 'un'),
('Manilha 1,33x0,5x0,05', 'Artefato', 'un'),
('Paver Retangular 20x10x6', 'Artefato', 'un');

-- Serviços
INSERT INTO itens_gestao (nome, categoria, unidade) VALUES
('Serviço Munck', 'Serviço', 'h'),
('Frete Bitruck', 'Serviço', 'viagem'),
('Serviço Empilhadeira', 'Serviço', 'h');

-- Materiais
INSERT INTO itens_gestao (nome, categoria, unidade) VALUES
('Areia', 'Material', 'm3'),
('Pedrisco', 'Material', 'm3'),
('Cimento 50kg', 'Material', 'un'),
('Aditivo', 'Material', 'kg');
