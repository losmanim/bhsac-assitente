-- =============================================
-- Script de alteração da tabela funcionarios
-- Adicionar campos RG, CNH e anexos de documentos
-- Sistema de Gestão de Funcionários - BHSAC
-- =============================================

USE gestao_funcionarios;

-- Adicionar coluna RG
ALTER TABLE funcionarios ADD COLUMN IF NOT EXISTS rg VARCHAR(20) NULL AFTER cpf;

-- Adicionar coluna CNH
ALTER TABLE funcionarios ADD COLUMN IF NOT EXISTS cnh VARCHAR(20) NULL AFTER rg;

-- Adicionar colunas para anexos de documentos
ALTER TABLE funcionarios ADD COLUMN IF NOT EXISTS anexo_cpf VARCHAR(255) NULL AFTER observacoes;
ALTER TABLE funcionarios ADD COLUMN IF NOT EXISTS anexo_rg VARCHAR(255) NULL AFTER anexo_cpf;
ALTER TABLE funcionarios ADD COLUMN IF NOT EXISTS anexo_cnh VARCHAR(255) NULL AFTER anexo_rg;
ALTER TABLE funcionarios ADD COLUMN IF NOT EXISTS anexo_nrs VARCHAR(255) NULL AFTER anexo_cnh;
ALTER TABLE funcionarios ADD COLUMN IF NOT EXISTS anexo_certificados VARCHAR(255) NULL AFTER anexo_nrs;

-- Criar índice para RG (opcional, útil para buscas)
CREATE INDEX IF NOT EXISTS idx_funcionarios_rg ON funcionarios(rg);
