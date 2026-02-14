-- =============================================
-- Script de criação das tabelas de Orçamento e Consumo por Peça
-- Sistema de Gestão - BHSAC
-- =============================================

USE gestao_funcionarios;

-- =============================================
-- Tabela de Orçamentos (Cabeçalho)
-- =============================================
CREATE TABLE IF NOT EXISTS orcamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) NOT NULL UNIQUE,
    data_emissao DATE NOT NULL,
    data_validade DATE NOT NULL,
    cliente_nome VARCHAR(150) NOT NULL,
    cliente_documento VARCHAR(20) NULL,
    cliente_contato VARCHAR(100) NULL,
    cliente_endereco TEXT NULL,
    subtotal DECIMAL(12, 2) DEFAULT 0.00,
    desconto DECIMAL(12, 2) DEFAULT 0.00,
    valor_total DECIMAL(12, 2) DEFAULT 0.00,
    condicoes_pagamento VARCHAR(100) NULL,
    observacoes TEXT NULL,
    status ENUM('Pendente', 'Aprovado', 'Recusado', 'Expirado') DEFAULT 'Pendente',
    usuario_id INT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Tabela de Itens do Orçamento
-- =============================================
CREATE TABLE IF NOT EXISTS orcamento_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orcamento_id INT NOT NULL,
    item_id INT NULL,
    descricao VARCHAR(200) NOT NULL,
    quantidade DECIMAL(12, 2) NOT NULL,
    unidade VARCHAR(20) NOT NULL,
    valor_unitario DECIMAL(12, 2) NOT NULL,
    valor_total DECIMAL(12, 2) NOT NULL,
    FOREIGN KEY (orcamento_id) REFERENCES orcamentos(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES itens_gestao(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Tabela de Composição de Materiais (BOM - Bill of Materials)
-- Para cálculo de consumo por peça
-- =============================================
CREATE TABLE IF NOT EXISTS composicao_materiais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peca_id INT NOT NULL COMMENT 'ID do artefato/produto final',
    material_id INT NOT NULL COMMENT 'ID do material/insumo',
    consumo_liquido DECIMAL(12, 4) NOT NULL COMMENT 'Consumo necessário por unidade',
    percentual_perda DECIMAL(5, 2) DEFAULT 0.00 COMMENT 'Margem de perda/segurança (%)',
    observacoes VARCHAR(200) NULL,
    ativo BOOLEAN DEFAULT TRUE,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (peca_id) REFERENCES itens_gestao(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES itens_gestao(id) ON DELETE CASCADE,
    UNIQUE KEY uk_peca_material (peca_id, material_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Tabela de Controle Diário Melhorado
-- =============================================
CREATE TABLE IF NOT EXISTS controle_diario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_producao DATE NOT NULL,
    turno ENUM('Manhã', 'Tarde', 'Noite') DEFAULT 'Manhã',
    operador VARCHAR(100) NULL,
    item_id INT NOT NULL,
    quantidade_planejada DECIMAL(12, 2) DEFAULT 0.00,
    quantidade_produzida DECIMAL(12, 2) NOT NULL,
    quantidade_refugo DECIMAL(12, 2) DEFAULT 0.00,
    hora_inicio TIME NULL,
    hora_fim TIME NULL,
    tempo_parada INT DEFAULT 0 COMMENT 'Tempo de parada em minutos',
    motivo_parada VARCHAR(200) NULL,
    observacoes TEXT NULL,
    usuario_id INT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES itens_gestao(id),
    INDEX idx_data_turno (data_producao, turno)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- View para cálculo de consumo por peça
-- =============================================
CREATE OR REPLACE VIEW vw_consumo_peca AS
SELECT 
    cm.id,
    cm.peca_id,
    p.nome AS peca_nome,
    p.unidade AS peca_unidade,
    cm.material_id,
    m.nome AS material_nome,
    m.unidade AS material_unidade,
    m.preco_referencia AS preco_material,
    cm.consumo_liquido,
    cm.percentual_perda,
    ROUND(cm.consumo_liquido * (1 + cm.percentual_perda / 100), 4) AS consumo_bruto,
    ROUND(cm.consumo_liquido * (1 + cm.percentual_perda / 100) * m.preco_referencia, 2) AS custo_material
FROM composicao_materiais cm
INNER JOIN itens_gestao p ON cm.peca_id = p.id
INNER JOIN itens_gestao m ON cm.material_id = m.id
WHERE cm.ativo = TRUE;

-- =============================================
-- View para resumo de custo total por peça
-- =============================================
CREATE OR REPLACE VIEW vw_custo_total_peca AS
SELECT 
    peca_id,
    peca_nome,
    peca_unidade,
    COUNT(*) AS qtd_materiais,
    SUM(consumo_bruto) AS consumo_total,
    SUM(custo_material) AS custo_total
FROM vw_consumo_peca
GROUP BY peca_id, peca_nome, peca_unidade;
