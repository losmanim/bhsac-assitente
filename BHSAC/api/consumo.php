<?php
/**
 * API para operações AJAX do módulo de consumo por peça (BOM)
 * Sistema de Gestão - BHSAC
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../models/ConsumoDAO.php';

$dao = new ConsumoDAO();

try {
    // Listar peças com custo calculado
    if (isset($_GET['listar_pecas'])) {
        $pecas = $dao->listarPecasComCusto();
        echo json_encode(['success' => true, 'pecas' => $pecas]);
        exit;
    }

    // Listar materiais disponíveis
    if (isset($_GET['listar_materiais'])) {
        $materiais = $dao->listarMateriais();
        echo json_encode(['success' => true, 'materiais' => $materiais]);
        exit;
    }

    // Listar composição de uma peça
    if (isset($_GET['composicao'])) {
        $pecaId = $_GET['peca_id'] ?? null;
        if (!$pecaId) {
            throw new Exception("ID da peça não fornecido");
        }
        $composicao = $dao->listarComposicao($pecaId);
        $custo = $dao->getCustoPeca($pecaId);
        echo json_encode(['success' => true, 'composicao' => $composicao, 'custo' => $custo]);
        exit;
    }

    // Calcular consumo para quantidade
    if (isset($_GET['calcular'])) {
        $pecaId = $_GET['peca_id'] ?? null;
        $quantidade = $_GET['quantidade'] ?? 1;
        if (!$pecaId) {
            throw new Exception("ID da peça não fornecido");
        }
        $resultado = $dao->calcularConsumo($pecaId, $quantidade);
        echo json_encode(['success' => true, 'resultado' => $resultado]);
        exit;
    }

    // Operações POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $acao = $input['acao'] ?? '';

        // Adicionar material à composição
        if ($acao === 'adicionar_material') {
            if (empty($input['peca_id']) || empty($input['material_id']) || !isset($input['consumo_liquido'])) {
                throw new Exception("Dados incompletos para adicionar material");
            }
            $dao->adicionarMaterial($input);
            echo json_encode(['success' => true, 'message' => 'Material adicionado com sucesso!']);
            exit;
        }

        // Remover material da composição
        if ($acao === 'remover_material') {
            if (empty($input['id'])) {
                throw new Exception("ID do registro não fornecido");
            }
            $dao->removerMaterial($input['id']);
            echo json_encode(['success' => true, 'message' => 'Material removido com sucesso!']);
            exit;
        }

        // Atualizar preço do material
        if ($acao === 'atualizar_preco') {
            if (empty($input['material_id']) || !isset($input['preco'])) {
                throw new Exception("ID e preço são obrigatórios");
            }
            $dao->atualizarPrecoMaterial($input['material_id'], $input['preco']);
            echo json_encode(['success' => true, 'message' => 'Preço atualizado com sucesso!']);
            exit;
        }

        // Salvar composição em lote
        if ($acao === 'salvar_composicao') {
            if (empty($input['peca_id']) || empty($input['materiais'])) {
                throw new Exception("Dados incompletos");
            }
            foreach ($input['materiais'] as $mat) {
                $mat['peca_id'] = $input['peca_id'];
                $dao->adicionarMaterial($mat);
            }
            echo json_encode(['success' => true, 'message' => 'Composição salva com sucesso!']);
            exit;
        }

        throw new Exception("Ação não reconhecida");
    }

    echo json_encode(['success' => false, 'error' => 'Requisição inválida']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
