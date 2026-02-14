<?php
/**
 * API para operações AJAX do módulo financeiro
 * Sistema de Gestão - BHSAC
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../models/FinanceiroDAO.php';

$dao = new FinanceiroDAO();

try {
    // Buscar movimentação por ID
    if (isset($_GET['id'])) {
        $movimentacao = $dao->buscarPorId($_GET['id']);
        if ($movimentacao) {
            echo json_encode([
                'success' => true,
                'movimentacao' => $movimentacao
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Movimentação não encontrada'
            ]);
        }
        exit;
    }

    // Obter resumo financeiro
    if (isset($_GET['resumo'])) {
        $dataInicio = $_GET['data_inicio'] ?? null;
        $dataFim = $_GET['data_fim'] ?? null;

        $resumo = [
            'success' => true,
            'entradas' => $dao->calcularTotalEntradas($dataInicio, $dataFim),
            'saidas' => $dao->calcularTotalSaidas($dataInicio, $dataFim),
            'saldo' => $dao->calcularSaldo($dataInicio, $dataFim),
            'total_movimentacoes' => $dao->contarMovimentacoes($dataInicio, $dataFim)
        ];

        echo json_encode($resumo);
        exit;
    }

    // Listar categorias
    if (isset($_GET['categorias'])) {
        $tipo = $_GET['tipo'] ?? null;
        $categorias = $dao->listarCategorias($tipo);
        echo json_encode([
            'success' => true,
            'categorias' => $categorias
        ]);
        exit;
    }

    // Resumo por categoria
    if (isset($_GET['resumo_categoria'])) {
        $tipo = $_GET['tipo'] ?? 'entrada';
        $dataInicio = $_GET['data_inicio'] ?? null;
        $dataFim = $_GET['data_fim'] ?? null;

        $resumo = $dao->getResumoPorCategoria($tipo, $dataInicio, $dataFim);
        echo json_encode([
            'success' => true,
            'resumo' => $resumo
        ]);
        exit;
    }

    // Resumo mensal
    if (isset($_GET['resumo_mensal'])) {
        $resumo = $dao->getResumoMensal();
        echo json_encode([
            'success' => true,
            'resumo' => $resumo
        ]);
        exit;
    }

    // Listar funcionários
    if (isset($_GET['funcionarios'])) {
        $funcionarios = $dao->listarFuncionariosAtivos();
        echo json_encode([
            'success' => true,
            'funcionarios' => $funcionarios
        ]);
        exit;
    }

    // Ações de Escrita (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input)
            $input = $_POST;

        $acao = $input['acao'] ?? '';

        if ($acao === 'save_categoria') {
            if (empty($input['nome']) || empty($input['tipo'])) {
                throw new Exception("Dados incompletos para o cadastro da categoria.");
            }
            $id = $dao->cadastrarCategoria($input);
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Categoria cadastrada com sucesso!']);
            exit;
        }
    }

    echo json_encode([
        'success' => false,
        'error' => 'Parâmetro não reconhecido'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
