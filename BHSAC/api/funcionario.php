<?php
/**
 * API para buscar detalhes de funcionário
 * Retorna JSON com dados completos
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/FuncionarioDAO.php';

$response = ['success' => false];

if (isset($_GET['id'])) {
    try {
        $dao = new FuncionarioDAO();
        $funcionario = $dao->buscarPorId($_GET['id']);

        if ($funcionario) {
            $funcionario['tempo_empresa'] = $dao->calcularTempoEmpresa($funcionario);
            $response = [
                'success' => true,
                'funcionario' => $funcionario
            ];
        } else {
            $response['error'] = 'Funcionário não encontrado';
        }
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
} else {
    $response['error'] = 'ID não informado';
}

echo json_encode($response);
