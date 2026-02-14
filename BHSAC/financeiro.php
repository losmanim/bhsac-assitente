<?php
/**
 * Módulo de Gestão Financeira
 * Sistema de Gestão - BHSAC
 */

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/FinanceiroDAO.php';

// Exigir login - apenas gerentes e admin podem acessar financeiro
Auth::exigirNivel('gerente');

$usuarioLogado = Auth::getUsuario();

$dao = new FinanceiroDAO();
$mensagem = '';

// Definir período padrão (mês atual)
$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
$dataFim = $_GET['data_fim'] ?? date('Y-m-t');
$tipoFiltro = $_GET['tipo'] ?? '';
$categoriaFiltro = $_GET['categoria'] ?? '';
$termoBusca = $_GET['busca'] ?? '';

// Processar cadastro de nova movimentação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['cadastrar']) || isset($_POST['cadastrar_hidden']))) {
    try {
        $dados = [
            'tipo' => $_POST['tipo'],
            'categoria_id' => $_POST['categoria_id'],
            'funcionario_id' => !empty($_POST['funcionario_id']) ? $_POST['funcionario_id'] : null,
            'descricao' => $_POST['descricao'],
            'valor' => $_POST['valor'],
            'data_movimentacao' => $_POST['data_movimentacao'],
            'observacoes' => $_POST['observacoes'] ?? null
        ];
        $dao->cadastrarMovimentacao($dados);
        $mensagem = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Movimentação cadastrada com sucesso!</div>';
    } catch (Exception $e) {
        $mensagem = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Erro: ' . $e->getMessage() . '</div>';
    }
}

// Processar edição de movimentação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    try {
        $dados = [
            'tipo' => $_POST['tipo'],
            'categoria_id' => $_POST['categoria_id'],
            'funcionario_id' => !empty($_POST['funcionario_id']) ? $_POST['funcionario_id'] : null,
            'descricao' => $_POST['descricao'],
            'valor' => $_POST['valor'],
            'data_movimentacao' => $_POST['data_movimentacao'],
            'observacoes' => $_POST['observacoes'] ?? null
        ];
        $dao->atualizar($_POST['id'], $dados);
        $mensagem = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Movimentação atualizada com sucesso!</div>';
    } catch (Exception $e) {
        $mensagem = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Erro: ' . $e->getMessage() . '</div>';
    }
}

// Processar exclusão de movimentação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir'])) {
    try {
        $dao->excluir($_POST['id']);
        $mensagem = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Movimentação excluída com sucesso!</div>';
    } catch (Exception $e) {
        $mensagem = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Erro: ' . $e->getMessage() . '</div>';
    }
}

// Buscar dados
$filtros = [
    'data_inicio' => $dataInicio,
    'data_fim' => $dataFim,
    'tipo' => $tipoFiltro,
    'categoria_id' => $categoriaFiltro,
    'busca' => $termoBusca
];

$movimentacoes = $dao->listarMovimentacoes($filtros);
$categorias = $dao->listarCategorias();
$categoriasEntrada = $dao->listarCategorias('entrada');
$categoriasSaida = $dao->listarCategorias('saida');
$funcionarios = $dao->listarFuncionariosAtivos();

// Calcular estatísticas
$totalEntradas = $dao->calcularTotalEntradas($dataInicio, $dataFim);
$totalSaidas = $dao->calcularTotalSaidas($dataInicio, $dataFim);
$saldo = $totalEntradas - $totalSaidas;
$totalMovimentacoes = $dao->contarMovimentacoes($dataInicio, $dataFim);

// Verificar período de pagamento (dias 01-05) e salários pendentes
$isPeriodoPagamento = $dao->isPeriodoPagamento();
$funcionariosPendentes = [];
$totalSalariosPendentes = 0;
$categoriaSalariosId = null;

if ($isPeriodoPagamento) {
    $funcionariosPendentes = $dao->getFuncionariosPagamentoPendente();
    $totalSalariosPendentes = $dao->calcularTotalSalariosPendentes();
    $categoriaSalariosId = $dao->getCategoriaSalariosId();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão Financeira | BHSAC</title>
    <meta name="description" content="Módulo de Gestão Financeira - BH Service e Artefatos de Concreto">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- CSS Customizado -->
    <link rel="stylesheet" href="css/style.css">

    <!-- Estilos para impressão -->
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            .print-only {
                display: block !important;
            }

            body {
                background: white !important;
                color: black !important;
            }

            .card {
                border: 1px solid #ddd !important;
                background: white !important;
            }
        }

        .print-only {
            display: none;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header no-print">
        <div class="header-content">
            <!-- Logo e Título -->
            <div class="logo">
                <div class="logo-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="logo-text">
                    <h1>BHSAC - Financeiro</h1>
                    <span>Gestão Financeira</span>
                </div>
            </div>

            <!-- Filtro de Período -->
            <form class="filter-form" action="financeiro.php" method="GET">
                <div class="filter-box">
                    <label class="text-secondary"><i class="bi bi-calendar3"></i> Período:</label>
                    <input type="date" name="data_inicio" value="<?= htmlspecialchars($dataInicio) ?>" class="form-control form-control-sm" style="width: 130px;">
                    <span class="text-secondary">até</span>
                    <input type="date" name="data_fim" value="<?= htmlspecialchars($dataFim) ?>" class="form-control form-control-sm" style="width: 130px;">
                    <button type="submit" class="btn btn-outline btn-sm">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
            </form>

            <!-- Ações do Header -->
            <div class="header-actions">
                <!-- Botão Nova Categoria -->
                <button class="btn btn-outline btn-sm" data-bs-toggle="modal" data-bs-target="#modalNovaCategoria" title="Nova Categoria">
                    <i class="bi bi-plus-lg"></i> Categoria
                </button>

                <!-- Botão Nova Movimentação -->
                <button class="btn btn-primary btn-sm" onclick="scrollToForm()" title="Nova Movimentação">
                    <i class="bi bi-plus-circle"></i> Movimentação
                </button>

                <div class="dropdown d-inline-block ms-2">
                    <button class="btn btn-outline btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-grid-3x3-gap"></i> Módulos
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="manual.php"><i class="bi bi-book me-2"></i>Manual</a></li>
                        <li><a class="dropdown-item" href="producao.php"><i class="bi bi-bricks me-2"></i>Produção</a></li>
                        <li><a class="dropdown-item" href="index.php"><i class="bi bi-people me-2"></i>Funcionários</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><button class="dropdown-item" onclick="openReportModal()"><i class="bi bi-file-earmark-bar-graph me-2"></i>Relatório Financeiro</button></li>
                    </ul>
                </div>

                <span class="text-secondary ms-2 d-none d-lg-inline">
                    <i class="bi bi-calendar3"></i>
                    <?= date('d/m/Y') ?>
                </span>

                <!-- Menu do Usuário -->
                <div class="dropdown ms-2">
                    <button class="btn btn-outline btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= htmlspecialchars($usuarioLogado['nome']) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted small"><?= htmlspecialchars($usuarioLogado['email']) ?></span></li>
                        <li><span class="dropdown-item-text"><span class="badge bg-warning text-dark"><?= ucfirst($usuarioLogado['nivel']) ?></span></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if (Auth::ehAdmin()): ?>
                            <li><a class="dropdown-item" href="usuarios.php"><i class="bi bi-people-fill me-2"></i>Gerenciar Usuários</a></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item text-danger" href="login.php?logout=1"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- Header para impressão -->
    <div class="print-only" style="text-align: center; padding: 20px; border-bottom: 2px solid #f59e0b;">
        <h1 style="color: #f59e0b; margin: 0;">BH Service - Gestão Financeira</h1>
        <p style="margin: 5px 0;">BH Service e Artefatos de Concreto</p>
        <p style="font-size: 12px; color: #666;">Período: <?= date('d/m/Y', strtotime($dataInicio)) ?> a
            <?= date('d/m/Y', strtotime($dataFim)) ?>
        </p>
    </div>

    <!-- Container Principal -->
    <div class="main-container fade-in">

        <?= $mensagem ?>

        <?php if ($isPeriodoPagamento && count($funcionariosPendentes) > 0): ?>
            <!-- Alerta de Pagamento de Salários -->
            <div class="alert-salarios no-print">
                <div class="alert-salarios-header">
                    <div class="alert-salarios-icon">
                        <i class="bi bi-bell-fill"></i>
                    </div>
                    <div class="alert-salarios-info">
                        <h4><i class="bi bi-calendar-check"></i> Período de Pagamento de Salários (Dias 01-05)</h4>
                        <p>Existem <strong><?= count($funcionariosPendentes) ?> funcionário(s)</strong> com salário pendente
                            neste mês.</p>
                        <p class="total-pendente">Total a pagar: <strong>R$
                                <?= number_format($totalSalariosPendentes, 2, ',', '.') ?></strong></p>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="openPagamentoModal()">
                        <i class="bi bi-cash-coin"></i> Registrar Pagamentos
                    </button>
                </div>
                <div class="alert-salarios-list">
                    <?php foreach ($funcionariosPendentes as $fp): ?>
                        <div class="funcionario-pendente-item">
                            <span class="funcionario-nome"><i class="bi bi-person"></i>
                                <?= htmlspecialchars($fp['nome']) ?></span>
                            <span class="funcionario-salario">R$ <?= number_format($fp['salario'], 2, ',', '.') ?></span>
                            <button class="btn btn-outline btn-sm"
                                onclick="registrarPagamentoIndividual(<?= $fp['id'] ?>, '<?= htmlspecialchars($fp['nome'], ENT_QUOTES) ?>', <?= $fp['salario'] ?>)">
                                <i class="bi bi-check"></i> Pagar
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Cards de Estatísticas -->
        <div class="stats-grid no-print">
            <div class="stat-card <?= $saldo >= 0 ? 'green' : 'red' ?>">
                <div class="stat-icon">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="stat-value <?= $saldo >= 0 ? 'text-success' : 'text-danger' ?>">R$
                    <?= number_format($saldo, 2, ',', '.') ?>
                </div>
                <div class="stat-label">Saldo do Período</div>
            </div>

            <div class="stat-card green">
                <div class="stat-icon">
                    <i class="bi bi-arrow-up-circle"></i>
                </div>
                <div class="stat-value text-success">R$ <?= number_format($totalEntradas, 2, ',', '.') ?></div>
                <div class="stat-label">Total de Entradas</div>
            </div>

            <div class="stat-card red">
                <div class="stat-icon">
                    <i class="bi bi-arrow-down-circle"></i>
                </div>
                <div class="stat-value text-danger">R$ <?= number_format($totalSaidas, 2, ',', '.') ?></div>
                <div class="stat-label">Total de Saídas</div>
            </div>

            <div class="stat-card blue">
                <div class="stat-icon">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="stat-value"><?= $totalMovimentacoes ?></div>
                <div class="stat-label">Movimentações</div>
            </div>
        </div>

        <!-- Grid de Conteúdo -->
        <div class="content-grid">

            <!-- Lista de Movimentações -->
            <div class="card">
                <div class="card-header">
                    <h2 class="text-primary"><i class="bi bi-list-ul"></i> Movimentações</h2>
                    <div class="header-filters">
                        <select id="filterTipo" onchange="filterTable()" class="filter-select">
                            <option value="">Todos</option>
                            <option value="entrada" <?= $tipoFiltro === 'entrada' ? 'selected' : '' ?>>Entradas</option>
                            <option value="saida" <?= $tipoFiltro === 'saida' ? 'selected' : '' ?>>Saídas</option>
                        </select>
                    </div>
                </div>
                <div class="card-body text-primary">
                    <?php if (count($movimentacoes) > 0): ?>
                        <div class="table-container">
                            <table class="employees-table" id="movimentacoesTable">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Descrição</th>
                                        <th>Categoria</th>
                                        <th>Valor</th>
                                        <th class="no-print">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($movimentacoes as $m): ?>
                                        <tr class="row-<?= $m['tipo'] ?>" data-tipo="<?= $m['tipo'] ?>">
                                            <td><?= date('d/m/Y', strtotime($m['data_movimentacao'])) ?></td>
                                            <td>
                                                <div class="mov-info">
                                                    <div class="mov-desc"><?= htmlspecialchars($m['descricao']) ?></div>
                                                    <?php if ($m['funcionario_nome']): ?>
                                                        <div class="mov-funcionario">
                                                            <i class="bi bi-person"></i>
                                                            <?= htmlspecialchars($m['funcionario_nome']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge-categoria"
                                                    style="background-color: <?= $m['categoria_cor'] ?>20; color: <?= $m['categoria_cor'] ?>; border: 1px solid <?= $m['categoria_cor'] ?>40;">
                                                    <i class="bi <?= $m['categoria_icone'] ?>"></i>
                                                    <?= htmlspecialchars($m['categoria_nome']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span
                                                    class="valor-<?= $m['tipo'] ?> <?= $m['tipo'] === 'entrada' ? 'text-success' : 'text-danger' ?>">
                                                    <?= $m['tipo'] === 'entrada' ? '+' : '-' ?> R$
                                                    <?= number_format($m['valor'], 2, ',', '.') ?>
                                                </span>
                                            </td>
                                            <td class="no-print">
                                                <div class="action-buttons">
                                                    <button class="btn btn-outline btn-sm"
                                                        onclick="showDetails(<?= $m['id'] ?>)" title="Ver detalhes">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline btn-sm"
                                                        onclick="editMovimentacao(<?= $m['id'] ?>)" title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-outline btn-sm btn-danger-outline"
                                                        onclick="confirmDelete(<?= $m['id'] ?>, '<?= htmlspecialchars($m['descricao'], ENT_QUOTES) ?>')"
                                                        title="Excluir">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>Nenhuma movimentação encontrada no período.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Formulário de Nova Movimentação -->
            <div class="card no-print">
                <div class="card-header">
                    <h2 class="text-primary"><i class="bi bi-plus-circle"></i> Nova Movimentação</h2>
                </div>
                <div class="card-body">
                    <form action="financeiro.php" method="post" id="formMovimentacao"
                        onsubmit="return confirmarRegistro(event)">
                        <input type="hidden" name="cadastrar_hidden" value="1">
                        <!-- Tipo de Movimentação -->
                        <div class="form-group">
                            <label><i class="bi bi-arrow-left-right"></i> Tipo</label>
                            <div class="tipo-toggle">
                                <input type="radio" id="tipo-entrada" name="tipo" value="entrada" checked>
                                <label for="tipo-entrada" class="tipo-label entrada">
                                    <i class="bi bi-arrow-up-circle"></i> Entrada
                                </label>
                                <input type="radio" id="tipo-saida" name="tipo" value="saida">
                                <label for="tipo-saida" class="tipo-label saida">
                                    <i class="bi bi-arrow-down-circle"></i> Saída
                                </label>
                            </div>
                        </div>

                        <!-- Categoria -->
                        <div class="form-group">
                            <label for="categoria_id"><i class="bi bi-tag"></i> Categoria</label>
                            <select class="form-control" id="categoria_id" name="categoria_id" required>
                                <optgroup label="Entradas" id="categorias-entrada">
                                    <?php foreach ($categoriasEntrada as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Saídas" id="categorias-saida" disabled>
                                    <?php foreach ($categoriasSaida as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>

                        <!-- Descrição -->
                        <div class="form-group">
                            <label for="descricao"><i class="bi bi-card-text"></i> Descrição</label>
                            <input type="text" class="form-control" id="descricao" name="descricao" required
                                placeholder="Descreva a movimentação">
                        </div>

                        <!-- Valor e Data -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="valor"><i class="bi bi-currency-dollar"></i> Valor</label>
                                <input type="number" step="0.01" min="0.01" class="form-control" id="valor" name="valor"
                                    required placeholder="0,00">
                            </div>
                            <div class="form-group">
                                <label for="data_movimentacao"><i class="bi bi-calendar-event"></i> Data</label>
                                <input type="date" class="form-control" id="data_movimentacao" name="data_movimentacao"
                                    required value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <!-- Funcionário (opcional) -->
                        <div class="form-group">
                            <label for="funcionario_id"><i class="bi bi-person"></i> Funcionário (opcional)</label>
                            <select class="form-control" id="funcionario_id" name="funcionario_id">
                                <option value="">-- Nenhum --</option>
                                <?php foreach ($funcionarios as $f): ?>
                                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Observações -->
                        <div class="form-group">
                            <label for="observacoes"><i class="bi bi-chat-text"></i> Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="2"
                                placeholder="Observações adicionais..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="bi bi-plus-circle"></i>
                            Registrar Movimentação
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark">
                <div class="modal-header">
                    <h5 class="modal-title text-warning"><i class="bi bi-receipt"></i> Detalhes da Movimentação</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edição -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bg-dark">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Movimentação</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="editModalContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Exclusão -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle"></i> Confirmar Exclusão
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir a movimentação <strong id="deleteMovName"></strong>?</p>
                    <p class="text-secondary small">Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="id" id="deleteMovId">
                        <button type="submit" name="excluir" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Excluir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Cadastro -->
    <div class="modal fade" id="confirmationModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark">
                <div class="modal-header">
                    <h5 class="modal-title text-info"><i class="bi bi-question-circle"></i> Confirmar Lançamento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info border-info" style="background-color: rgba(0, 0, 0, 0.777);">
                        <i class="bi bi-info-circle text-warning"></i>
                        <p class="text-danger">Revise os dados antes de confirmar.</p>
                    </div>
                    <div class="details-grid">
                        <div class="detail-item">
                            <i class="bi bi-arrow-left-right"></i>
                            <div>
                                <span class="detail-label">Tipo</span>
                                <span class="detail-value" id="conf-tipo"></span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <i class="bi bi-tag"></i>
                            <div>
                                <span class="detail-label">Categoria</span>
                                <span class="detail-value" id="conf-categoria"></span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <i class="bi bi-currency-dollar"></i>
                            <div>
                                <span class="detail-label">Valor</span>
                                <span class="detail-value" id="conf-valor"></span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <i class="bi bi-calendar-event"></i>
                            <div>
                                <span class="detail-label">Data</span>
                                <span class="detail-value" id="conf-data"></span>
                            </div>
                        </div>
                        <div class="detail-item full-width">
                            <i class="bi bi-card-text"></i>
                            <div>
                                <span class="detail-label">Descrição</span>
                                <span class="detail-value" id="conf-descricao"></span>
                            </div>
                        </div>
                        <div class="detail-item full-width" id="conf-funcionario-container" style="display:none;">
                            <i class="bi bi-person"></i>
                            <div>
                                <span class="detail-label">Funcionário</span>
                                <span class="detail-value" id="conf-funcionario"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">
                        <i class="bi bi-pencil"></i> Editar/Revisar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="submitMovimentacao()">
                        <i class="bi bi-check-lg"></i> Sim, Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Relatório -->
    <div class="modal fade" id="reportModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content bg-dark">
                <div class="modal-header">
                    <h5 class="modal-title text-warning"><i class="bi bi-file-earmark-bar-graph"></i> Relatório
                        Financeiro</h5>
                    <div class="modal-header-actions">
                        <button class="btn btn-outline btn-sm me-2" onclick="window.print()">
                            <i class="bi bi-printer"></i> Imprimir
                        </button>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <div class="modal-body" id="reportContent">
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Pagamento de Salários -->
    <div class="modal fade" id="pagamentoModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content bg-dark">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cash-coin"></i> Registrar Pagamento de Salário</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="pagamentoModalContent">
                    <form method="POST" id="formPagamento">
                        <input type="hidden" name="tipo" value="saida">
                        <input type="hidden" name="categoria_id" value="<?= $categoriaSalariosId ?>">

                        <div class="form-group">
                            <label><i class="bi bi-person"></i> Funcionário</label>
                            <select class="form-control" id="pagamento_funcionario_id" name="funcionario_id" required
                                onchange="atualizarValorSalario()">
                                <option value="">-- Selecione --</option>
                                <?php foreach ($funcionariosPendentes as $fp): ?>
                                    <option value="<?= $fp['id'] ?>" data-salario="<?= $fp['salario'] ?>">
                                        <?= htmlspecialchars($fp['nome']) ?> - R$
                                        <?= number_format($fp['salario'], 2, ',', '.') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="bi bi-currency-dollar"></i> Valor</label>
                                <input type="number" step="0.01" class="form-control" id="pagamento_valor" name="valor"
                                    required>
                            </div>
                            <div class="form-group">
                                <label><i class="bi bi-calendar-event"></i> Data do Pagamento</label>
                                <input type="date" class="form-control" name="data_movimentacao"
                                    value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="bi bi-card-text"></i> Descrição</label>
                            <input type="text" class="form-control" id="pagamento_descricao" name="descricao"
                                value="Pagamento de salário - <?= date('m/Y') ?>" required>
                        </div>

                        <div class="form-group">
                            <label><i class="bi bi-chat-text"></i> Observações</label>
                            <textarea class="form-control" name="observacoes" rows="2"
                                placeholder="Observações adicionais..."></textarea>
                        </div>

                        <button type="submit" name="cadastrar" class="btn btn-primary btn-block">
                            <i class="bi bi-check-circle"></i> Registrar Pagamento
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer no-print">
        <p>© <?= date('Y') ?> BHSAC - BH Service e Artefatos de Concreto</p>
        <p>Desenvolvido por <a href="https://github.com/losmanim" target="_blank">Luiz Antonio</a></p>
    </footer>

    <!-- Modal Nova Categoria -->
    <div class="modal fade" id="modalNovaCategoria" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark border-secondary bg-opacity-95 shadow-lg"
                style="backdrop-filter: blur(10px);">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title fw-bold text-primary"><i class="bi bi-plus-lg me-2"></i> Criar Nova Categoria
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="formNovaCategoria">
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">NOME DA CATEGORIA</label>
                            <input type="text" name="nome" class="form-control bg-dark text-white border-secondary"
                                placeholder="Ex: Aluguel, Internet, etc." required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-secondary small fw-bold">TIPO</label>
                                <select name="tipo" class="form-select bg-dark text-white border-secondary" required>
                                    <option value="saida">Despesa (Saída)</option>
                                    <option value="entrada">Receita (Entrada)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-secondary small fw-bold">COR</label>
                                <input type="color" name="cor"
                                    class="form-control form-control-color bg-dark border-secondary w-100"
                                    value="#6b7280">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">ÍCONE (Bootstrap Icons)</label>
                            <select name="icone" class="form-select bg-dark text-white border-secondary">
                                <option value="bi-tag">Tag</option>
                                <option value="bi-cash">Dinheiro</option>
                                <option value="bi-cart">Venda</option>
                                <option value="bi-tools">Serviço</option>
                                <option value="bi-lightning">Energia</option>
                                <option value="bi-water">Água</option>
                                <option value="bi-house">Aluguel</option>
                                <option value="bi-truck">Transporte</option>
                                <option value="bi-person">Salário</option>
                                <option value="bi-gear">Manutenção</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary px-4" onclick="cadastrarNovaCategoria()">
                        <i class="bi bi-save me-1"></i> Criar Categoria
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Dados para JavaScript
        const categoriasEntrada = <?= json_encode($categoriasEntrada) ?>;
        const categoriasSaida = <?= json_encode($categoriasSaida) ?>;
        const funcionarios = <?= json_encode($funcionarios) ?>;
        const funcionariosPendentes = <?= json_encode($funcionariosPendentes) ?>;
        const categoriaSalariosId = <?= $categoriaSalariosId ? $categoriaSalariosId : 'null' ?>;

        // Função para formatar moeda
        const formatMoney = (value) => {
            return parseFloat(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }

        // Função para formatar data
        const formatDateBr = (dateString) => {
            const date = new Date(dateString + 'T00:00:00'); // Compensar fuso
            return date.toLocaleDateString('pt-BR');
        }

        // --- SISTEMA DE CONFIRMAÇÃO ---
        let formParaEnviar = null;

        function confirmarRegistro(event) {
            event.preventDefault(); // Impede o envio imediato
            formParaEnviar = event.target;

            // Coletar dados do formulário
            const formData = new FormData(formParaEnviar);

            // Obter valores
            const tipo = formData.get('tipo');
            const categoriaSelect = formParaEnviar.querySelector('#categoria_id');
            const categoriaTexto = categoriaSelect.options[categoriaSelect.selectedIndex].text;
            const descricao = formData.get('descricao');
            const valor = formData.get('valor');
            const data = formData.get('data_movimentacao');

            const funcionarioSelect = formParaEnviar.querySelector('#funcionario_id');
            const funcionarioId = formData.get('funcionario_id');
            const funcionarioTexto = funcionarioId ? funcionarioSelect.options[funcionarioSelect.selectedIndex].text : null;

            // Preencher Modal
            document.getElementById('conf-tipo').innerHTML = tipo === 'entrada'
                ? '<span class="text-success"><i class="bi bi-arrow-up-circle"></i> Entrada</span>'
                : '<span class="text-danger"><i class="bi bi-arrow-down-circle"></i> Saída</span>';

            document.getElementById('conf-categoria').textContent = categoriaTexto;
            document.getElementById('conf-valor').textContent = formatMoney(valor);
            document.getElementById('conf-data').textContent = formatDateBr(data);
            document.getElementById('conf-descricao').textContent = descricao;

            const funcContainer = document.getElementById('conf-funcionario-container');
            if (funcionarioTexto) {
                document.getElementById('conf-funcionario').textContent = funcionarioTexto;
                funcContainer.style.display = 'flex';
            } else {
                funcContainer.style.display = 'none';
            }

            // Exibir Modal
            const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            modal.show();

            return false;
        }

        function submitMovimentacao() {
            if (formParaEnviar) {
                formParaEnviar.submit();
            }
        }
        // ------------------------------

        // Abrir modal de pagamento de salários
        function openPagamentoModal() {
            const modal = new bootstrap.Modal(document.getElementById('pagamentoModal'));
            modal.show();
        }

        // Atualizar valor do salário quando selecionar funcionário
        function atualizarValorSalario() {
            const select = document.getElementById('pagamento_funcionario_id');
            const valorInput = document.getElementById('pagamento_valor');
            const descricaoInput = document.getElementById('pagamento_descricao');
            const selectedOption = select.options[select.selectedIndex];

            if (selectedOption && selectedOption.dataset.salario) {
                valorInput.value = selectedOption.dataset.salario;
                const nomeFunc = selectedOption.text.split(' - ')[0];
                descricaoInput.value = `Pagamento de salário - ${nomeFunc} - <?= date('m/Y') ?>`;
            }
        }

        // Registrar pagamento individual (click no botão "Pagar" da lista)
        function registrarPagamentoIndividual(id, nome, salario) {
            const modal = new bootstrap.Modal(document.getElementById('pagamentoModal'));

            // Selecionar o funcionário no select
            const select = document.getElementById('pagamento_funcionario_id');
            select.value = id;

            // Preencher valor e descrição
            document.getElementById('pagamento_valor').value = salario;
            document.getElementById('pagamento_descricao').value = `Pagamento de salário - ${nome} - <?= date('m/Y') ?>`;

            modal.show();
        }

        // Alternar categorias baseado no tipo selecionado
        document.querySelectorAll('input[name="tipo"]').forEach(radio => {
            radio.addEventListener('change', function () {
                const categoriaSelect = document.getElementById('categoria_id');
                const entradaGroup = document.getElementById('categorias-entrada');
                const saidaGroup = document.getElementById('categorias-saida');

                if (this.value === 'entrada') {
                    entradaGroup.disabled = false;
                    saidaGroup.disabled = true;
                    categoriaSelect.value = categoriasEntrada[0]?.id || '';
                } else {
                    entradaGroup.disabled = true;
                    saidaGroup.disabled = false;
                    categoriaSelect.value = categoriasSaida[0]?.id || '';
                }
            });
        });

        // Filtrar tabela por tipo
        function filterTable() {
            const tipo = document.getElementById('filterTipo').value;
            const rows = document.querySelectorAll('#movimentacoesTable tbody tr');

            rows.forEach(row => {
                if (!tipo || row.dataset.tipo === tipo) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Mostrar detalhes da movimentação
        function showDetails(id) {
            const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
            const modalContent = document.getElementById('modalContent');

            modalContent.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
            `;

            modal.show();

            fetch('api/movimentacao.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const m = data.movimentacao;
                        const tipoClass = m.tipo === 'entrada' ? 'text-success' : 'text-danger';
                        const tipoIcon = m.tipo === 'entrada' ? 'bi-arrow-up-circle' : 'bi-arrow-down-circle';
                        const tipoLabel = m.tipo === 'entrada' ? 'Entrada' : 'Saída';

                        modalContent.innerHTML = `
                            <div class="details-header">
                                <div class="details-avatar" style="background: ${m.categoria_cor}">
                                    <i class="bi ${m.categoria_icone}"></i>
                                </div>
                                <div class="details-info">
                                    <h3 class="text-warning">${m.descricao}</h3>
                                    <span class="badge-categoria" style="background-color: ${m.categoria_cor}20; color: ${m.categoria_cor}; border: 1px solid ${m.categoria_cor}40;">
                                        ${m.categoria_nome}
                                    </span>
                                    <span class="badge ${m.tipo === 'entrada' ? 'badge-active' : 'badge-inactive'}">
                                        <i class="bi ${tipoIcon}"></i> ${tipoLabel}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="details-grid">
                                <div class="detail-item">
                                    <i class="bi bi-currency-dollar ${tipoClass}"></i>
                                    <div>
                                        <span class="detail-label">Valor</span>
                                        <span class="detail-value ${tipoClass}">
                                            ${m.tipo === 'entrada' ? '+' : '-'} R$ ${parseFloat(m.valor).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <i class="bi bi-calendar3"></i>
                                    <div>
                                        <span class="detail-label">Data</span>
                                        <span class="detail-value text-primary">${formatDate(m.data_movimentacao)}</span>
                                    </div>
                                </div>
                                
                                ${m.funcionario_nome ? `
                                <div class="detail-item full-width">
                                    <i class="bi bi-person"></i>
                                    <div>
                                        <span class="detail-label">Funcionário</span>
                                        <span class="detail-value text-white">${m.funcionario_nome}</span>
                                    </div>
                                </div>
                                ` : ''}
                                
                                ${m.observacoes ? `
                                <div class="detail-item full-width">
                                    <i class="bi bi-chat-text"></i>
                                    <div>
                                        <span class="detail-label">Observações</span>
                                        <span class="detail-value text-white">${m.observacoes}</span>
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        `;
                    } else {
                        modalContent.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                    }
                })
                .catch(error => {
                    modalContent.innerHTML = `<div class="alert alert-danger">Erro ao carregar detalhes</div>`;
                });
        }

        // Editar movimentação
        function editMovimentacao(id) {
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            const modalContent = document.getElementById('editModalContent');

            modalContent.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
            `;

            modal.show();

            fetch('api/movimentacao.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const m = data.movimentacao;

                        let categoriasOptions = '';
                        if (m.tipo === 'entrada') {
                            categoriasEntrada.forEach(c => {
                                categoriasOptions += `<option value="${c.id}" ${c.id == m.categoria_id ? 'selected' : ''}>${c.nome}</option>`;
                            });
                        } else {
                            categoriasSaida.forEach(c => {
                                categoriasOptions += `<option value="${c.id}" ${c.id == m.categoria_id ? 'selected' : ''}>${c.nome}</option>`;
                            });
                        }

                        let funcionariosOptions = '<option value="">-- Nenhum --</option>';
                        funcionarios.forEach(f => {
                            funcionariosOptions += `<option value="${f.id}" ${f.id == m.funcionario_id ? 'selected' : ''}>${f.nome}</option>`;
                        });

                        modalContent.innerHTML = `
                            <form method="POST">
                                <input type="hidden" name="id" value="${m.id}">
                                <input type="hidden" name="tipo" value="${m.tipo}">
                                
                                <div class="form-group">
                                    <label><i class="bi bi-tag"></i> Categoria</label>
                                    <select class="form-control" name="categoria_id" required>
                                        ${categoriasOptions}
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="bi bi-card-text"></i> Descrição</label>
                                    <input type="text" class="form-control" name="descricao" value="${m.descricao}" required>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label><i class="bi bi-currency-dollar"></i> Valor</label>
                                        <input type="number" step="0.01" class="form-control" name="valor" value="${m.valor}" required>
                                    </div>
                                    <div class="form-group">
                                        <label><i class="bi bi-calendar-event"></i> Data</label>
                                        <input type="date" class="form-control" name="data_movimentacao" value="${m.data_movimentacao}" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="bi bi-person"></i> Funcionário</label>
                                    <select class="form-control" name="funcionario_id">
                                        ${funcionariosOptions}
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="bi bi-chat-text"></i> Observações</label>
                                    <textarea class="form-control" name="observacoes" rows="2">${m.observacoes || ''}</textarea>
                                </div>
                                
                                <button type="submit" name="editar" class="btn btn-primary btn-block">
                                    <i class="bi bi-check-circle"></i> Salvar Alterações
                                </button>
                            </form>
                        `;
                    } else {
                        modalContent.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                    }
                })
                .catch(error => {
                    modalContent.innerHTML = `<div class="alert alert-danger">Erro ao carregar dados</div>`;
                });
        }

        // Confirmar exclusão
        function confirmDelete(id, descricao) {
            document.getElementById('deleteMovId').value = id;
            document.getElementById('deleteMovName').textContent = descricao;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        // Abrir modal de relatório
        function openReportModal() {
            const modal = new bootstrap.Modal(document.getElementById('reportModal'));
            const reportContent = document.getElementById('reportContent');

            const entradas = <?= $totalEntradas ?>;
            const saidas = <?= $totalSaidas ?>;
            const saldo = <?= $saldo ?>;
            const saldoClass = saldo >= 0 ? 'text-success' : 'text-danger';

            reportContent.innerHTML = `
                <div class="report-summary">
                    <h4>Resumo do Período</h4>
                    <p class="text-success">De ${formatDate('<?= $dataInicio ?>')} a ${formatDate('<?= $dataFim ?>')}</p>
                    
                    <div class="report-stats d-flex justify-content-between">
                        <div class="report-stat green">
                            <i class="bi bi-arrow-up-circle"></i>
                            <div>
                                <span class="label">Total de Entradas</span>
                                <span class="value text-success">R$ ${entradas.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</span>
                            </div>
                        </div>
                        <div class="report-stat red">
                            <i class="bi bi-arrow-down-circle"></i>
                            <div>
                                <span class="label">Total de Saídas</span>
                                <span class="value text-danger">R$ ${saidas.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</span>
                            </div>
                        </div>
                        <div class="report-stat ${saldo >= 0 ? 'green' : 'red'}">
                            <i class="bi bi-wallet2"></i>
                            <div>
                                <span class="label">Saldo</span>
                                <span class="value ${saldoClass}">R$ ${saldo.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">
                
                <h5>Movimentações do Período</h5>
                <table class="employees-table mt-3">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${generateReportRows()}
                    </tbody>
                </table>
            `;

            modal.show();
        }

        // Gerar linhas do relatório
        function generateReportRows() {
            const movData = <?= json_encode($movimentacoes) ?>;
            let html = '';

            movData.forEach(m => {
                const tipoClass = m.tipo === 'entrada' ? 'text-success' : 'text-danger';
                const sinal = m.tipo === 'entrada' ? '+' : '-';

                html += `
                    <tr class="text-white">
                        <td>${formatDate(m.data_movimentacao)}</td>
                        <td>${m.descricao}</td>
                        <td>
                            <span class="badge-categoria" style="background-color: ${m.categoria_cor}20; color: ${m.categoria_cor};">
                                ${m.categoria_nome}
                            </span>
                        </td>
                        <td class="${tipoClass}">${sinal} R$ ${parseFloat(m.valor).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</td>
                    </tr>
                `;
            });

            return html || '<tr><td colspan="4" class="text-center text-secondary">Nenhuma movimentação no período</td></tr>';
        }

        // Formatar data
        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr + 'T00:00:00');
            return date.toLocaleDateString('pt-BR');
        }

        // Função para scroll até o formulário
        function scrollToForm() {
            document.getElementById('formMovimentacao').scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
            // Focar no primeiro campo
            setTimeout(() => {
                document.getElementById('descricao').focus();
            }, 500);
        }

        async function cadastrarNovaCategoria() {
            const form = document.getElementById('formNovaCategoria');
            const formData = new FormData(form);
            const dados = {
                acao: 'save_categoria',
                nome: formData.get('nome'),
                tipo: formData.get('tipo'),
                cor: formData.get('cor'),
                icone: formData.get('icone')
            };

            try {
                const response = await fetch('api/movimentacao.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dados)
                });
                const data = await response.json();
                if (data.success) {
                    alert('Categoria criada com sucesso!');
                    location.reload();
                } else {
                    alert('Erro ao criar categoria: ' + data.error);
                }
            } catch (error) {
                alert('Erro na comunicação com o servidor.');
            }
        }
    </script>
</body>

</html>