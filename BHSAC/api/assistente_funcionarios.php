<?php
/**
 * API de Assistente Virtual para Módulo de Funcionários
 * Processa comandos de voz e retorna respostas estruturadas
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/FuncionarioDAO.php';
require_once __DIR__ . '/../funcionarios.php';

// Autenticação básica
Auth::exigirLogin();

$dao = new FuncionarioDAO();
$resposta = [
    'sucesso' => false,
    'mensagem' => '',
    'dados' => null,
    'acao_executada' => ''
];

try {
    // Receber comando via POST
    $input = json_decode(file_get_contents('php://input'), true);
    $comando = strtolower(trim($input['comando'] ?? ''));
    
    if (empty($comando)) {
        throw new Exception('Comando não fornecido');
    }

    // Processar comando de voz
    $resultado = processarComandoVoz($comando, $dao);
    
    $resposta['sucesso'] = true;
    $resposta['mensagem'] = $resultado['mensagem'];
    $resposta['dados'] = $resultado['dados'] ?? null;
    $resposta['acao_executada'] = $resultado['acao'];
    
} catch (Exception $e) {
    $resposta['mensagem'] = 'Erro: ' . $e->getMessage();
    http_response_code(400);
}

echo json_encode($resposta, JSON_UNESCAPED_UNICODE);

/**
 * Processa comandos de voz e retorna ações estruturadas
 */
function processarComandoVoz($comando, $dao)
{
    // COMANDOS DE CONSULTA
    if (preg_match('/(quantos|quantas|listar|mostrar|ver|buscar).*funcionários?/', $comando)) {
        $funcionarios = $dao->listarTodos();
        $total = count($funcionarios);
        
        return [
            'acao' => 'listar_funcionarios',
            'mensagem' => "Encontrei {$total} funcionários ativos no sistema.",
            'dados' => [
                'total' => $total,
                'funcionarios' => array_map(function($func) {
                    return [
                        'id' => $func['id'],
                        'nome' => $func['nome'],
                        'cargo' => $func['cargo'],
                        'telefone' => $func['telefone']
                    ];
                }, $funcionarios)
            ]
        ];
    }
    
    // BUSCAR FUNCIONÁRIO ESPECÍFICO
    if (preg_match('/(buscar|procurar|achar|localizar).*funcionário.*([a-zA-Z\s]{3,})/', $comando, $matches)) {
        $nome = trim($matches[2]);
        $resultados = $dao->buscar($nome);
        
        if (count($resultados) > 0) {
            $func = $resultados[0];
            return [
                'acao' => 'buscar_funcionario',
                'mensagem' => "Encontrei o funcionário {$func['nome']}, cargo de {$func['cargo']}.",
                'dados' => [
                    'id' => $func['id'],
                    'nome' => $func['nome'],
                    'cargo' => $func['cargo'],
                    'telefone' => $func['telefone'],
                    'email' => $func['email'],
                    'data_contratacao' => $func['data_contratacao']
                ]
            ];
        } else {
            return [
                'acao' => 'buscar_funcionario',
                'mensagem' => "Não encontrei funcionário com o nome '{$nome}'.",
                'dados' => null
            ];
        }
    }
    
    // INFORMAÇÕES DE CONTATO
    if (preg_match('/(telefone|contato|whatsapp).*funcionário.*([a-zA-Z\s]{3,})/', $comando, $matches)) {
        $nome = trim($matches[2]);
        $resultados = $dao->buscar($nome);
        
        if (count($resultados) > 0) {
            $func = $resultados[0];
            return [
                'acao' => 'contato_funcionario',
                'mensagem' => "O telefone de {$func['nome']} é {$func['telefone']}.",
                'dados' => [
                    'nome' => $func['nome'],
                    'telefone' => $func['telefone'],
                    'email' => $func['email']
                ]
            ];
        } else {
            return [
                'acao' => 'contato_funcionario',
                'mensagem' => "Não encontrei funcionário com o nome '{$nome}'.",
                'dados' => null
            ];
        }
    }
    
    // FUNCIONÁRIOS POR CARGO
    if (preg_match('/(quantos|listar|mostrar).*([a-zA-Z\s]{3,})/', $comando, $matches) && 
        in_array(trim($matches[2]), ['ajudante', 'pedreiro', 'motorista', 'mestre de obras'])) {
        $cargo = trim($matches[2]);
        $todos = $dao->listarTodos();
        $filtrados = array_filter($todos, function($func) use ($cargo) {
            return stripos($func['cargo'], $cargo) !== false;
        });
        $total = count($filtrados);
        
        return [
            'acao' => 'funcionarios_por_cargo',
            'mensagem' => "Encontrei {$total} funcionários com cargo de {$cargo}.",
            'dados' => [
                'cargo' => $cargo,
                'total' => $total,
                'funcionarios' => array_map(function($func) {
                    return [
                        'nome' => $func['nome'],
                        'telefone' => $func['telefone']
                    ];
                }, $filtrados)
            ]
        ];
    }
    
    // CADASTRAR NOVO FUNCIONÁRIO (básico)
    if (preg_match('/(cadastrar|adicionar|contratar).*novo.*funcionário.*([a-zA-Z\s]{3,})/', $comando, $matches)) {
        $nome = trim($matches[2]);
        
        return [
            'acao' => 'iniciar_cadastro',
            'mensagem' => "Para cadastrar o funcionário {$nome}, preciso de mais informações: cargo, salário, telefone e data de contratação. Você pode fornecer estes dados?",
            'dados' => [
                'nome_sugerido' => $nome,
                'campos_necessarios' => ['cargo', 'salário', 'telefone', 'data_contratacao']
            ]
        ];
    }
    
    // COMANDO NÃO RECONHECIDO
    return [
        'acao' => 'comando_nao_reconhecido',
        'mensagem' => "Não entendi o comando '{$comando}'. Tente: 'quantos funcionários', 'buscar funcionário [nome]', 'telefone do funcionário [nome]', ou 'cadastrar novo funcionário [nome]'.",
        'dados' => null
    ];
}
?>
