<?php
/**
 * API para operações AJAX do módulo de produção e serviços
 * Sistema de Gestão - BHSAC
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../models/ProducaoDAO.php';

$dao = new ProducaoDAO();

try {
    // Listar itens por categoria
    if (isset($_GET['listar_itens'])) {
        $categoria = $_GET['categoria'] ?? null;
        $itens = $dao->listarItens($categoria);
        echo json_encode(['success' => true, 'itens' => $itens]);
        exit;
    }

    // Listar registros recentes
    if (isset($_GET['listar_registros'])) {
        $filtros = [
            'data_inicio' => $_GET['data_inicio'] ?? null,
            'data_fim' => $_GET['data_fim'] ?? null,
            'tipo_operacao' => $_GET['tipo_operacao'] ?? null,
            'limite' => $_GET['limite'] ?? 50
        ];
        $registros = $dao->listarRegistros($filtros);
        echo json_encode(['success' => true, 'registros' => $registros]);
        exit;
    }

    // Obter resumo consolidado
    if (isset($_GET['resumo'])) {
        $dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
        $dataFim = $_GET['data_fim'] ?? date('Y-m-t');
        $resumo = $dao->getResumoPorItem($dataInicio, $dataFim);
        echo json_encode(['success' => true, 'resumo' => $resumo]);
        exit;
    }

    // Obter estatísticas por categoria
    if (isset($_GET['stats_categoria'])) {
        $dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
        $dataFim = $_GET['data_fim'] ?? date('Y-m-t');
        $stats = $dao->getStatsPorCategoria($dataInicio, $dataFim);
        echo json_encode(['success' => true, 'stats' => $stats]);
        exit;
    }

    // Obter estatísticas semanais
    if (isset($_GET['stats_semanais'])) {
        $dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
        $dataFim = $_GET['data_fim'] ?? date('Y-m-t');
        $stats = $dao->getStatsSemanais($dataInicio, $dataFim);
        echo json_encode(['success' => true, 'stats' => $stats]);
        exit;
    }

    // Obter estatísticas mensais
    if (isset($_GET['stats_mensais'])) {
        $dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
        $dataFim = $_GET['data_fim'] ?? date('Y-m-t');
        $stats = $dao->getStatsMensais($dataInicio, $dataFim);
        echo json_encode(['success' => true, 'stats' => $stats]);
        exit;
    }

    // Obter produção detalhada para lançamento diário
    if (isset($_GET['get_diario'])) {
        $data = $_GET['data'] ?? date('Y-m-d');
        $registros = $dao->getProducaoDiaria($data);
        echo json_encode(['success' => true, 'registros' => $registros]);
        exit;
    }

    // Obter controle diário detalhado
    if (isset($_GET['get_controle_diario'])) {
        $data = $_GET['data'] ?? date('Y-m-d');
        $turno = $_GET['turno'] ?? null;
        $registros = $dao->listarControleDiario($data, $turno);
        $resumo = $dao->getResumoDiario($data);
        echo json_encode(['success' => true, 'registros' => $registros, 'resumo' => $resumo]);
        exit;
    }

    // Calcular consumo de materiais baseado na produção
    if (isset($_GET['consumo_producao'])) {
        $dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
        $dataFim = $_GET['data_fim'] ?? date('Y-m-t');
        $consumo = $dao->calcularConsumoProducao($dataInicio, $dataFim);
        echo json_encode(['success' => true, 'consumo' => $consumo]);
        exit;
    }

    // Calcular consumo do controle diário
    if (isset($_GET['consumo_diario'])) {
        $data = $_GET['data'] ?? date('Y-m-d');
        $turno = $_GET['turno'] ?? null;
        $consumo = $dao->calcularConsumoControleDiario($data, $turno);
        echo json_encode(['success' => true, 'consumo' => $consumo]);
        exit;
    }

    // Resumo de consumo mensal
    if (isset($_GET['consumo_mensal'])) {
        $mes = $_GET['mes'] ?? date('m');
        $ano = $_GET['ano'] ?? date('Y');
        $resumo = $dao->getResumoConsumoMensal($mes, $ano);
        echo json_encode(['success' => true, 'resumo' => $resumo]);
        exit;
    }

    // Obter um registro específico
    if (isset($_GET['get_registro'])) {
        $id = $_GET['id'] ?? null;
        if (!$id)
            throw new Exception("ID não fornecido");
        $registro = $dao->getById($id);
        echo json_encode(['success' => true, 'registro' => $registro]);
        exit;
    }

    // Registrar ou Atualizar operação (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            $input = $_POST;
        }

        $id = $input['id'] ?? null;
        $acao = $input['acao'] ?? 'salvar';

        if ($acao === 'excluir') {
            if (!$id)
                throw new Exception("ID não fornecido para exclusão");
            $dao->excluirOperacao($id);
            echo json_encode(['success' => true, 'message' => 'Registro excluído']);
            exit;
        }

        if ($acao === 'save_diario') {
            $data = $input['data'] ?? null;
            $dados = $input['dados'] ?? [];
            if (!$data)
                throw new Exception("Data não informada.");
            $dao->salvarProducaoDiaria($data, $dados);
            echo json_encode(['success' => true, 'message' => 'Produção diária salva com sucesso!']);
            exit;
        }

        // Salvar controle diário em lote (melhorado)
        if ($acao === 'save_controle_diario') {
            $data = $input['data'] ?? null;
            $turno = $input['turno'] ?? 'Manhã';
            $registros = $input['registros'] ?? [];
            if (!$data)
                throw new Exception("Data não informada.");
            $dao->salvarControleDiarioLote($data, $turno, $registros);
            echo json_encode(['success' => true, 'message' => 'Controle diário salvo com sucesso!']);
            exit;
        }

        // Salvar registro individual de controle diário
        if ($acao === 'save_controle_individual') {
            $id = $input['id'] ?? null;
            if ($id) {
                $dao->atualizarControleDiario($id, $input);
                echo json_encode(['success' => true, 'message' => 'Registro atualizado!']);
            } else {
                $newId = $dao->salvarControleDiario($input);
                echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Registro salvo!']);
            }
            exit;
        }

        // Excluir registro de controle diário
        if ($acao === 'excluir_controle_diario') {
            $id = $input['id'] ?? null;
            if (!$id)
                throw new Exception("ID não fornecido.");
            $dao->excluirControleDiario($id);
            echo json_encode(['success' => true, 'message' => 'Registro excluído!']);
            exit;
        }

        if ($acao === 'save_item') {
            if (empty($input['nome']) || empty($input['categoria']) || empty($input['unidade'])) {
                throw new Exception("Dados incompletos para o cadastro do item.");
            }
            $newId = $dao->cadastrarItem($input);
            echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Item cadastrado com sucesso!']);
            exit;
        }

        if (empty($input['item_id']) || empty($input['tipo_operacao']) || empty($input['quantidade']) || empty($input['data_operacao'])) {
            throw new Exception("Dados incompletos para o registro.");
        }

        if ($id) {
            $dao->atualizarOperacao($id, $input);
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Atualizado']);
        } else {
            $newId = $dao->registrarOperacao($input);
            echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Cadastrado']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Ação não reconhecida']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
