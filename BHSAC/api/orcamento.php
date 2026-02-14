<?php
/**
 * API para operações AJAX do módulo de orçamentos
 * Sistema de Gestão - BHSAC
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../models/OrcamentoDAO.php';
require_once __DIR__ . '/../models/ProducaoDAO.php';

$dao = new OrcamentoDAO();
$producaoDao = new ProducaoDAO();

try {
    // Gerar número de orçamento
    if (isset($_GET['gerar_numero'])) {
        $numero = $dao->gerarNumero();
        echo json_encode(['success' => true, 'numero' => $numero]);
        exit;
    }

    // Listar itens para seleção
    if (isset($_GET['listar_itens'])) {
        $itens = $producaoDao->listarItens();
        echo json_encode(['success' => true, 'itens' => $itens]);
        exit;
    }

    // Listar orçamentos
    if (isset($_GET['listar'])) {
        $filtros = [
            'status' => $_GET['status'] ?? null,
            'data_inicio' => $_GET['data_inicio'] ?? null,
            'data_fim' => $_GET['data_fim'] ?? null,
            'cliente' => $_GET['cliente'] ?? null,
            'limite' => $_GET['limite'] ?? 50
        ];
        $orcamentos = $dao->listar($filtros);
        echo json_encode(['success' => true, 'orcamentos' => $orcamentos]);
        exit;
    }

    // Obter orçamento específico
    if (isset($_GET['id'])) {
        $orcamento = $dao->getById($_GET['id']);
        if (!$orcamento) {
            throw new Exception("Orçamento não encontrado");
        }
        echo json_encode(['success' => true, 'orcamento' => $orcamento]);
        exit;
    }

    // Estatísticas
    if (isset($_GET['estatisticas'])) {
        $dataInicio = $_GET['data_inicio'] ?? null;
        $dataFim = $_GET['data_fim'] ?? null;
        $stats = $dao->getEstatisticas($dataInicio, $dataFim);
        echo json_encode(['success' => true, 'estatisticas' => $stats]);
        exit;
    }

    // Operações POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $acao = $input['acao'] ?? 'criar';

        // Criar novo orçamento
        if ($acao === 'criar') {
            if (empty($input['cliente_nome']) || empty($input['itens'])) {
                throw new Exception("Dados incompletos para criar orçamento");
            }

            $input['numero'] = $dao->gerarNumero();
            $input['data_emissao'] = $input['data_emissao'] ?? date('Y-m-d');
            $input['data_validade'] = $input['data_validade'] ?? date('Y-m-d', strtotime('+15 days'));

            $id = $dao->criar($input);
            echo json_encode(['success' => true, 'id' => $id, 'numero' => $input['numero'], 'message' => 'Orçamento criado com sucesso!']);
            exit;
        }

        // Atualizar orçamento
        if ($acao === 'atualizar') {
            if (empty($input['id'])) {
                throw new Exception("ID do orçamento não fornecido");
            }
            $dao->atualizar($input['id'], $input);
            echo json_encode(['success' => true, 'message' => 'Orçamento atualizado com sucesso!']);
            exit;
        }

        // Atualizar status
        if ($acao === 'atualizar_status') {
            if (empty($input['id']) || empty($input['status'])) {
                throw new Exception("ID e status são obrigatórios");
            }
            $dao->atualizarStatus($input['id'], $input['status']);
            echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso!']);
            exit;
        }

        // Excluir orçamento
        if ($acao === 'excluir') {
            if (empty($input['id'])) {
                throw new Exception("ID do orçamento não fornecido");
            }
            $dao->excluir($input['id']);
            echo json_encode(['success' => true, 'message' => 'Orçamento excluído com sucesso!']);
            exit;
        }

        throw new Exception("Ação não reconhecida");
    }

    echo json_encode(['success' => false, 'error' => 'Requisição inválida']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
