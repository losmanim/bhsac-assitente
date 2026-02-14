-- =============================================
-- Script de criação do banco de dados
-- Sistema de Gestão de Funcionários - BHSAC
-- =============================================

-- Criar o banco de dados
CREATE DATABASE IF NOT EXISTS gestao_funcionarios
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Usar o banco de dados
USE gestao_funcionarios;

-- =============================================
-- Tabela de Funcionários
-- =============================================
CREATE TABLE IF NOT EXISTS funcionarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cargo VARCHAR(50) NOT NULL,
    salario DECIMAL(10, 2) NOT NULL,
    data_nascimento DATE NOT NULL,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    endereco VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    data_contratacao DATE NULL,
    data_rescisao DATE NULL,
    observacoes TEXT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Índices para melhorar performance
-- =============================================
CREATE INDEX IF NOT EXISTS idx_funcionarios_nome ON funcionarios(nome);
CREATE INDEX IF NOT EXISTS idx_funcionarios_cargo ON funcionarios(cargo);
CREATE INDEX IF NOT EXISTS idx_funcionarios_cpf ON funcionarios(cpf);
CREATE INDEX IF NOT EXISTS idx_funcionarios_contratacao ON funcionarios(data_contratacao);

-- =============================================
-- Dados de exemplo (opcional)
-- =============================================
INSERT INTO funcionarios (nome, cargo, salario, data_nascimento, cpf, endereco, email, telefone, data_contratacao) VALUES
('João Silva', 'Pedreiro', 2500.00, '1985-03-15', '123.456.789-00', 'Rua das Flores, 123 - Centro', 'joao@email.com', '(11) 99999-1111', '2020-02-01'),
('Maria Santos', 'Engenheira Civil', 8500.00, '1990-07-22', '234.567.890-11', 'Av. Principal, 456 - Jardim', 'maria@email.com', '(11) 99999-2222', '2019-05-15'),
('Carlos Oliveira', 'Mestre de Obras', 4500.00, '1978-11-08', '345.678.901-22', 'Rua Nova, 789 - Vila Nova', 'carlos@email.com', '(11) 99999-3333', '2018-08-20')
ON DUPLICATE KEY UPDATE nome=nome;
