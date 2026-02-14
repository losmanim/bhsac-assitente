<?php
/**
 * Módulo de Gestão de Produção e Serviços
 * Sistema de Gestão - BHSAC
 */

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/ProducaoDAO.php';

// Exigir login para acessar esta página
Auth::exigirLogin();

$usuarioLogado = Auth::getUsuario();

$dao = new ProducaoDAO();
$mensagem = '';

// Processar lançamento via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar'])) {
    try {
        $dados = [
            'item_id' => $_POST['item_id'],
            'tipo_operacao' => $_POST['tipo_operacao'],
            'quantidade' => $_POST['quantidade'],
            'valor_total' => $_POST['valor_total'] ?? 0,
            'data_operacao' => $_POST['data_operacao'],
            'observacoes' => $_POST['observacoes'] ?? ''
        ];

        if (!empty($_POST['id'])) {
            $dao->atualizarOperacao($_POST['id'], $dados);
            $mensagem = '<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> Registro atualizado com sucesso!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        } else {
            $dao->registrarOperacao($dados);
            $mensagem = '<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> Registro realizado com sucesso!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    } catch (Exception $e) {
        $mensagem = '<div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-circle"></i> Erro: ' . $e->getMessage() . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// Filtros básicos
$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
$dataFim = $_GET['data_fim'] ?? date('Y-m-t');

$registros = $dao->listarRegistros([
    'data_inicio' => $dataInicio,
    'data_fim' => $dataFim,
    'limite' => 100
]);

$itens = $dao->listarItens();

// Cálculos para o Dashboard
$totalArtefatos = 0;
$totalServicosKm = 0;
$contagemServicos = 0;
$totalMateriais = 0;

foreach ($registros as $reg) {
    if ($reg['categoria'] === 'Artefato' && $reg['tipo_operacao'] === 'Produção') {
        $totalArtefatos += $reg['quantidade'];
    } elseif ($reg['categoria'] === 'Serviço') {
        $contagemServicos++;
        if ($reg['unidade'] === 'km') {
            $totalServicosKm += $reg['quantidade'];
        }
    } elseif ($reg['categoria'] === 'Material' && $reg['tipo_operacao'] === 'Consumo') {
        $totalMateriais += $reg['quantidade'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produção e Serviços | BHSAC</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS Customizado -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary: #f59e0b;
            --primary-dark: #d97706;
            --primary-light: #fbbf24;
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --text-primary: #ffffff;
            /* Brighter white for better contrast */
            --text-secondary: #cbd5e1;
            /* Lighter gray for better visibility */
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--bg-dark) 0%, #1a1a2e 100%);
            color: var(--text-primary);
        }

        .header {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid var(--primary);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo-text h1 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
            line-height: 1;
        }

        .logo-text span {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 2px;
            display: block;
            margin-top: 2px;
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary), #ea580c);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
            color: white;
        }

        .card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
        }

        .card-header h5 {
            color: var(--primary);
            font-weight: 700;
        }

        .table {
            color: var(--text-primary);
        }

        .table-dark {
            --bs-table-bg: rgba(0, 0, 0, 0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), #ea580c);
            border: none;
            font-weight: 600;
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }

        .stat-card {
            padding: 1.5rem;
            transition: transform 0.2s;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary);
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .text-muted {
            color: var(--text-secondary) !important;
        }

        .form-label {
            color: var(--text-secondary);
            font-weight: 500;
        }

        .form-control,
        .form-select {
            background-color: #374151 !important;
            border-color: #4b5563 !important;
            color: white !important;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 0.25rem rgba(245, 158, 11, 0.25) !important;
        }

        .report-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }

        .report-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
    </style>
</head>

<body>

    <header class="header mb-4">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="logo-icon me-3">
                    <i class="bi bi-bricks"></i>
                </div>
                <div class="logo-text">
                    <h1 class="mb-0">BHSAC</h1>
                    <span>Produção e Serviços</span>
                </div>
            </div>

            <nav class="d-flex gap-3 align-items-center">
                <a href="manual.php" class="btn btn-outline-light btn-sm" title="Manual de Uso">
                    <i class="bi bi-book pe-1"></i> Manual
                </a>
                <a href="index.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-people pe-1"></i> Funcionários
                </a>
                <a href="financeiro.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-wallet2 pe-1"></i> Financeiro
                </a>

                <!-- Menu do Usuário -->
                <div class="dropdown ms-2">
                    <button class="btn btn-outline-warning btn-sm dropdown-toggle" type="button"
                        data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= htmlspecialchars($usuarioLogado['nome']) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span
                                class="dropdown-item-text text-muted small"><?= htmlspecialchars($usuarioLogado['email']) ?></span>
                        </li>
                        <li><span class="dropdown-item-text"><span
                                    class="badge bg-warning text-dark"><?= ucfirst($usuarioLogado['nivel']) ?></span></span>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <?php if (Auth::ehAdmin()): ?>
                            <li><a class="dropdown-item" href="usuarios.php"><i class="bi bi-people-fill me-2"></i>Gerenciar
                                    Usuários</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                        <?php endif; ?>
                        <li><a class="dropdown-item text-danger" href="login.php?logout=1"><i
                                    class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                    </ul>
                </div>
            </nav>
        </div>
    </header>

    <main class="container-fluid px-4 pb-5">
        <?= $mensagem ?>

        <!-- Dashboard Rapidinho -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card border-start border-4 border-success">
                    <div class="stat-icon text-success"><i class="bi bi-hammer"></i></div>
                    <div class="h3 mb-1">
                        <?= number_format($totalArtefatos, 0, ',', '.') ?> <small class="fs-6">un</small>
                    </div>
                    <div class="text-muted">Artefatos Produzidos</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-start border-4 border-primary">
                    <div class="stat-icon text-primary"><i class="bi bi-truck"></i></div>
                    <div class="h3 mb-1">
                        <?= number_format($totalServicosKm, 1, ',', '.') ?> <small class="fs-6">km</small>
                    </div>
                    <div class="text-muted">
                        <?= $contagemServicos ?> Fretes/Serviços Realizados
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-start border-4 border-danger">
                    <div class="stat-icon text-danger"><i class="bi bi-box-seam"></i></div>
                    <div class="h3 mb-1">
                        <?= number_format($totalMateriais, 1, ',', '.') ?> <small class="fs-6">insumos</small>
                    </div>
                    <div class="text-muted">Materiais Consumidos</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-start border-4 border-warning">
                    <div class="stat-icon text-warning"><i class="bi bi-calendar-check"></i></div>
                    <div class="h3 mb-1">
                        <?= ucfirst(date('M/Y')) ?>
                    </div>
                    <div class="text-muted">Período Selecionado</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Lançamento -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-plus-circle pe-2"></i> Novo Lançamento</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                            data-bs-target="#modalNovoItem">
                            <i class="bi bi-plus-lg"></i> Novo Item
                        </button>
                    </div>
                    <div class="card-body">
                        <form action="producao.php" method="POST">
                            <input type="hidden" name="registrar" value="1">
                            <div class="mb-3">
                                <label class="form-label">Item / Produto</label>
                                <select name="item_id" class="form-select bg-dark text-white border-secondary" required>
                                    <option value="">Selecione...</option>
                                    <?php
                                    $cat_atual = '';
                                    foreach ($itens as $item):
                                        if ($cat_atual != $item['categoria']):
                                            if ($cat_atual != '')
                                                echo '</optgroup>';
                                            $cat_atual = $item['categoria'];
                                            echo '<optgroup label="' . $cat_atual . '">';
                                        endif;
                                        ?>
                                        <option value="<?= $item['id'] ?>">
                                            <?= $item['nome'] ?> (
                                            <?= $item['unidade'] ?>)
                                        </option>
                                    <?php endforeach;
                                    echo '</optgroup>'; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tipo de Operação</label>
                                <select name="tipo_operacao" class="form-select bg-dark text-white border-secondary"
                                    required>
                                    <option value="Produção">Produção</option>
                                    <option value="Serviço">Serviço</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Quantidade</label>
                                    <input type="number" step="0.01" name="quantidade"
                                        class="form-control bg-dark text-white border-secondary" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Data</label>
                                    <input type="date" name="data_operacao" value="<?= date('Y-m-d') ?>"
                                        class="form-control bg-dark text-white border-secondary" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Valor Total (opcional)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-secondary text-white border-secondary">R$</span>
                                    <input type="number" step="0.01" name="valor_total"
                                        class="form-control bg-dark text-white border-secondary">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Observações</label>
                                <textarea name="observacoes" class="form-control bg-dark text-white border-secondary"
                                    rows="3"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 mt-2">
                                <i class="bi bi-save pe-2"></i> Gravar Registro
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Listagem de Registros -->
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-list-check pe-2"></i> Registros Recentes</h5>
                        <form method="GET" class="d-flex gap-2" id="filterForm">
                            <input type="date" name="data_inicio"
                                class="form-control form-control-sm bg-dark text-white border-secondary"
                                value="<?= $dataInicio ?>" onchange="this.form.submit()">
                            <input type="date" name="data_fim"
                                class="form-control form-control-sm bg-dark text-white border-secondary"
                                value="<?= $dataFim ?>" onchange="this.form.submit()">
                        </form>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="ps-4">Data</th>
                                        <th>Item</th>
                                        <th>Operação</th>
                                        <th>Qtd</th>
                                        <th>Valor</th>
                                        <th class="pe-4">Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($registros)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                                Nenhum registro encontrado no período.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($registros as $reg):
                                            $badgeClass = 'badge-producao';
                                            if ($reg['tipo_operacao'] == 'Serviço' || $reg['tipo_operacao'] == 'Prestação de Serviço')
                                                $badgeClass = 'badge-servico';
                                            ?>
                                            <tr>
                                                <td class="ps-4 text-primary small">
                                                    <?= date('d/m/Y', strtotime($reg['data_operacao'])) ?>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-secondary">
                                                        <?= $reg['item_nome'] ?>
                                                    </div>
                                                    <small class="text-primary">
                                                        <?= $reg['categoria'] ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $badgeClass ?> text-primary">
                                                        <?= $reg['tipo_operacao'] ?>
                                                    </span>
                                                </td>
                                                <td class="fw-bold text-primary">
                                                    <?= number_format($reg['quantidade'], 2, ',', '.') ?>
                                                    <small class="text-danger fw-normal">
                                                        <?= $reg['unidade'] ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if ($reg['valor_total'] > 0): ?>
                                                        R$
                                                        <?= number_format($reg['valor_total'], 2, ',', '.') ?>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td class="pe-4">
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-outline-warning btn-sm"
                                                            onclick="editarRegistro(<?= $reg['id'] ?>)" title="Editar">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger btn-sm"
                                                            onclick="excluirRegistro(<?= $reg['id'] ?>)" title="Excluir">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <!-- Abas para módulos -->
        <div class="row mt-4">
            <div class="col-12">
                <ul class="nav nav-tabs" id="moduloTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="controle-tab" data-bs-toggle="tab"
                            data-bs-target="#controleTab">
                            <i class="bi bi-calendar-check me-1"></i> Controle Diário
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="orcamento-tab" data-bs-toggle="tab" data-bs-target="#orcamentoTab">
                            <i class="bi bi-file-earmark-text me-1"></i> Orçamentos
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="consumo-tab" data-bs-toggle="tab" data-bs-target="#consumoTab">
                            <i class="bi bi-calculator me-1"></i> Consumo por Peça
                        </button>
                    </li>
                </ul>

                <div class="tab-content mt-3" id="moduloTabsContent">
                    <!-- ABA: Controle Diário Melhorado -->
                    <div class="tab-pane fade show active" id="controleTab">
                        <div class="card border-primary">
                            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <h5 class="mb-0 fw-bold"><i class="bi bi-clipboard-data pe-2"></i> Controle de Produção
                                    Diária</h5>
                                <div class="d-flex gap-2 flex-wrap">
                                    <input type="date" id="dataControle"
                                        class="form-control form-control-sm bg-dark text-white border-secondary"
                                        value="<?= date('Y-m-d') ?>" onchange="carregarControleDiario()">
                                    <select id="turnoControle"
                                        class="form-select form-select-sm bg-dark text-white border-secondary"
                                        onchange="carregarControleDiario()">
                                        <option value="Manhã">Manhã</option>
                                        <option value="Tarde">Tarde</option>
                                        <option value="Noite">Noite</option>
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                        data-bs-target="#modalNovoItem">
                                        <i class="bi bi-plus-lg"></i> Novo Produto
                                    </button>
                                    <button class="btn btn-sm btn-primary" onclick="salvarControleDiario()">
                                        <i class="bi bi-save"></i> Salvar
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Resumo do Dia -->
                                <div id="resumoDiario" class="row g-3 mb-4">
                                    <div class="col-md-3">
                                        <div class="p-3 bg-success bg-opacity-10 rounded border border-success">
                                            <div class="small text-success">PRODUZIDO</div>
                                            <div class="h4 mb-0" id="totalProduzido">0</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3 bg-danger bg-opacity-10 rounded border border-danger">
                                            <div class="small text-danger">REFUGO</div>
                                            <div class="h4 mb-0" id="totalRefugo">0</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3 bg-info bg-opacity-10 rounded border border-info">
                                            <div class="small text-info">LÍQUIDO</div>
                                            <div class="h4 mb-0" id="totalLiquido">0</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3 bg-warning bg-opacity-10 rounded border border-warning">
                                            <div class="small text-warning">EFICIÊNCIA</div>
                                            <div class="h4 mb-0" id="eficienciaGeral">0%</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Grade de Itens -->
                                <div id="gradeControleDiario" class="row g-3">
                                    <?php
                                    $artefatos = array_filter($itens, fn($i) => $i['categoria'] === 'Artefato');
                                    foreach ($artefatos as $art):
                                        ?>
                                        <div class="col-md-6 col-lg-4">
                                            <div class="p-3 rounded bg-dark bg-opacity-25 border border-secondary h-100">
                                                <label
                                                    class="form-label small text-secondary fw-bold"><?= $art['nome'] ?></label>
                                                <div class="row g-2">
                                                    <div class="col-4">
                                                        <label class="form-label small text-muted">Planej.</label>
                                                        <input type="number"
                                                            class="form-control form-control-sm bg-dark text-white border-secondary controle-planejada"
                                                            data-item-id="<?= $art['id'] ?>" step="1" min="0"
                                                            placeholder="0">
                                                    </div>
                                                    <div class="col-4">
                                                        <label class="form-label small text-muted">Produz.</label>
                                                        <input type="number"
                                                            class="form-control form-control-sm bg-dark text-white border-secondary controle-produzida"
                                                            data-item-id="<?= $art['id'] ?>" step="1" min="0"
                                                            placeholder="0">
                                                    </div>
                                                    <div class="col-4">
                                                        <label class="form-label small text-muted">Refugo</label>
                                                        <input type="number"
                                                            class="form-control form-control-sm bg-dark text-white border-secondary controle-refugo"
                                                            data-item-id="<?= $art['id'] ?>" step="1" min="0"
                                                            placeholder="0">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ABA: Orçamentos -->
                    <div class="tab-pane fade" id="orcamentoTab">
                        <div class="card border-warning">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text pe-2"></i> Orçamentos</h5>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                    data-bs-target="#modalNovoOrcamento">
                                    <i class="bi bi-plus-lg"></i> Novo Orçamento
                                </button>
                            </div>
                            <div class="card-body">
                                <!-- Estatísticas de Orçamentos -->
                                <div id="statsOrcamentos" class="row g-3 mb-4">
                                    <div class="col-md-3">
                                        <div class="p-3 bg-secondary bg-opacity-10 rounded border border-secondary">
                                            <div class="small text-secondary">TOTAL</div>
                                            <div class="h4 mb-0" id="orcTotal">0</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3 bg-warning bg-opacity-10 rounded border border-warning">
                                            <div class="small text-warning">PENDENTES</div>
                                            <div class="h4 mb-0" id="orcPendentes">0</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3 bg-success bg-opacity-10 rounded border border-success">
                                            <div class="small text-success">APROVADOS</div>
                                            <div class="h4 mb-0" id="orcAprovados">0</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3 bg-primary bg-opacity-10 rounded border border-primary">
                                            <div class="small text-primary">VALOR APROVADO</div>
                                            <div class="h5 mb-0" id="orcValorAprovado">R$ 0,00</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Lista de Orçamentos -->
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Número</th>
                                                <th>Cliente</th>
                                                <th>Data</th>
                                                <th>Validade</th>
                                                <th>Valor</th>
                                                <th>Status</th>
                                                <th style="width: 120px;">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody id="listaOrcamentos">
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">Carregando...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ABA: Consumo por Peça -->
                    <div class="tab-pane fade" id="consumoTab">
                        <div class="card border-info">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold"><i class="bi bi-calculator pe-2"></i> Cálculo de Consumo por
                                    Peça</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-4">
                                    <!-- Seleção e Cálculo -->
                                    <div class="col-lg-5">
                                        <div class="p-3 bg-dark bg-opacity-25 rounded border border-secondary">
                                            <h6 class="text-info mb-3">Calculadora de Consumo</h6>
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <label class="form-label small mb-0">Selecione a
                                                        Peça/Artefato</label>
                                                    <button type="button"
                                                        class="btn btn-sm btn-link p-0 text-info text-decoration-none"
                                                        data-bs-toggle="modal" data-bs-target="#modalNovoItem">
                                                        <i class="bi bi-plus-circle"></i> Novo Item
                                                    </button>
                                                </div>
                                                <select id="pecaCalculo"
                                                    class="form-select bg-dark text-white border-secondary"
                                                    onchange="carregarComposicao()">
                                                    <option value="">Selecione...</option>
                                                    <?php foreach ($artefatos as $art): ?>
                                                        <option value="<?= $art['id'] ?>"><?= $art['nome'] ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small">Quantidade a Produzir</label>
                                                <input type="number" id="qtdCalculo"
                                                    class="form-control bg-dark text-white border-secondary" value="100"
                                                    min="1" onchange="calcularConsumo()">
                                            </div>
                                            <button class="btn btn-info w-100" onclick="calcularConsumo()">
                                                <i class="bi bi-calculator me-1"></i> Calcular Consumo
                                            </button>

                                            <!-- Resultado do Cálculo -->
                                            <div id="resultadoCalculo" class="mt-4 d-none">
                                                <h6 class="text-warning border-bottom border-warning pb-2">Resultado
                                                </h6>
                                                <div id="listaMateriais"></div>
                                                <div
                                                    class="mt-3 p-3 bg-warning bg-opacity-10 rounded border border-warning">
                                                    <div class="d-flex justify-content-between">
                                                        <span class="text-warning fw-bold">CUSTO TOTAL:</span>
                                                        <span class="h5 mb-0 text-warning" id="custoTotalCalculo">R$
                                                            0,00</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Composição da Peça -->
                                    <div class="col-lg-7">
                                        <div class="p-3 bg-dark bg-opacity-25 rounded border border-secondary">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="text-info mb-0">Composição de Materiais</h6>
                                                <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal"
                                                    data-bs-target="#modalAdicionarMaterial">
                                                    <i class="bi bi-plus-lg"></i> Adicionar Material
                                                </button>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm align-middle mb-0">
                                                    <thead class="table-dark">
                                                        <tr>
                                                            <th>Material</th>
                                                            <th>Consumo/Un</th>
                                                            <th>Perda %</th>
                                                            <th>Cons. Bruto</th>
                                                            <th>Custo/Un</th>
                                                            <th></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tabelaComposicao">
                                                        <tr>
                                                            <td colspan="6" class="text-center py-3 text-muted">
                                                                Selecione uma peça</td>
                                                        </tr>
                                                    </tbody>
                                                    <tfoot id="footerComposicao" class="d-none">
                                                        <tr class="table-warning">
                                                            <td colspan="4" class="fw-bold text-end">CUSTO TOTAL POR
                                                                UNIDADE:</td>
                                                            <td colspan="2" class="fw-bold" id="custoUnitarioPeca">R$
                                                                0,00</td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seção de Relatórios Interativos -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-graph-up-arrow pe-2"></i> Gestão Estratégica e
                            Relatórios</h5>
                        <div class="d-flex gap-2">
                            <ul class="nav nav-pills nav-pills-sm" id="reportTabs" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active py-1" id="weekly-tab" data-bs-toggle="pill"
                                        data-bs-target="#weekly-report">Semanal</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link py-1" id="monthly-tab" data-bs-toggle="pill"
                                        data-bs-target="#monthly-report">Mensal</button>
                                </li>
                            </ul>
                            <button class="btn btn-sm btn-outline-primary" onclick="carregarRelatorios()">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <!-- Área de Gráficos (Abas) -->
                            <div class="col-lg-8">
                                <div class="tab-content" id="reportTabsContent">
                                    <!-- Visão Semanal -->
                                    <div class="tab-pane fade show active" id="weekly-report">
                                        <div class="report-card p-4 rounded-3">
                                            <h6 class="text-secondary fw-bold mb-4">Produção e Vendas Semanais</h6>
                                            <div class="chart-container">
                                                <canvas id="chartSemanal"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Visão Mensal -->
                                    <div class="tab-pane fade" id="monthly-report">
                                        <div class="report-card p-4 rounded-3">
                                            <h6 class="text-secondary fw-bold mb-4">Comparativo de Operações Mensais
                                            </h6>
                                            <div class="chart-container">
                                                <canvas id="chartMensal"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Distribuição por Categoria e Gestão -->
                            <div class="col-lg-4">
                                <div class="report-card p-4 rounded-3 h-100">
                                    <h6 class="text-secondary fw-bold mb-4">Eficiência por Categoria</h6>
                                    <div id="statsCategorias" class="d-flex flex-column gap-3">
                                        <!-- Preenchido via JS -->
                                        <div class="text-center py-5">
                                            <div class="spinner-border text-primary" role="status"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Confirmação de Revisão -->
    <div class="modal fade" id="modalConfirmacao" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white border-warning">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-warning"><i class="bi bi-shield-check me-2"></i> Revisão de Dados</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Por favor, revise os dados antes de confirmar o lançamento:</p>
                    <div class="p-3 bg-secondary bg-opacity-25 rounded border border-secondary mb-3">
                        <div class="row mb-2">
                            <div class="col-4 text-muted small">ITEM:</div>
                            <div class="col-8 fw-bold" id="conf-item"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 text-muted small">TIPO:</div>
                            <div class="col-8" id="conf-tipo"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 text-muted small">QTD:</div>
                            <div class="col-8 fw-bold" id="conf-qtd"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 text-muted small">VALOR:</div>
                            <div class="col-8" id="conf-valor"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 text-muted small">DATA:</div>
                            <div class="col-8" id="conf-data"></div>
                        </div>
                    </div>
                    <p class="small text-muted mb-0">Tudo correto? Se sim, clique em "Confirmar e Gravar".</p>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Voltar e
                        Corrigir</button>
                    <button type="button" class="btn btn-warning fw-bold px-4" id="btnConfirmarSalvar">
                        <i class="bi bi-check-all me-2"></i> Confirmar e Gravar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edição -->
    <div class="modal fade" id="modalEdicao" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-white border-primary">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i> Editar Registro</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEdicao" action="producao.php" method="POST">
                    <input type="hidden" name="id" id="edit-id">
                    <input type="hidden" name="registrar" value="1">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Item / Produto</label>
                            <select name="item_id" id="edit-item_id"
                                class="form-select bg-dark text-white border-secondary" required>
                                <?php foreach ($itens as $item): ?>
                                    <option value="<?= $item['id'] ?>"><?= $item['nome'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo de Operação</label>
                            <select name="tipo_operacao" id="edit-tipo_operacao"
                                class="form-select bg-dark text-white border-secondary" required>
                                <option value="Produção">Produção</option>
                                <option value="Venda">Venda</option>
                                <option value="Consumo">Consumo</option>
                                <option value="Prestação de Serviço">Prestação de Serviço</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Quantidade</label>
                                <input type="number" step="0.01" name="quantidade" id="edit-quantidade"
                                    class="form-control bg-dark text-white border-secondary" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data</label>
                                <input type="date" name="data_operacao" id="edit-data_operacao"
                                    class="form-control bg-dark text-white border-secondary" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Valor Total</label>
                            <input type="number" step="0.01" name="valor_total" id="edit-valor_total"
                                class="form-control bg-dark text-white border-secondary">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Observações</label>
                            <textarea name="observacoes" id="edit-observacoes"
                                class="form-control bg-dark text-white border-secondary" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="registrar" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer no-print">
        <p>©
            <?= date('Y') ?> BHSAC - BH Service e Artefatos de Concreto
        </p>
        <p>Desenvolvido por <a href="https://github.com/losmanim" target="_blank">Luiz Antonio</a></p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Gerenciar Modal de Confirmação para Novo Registro
        const formNovo = document.querySelector('form[action="producao.php"]');
        const modalConfirmacao = new bootstrap.Modal(document.getElementById('modalConfirmacao'));
        const btnConfirmarSalvar = document.getElementById('btnConfirmarSalvar');

        formNovo.addEventListener('submit', function (e) {
            if (!this.dataset.confirmed) {
                e.preventDefault();

                // Preencher dados no modal de revisão
                const selectItem = this.querySelector('select[name="item_id"]');
                document.getElementById('conf-item').innerText = selectItem.options[selectItem.selectedIndex].text;
                document.getElementById('conf-tipo').innerText = this.querySelector('select[name="tipo_operacao"]').value;
                document.getElementById('conf-qtd').innerText = this.querySelector('input[name="quantidade"]').value;
                document.getElementById('conf-valor').innerText = 'R$ ' + (this.querySelector('input[name="valor_total"]').value || '0,00');
                document.getElementById('conf-data').innerText = this.querySelector('input[name="data_operacao"]').value.split('-').reverse().join('/');

                modalConfirmacao.show();
            }
        });

        btnConfirmarSalvar.addEventListener('click', () => {
            formNovo.dataset.confirmed = 'true';
            formNovo.submit();
        });

        // Função para Editar Registro
        async function editarRegistro(id) {
            try {
                const response = await fetch(`api/producao.php?get_registro=1&id=${id}`);
                const data = await response.json();

                if (data.success) {
                    const reg = data.registro;
                    document.getElementById('edit-id').value = reg.id;
                    document.getElementById('edit-item_id').value = reg.item_id;
                    document.getElementById('edit-tipo_operacao').value = reg.tipo_operacao;
                    document.getElementById('edit-quantidade').value = reg.quantidade;
                    document.getElementById('edit-data_operacao').value = reg.data_operacao;
                    document.getElementById('edit-valor_total').value = reg.valor_total;
                    document.getElementById('edit-observacoes').value = reg.observacoes || '';

                    new bootstrap.Modal(document.getElementById('modalEdicao')).show();
                }
            } catch (error) {
                alert('Erro ao carregar dados do registro.');
            }
        }

        // Função para Excluir Registro
        async function excluirRegistro(id) {
            if (confirm('Tem certeza que deseja excluir este registro permanentemente?')) {
                try {
                    const response = await fetch('api/producao.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: id, acao: 'excluir' })
                    });
                    const data = await response.json();
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erro ao excluir: ' + data.error);
                    }
                } catch (error) {
                    alert('Erro na comunicação com o servidor.');
                }
            }
        }

        // --- Lógica de Relatórios Interativos ---
        let meuGraficoSemanal = null;

        let meuGraficoMensal = null;

        async function carregarRelatorios() {
            const dataInicio = document.querySelector('input[name="data_inicio"]').value;
            const dataFim = document.querySelector('input[name="data_fim"]').value;

            try {
                // Carregar stats por categoria
                const resCat = await fetch(`api/producao.php?stats_categoria=1&data_inicio=${dataInicio}&data_fim=${dataFim}`);
                const dataCat = await resCat.json();
                if (dataCat.success) renderizarStatsCategorias(dataCat.stats);

                // Carregar stats semanais
                const resSem = await fetch(`api/producao.php?stats_semanais=1&data_inicio=${dataInicio}&data_fim=${dataFim}`);
                const dataSem = await resSem.json();
                if (dataSem.success) atualizarGraficoSemanal(dataSem.stats);

                // Carregar stats mensais
                const resMen = await fetch(`api/producao.php?stats_mensais=1&data_inicio=${dataInicio}&data_fim=${dataFim}`);
                const dataMen = await resMen.json();
                if (dataMen.success) atualizarGraficoMensal(dataMen.stats);

            } catch (error) {
                console.error('Erro ao carregar relatórios:', error);
            }
        }

        function renderizarStatsCategorias(stats) {
            const container = document.getElementById('statsCategorias');
            if (stats.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-5">Nenhum dado no período.</div>';
                return;
            }

            container.innerHTML = stats.map(s => `
                <div class="p-3 rounded bg-dark bg-opacity-50 border-start border-3 ${getBorderColor(s.tipo_operacao)}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-info fw-bold text-uppercase">${s.categoria}</span>
                        <span class="badge bg-secondary opacity-50 small">${s.total_registros} reg</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold text-success">${s.tipo_operacao}</span>
                        <span class="text-primary fw-bold">${parseFloat(s.total_qtd).toLocaleString('pt-BR')} <small>un/qtd</small></span>
                    </div>
                    ${s.total_valor > 0 ? `
                    <div class="text-end small text-success mt-1">
                        R$ ${parseFloat(s.total_valor).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                    </div>` : ''}
                </div>
            `).join('');
        }

        function getBorderColor(tipo) {
            if (tipo === 'Produção') return 'border-success';
            if (tipo === 'Serviço' || tipo === 'Prestação de Serviço') return 'border-warning';
            return 'border-secondary';
        }

        function atualizarGraficoSemanal(stats) {
            const ctx = document.getElementById('chartSemanal').getContext('2d');
            const semanas = [...new Set(stats.map(s => `Sem ${s.semana.toString().substr(4)}`))];
            const tipos = [...new Set(stats.map(s => s.tipo_operacao))];

            const datasets = tipos.map(tipo => {
                const cor = getTipoColor(tipo);
                return {
                    label: tipo,
                    data: semanas.map(sem => {
                        const registro = stats.find(s => `Sem ${s.semana.toString().substr(4)}` === sem && s.tipo_operacao === tipo);
                        return registro ? parseFloat(registro.total_qtd) : 0;
                    }),
                    borderColor: cor,
                    backgroundColor: cor + '20',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                };
            });

            if (meuGraficoSemanal) meuGraficoSemanal.destroy();
            meuGraficoSemanal = new Chart(ctx, {
                type: 'line',
                data: { labels: semanas, datasets: datasets },
                options: getChartOptions()
            });
        }

        function atualizarGraficoMensal(stats) {
            const ctx = document.getElementById('chartMensal').getContext('2d');
            const meses = [...new Set(stats.map(s => s.mes))];
            const tipos = [...new Set(stats.map(s => s.tipo_operacao))];

            const datasets = tipos.map(tipo => {
                const cor = getTipoColor(tipo);
                return {
                    label: tipo,
                    data: meses.map(mes => {
                        const registro = stats.find(s => s.mes === mes && s.tipo_operacao === tipo);
                        return registro ? parseFloat(registro.total_qtd) : 0;
                    }),
                    backgroundColor: cor
                };
            });

            if (meuGraficoMensal) meuGraficoMensal.destroy();
            meuGraficoMensal = new Chart(ctx, {
                type: 'bar',
                data: { labels: meses.map(m => m.split('-').reverse().join('/')), datasets: datasets },
                options: getChartOptions()
            });
        }

        function getChartOptions() {
            return {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: '#cbd5e1', font: { size: 10 } } }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: '#cbd5e1', font: { size: 10 } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#cbd5e1', font: { size: 10 } }
                    }
                }
            };
        }

        function getTipoColor(tipo) {
            const colors = {
                'Produção': '#10b981',
                'Serviço': '#f59e0b',
                'Prestação de Serviço': '#f59e0b'
            };
            return colors[tipo] || '#94a3b8';
        }

        // Redimensionar gráficos ao trocar de aba (correção comum do Chart.js)
        document.querySelectorAll('button[data-bs-toggle="pill"]').forEach(btn => {
            btn.addEventListener('shown.bs.tab', () => {
                if (meuGraficoSemanal) meuGraficoSemanal.resize();
                if (meuGraficoMensal) meuGraficoMensal.resize();
            });
        });

        // --- Lógica de Controle Diário ---
        async function carregarProducaoDiaria() {
            const dataSel = document.getElementById('dataControle').value;
            const inputs = document.querySelectorAll('.item-diario-input');

            // Limpar inputs
            inputs.forEach(input => input.value = '');

            if (!dataSel) return;

            try {
                const response = await fetch(`api/producao.php?get_diario=1&data=${dataSel}`);
                const data = await response.json();

                if (data.success) {
                    data.registros.forEach(reg => {
                        const input = document.querySelector(`.item-diario-input[data-item-id="${reg.item_id}"]`);
                        if (input) input.value = reg.quantidade;
                    });
                }
            } catch (error) {
                console.error('Erro ao carregar produção diária:', error);
            }
        }

        async function salvarProducaoDiaria() {
            const dataSel = document.getElementById('dataControle').value;
            const inputs = document.querySelectorAll('.item-diario-input');
            const dados = [];

            inputs.forEach(input => {
                const qtd = parseFloat(input.value);
                if (qtd > 0) {
                    dados.push({
                        item_id: input.dataset.itemId,
                        quantidade: qtd
                    });
                }
            });

            if (!dataSel) {
                alert('Por favor, selecione uma data.');
                return;
            }

            if (dados.length === 0 && !confirm('Nenhuma quantidade preenchida. Isso removerá todos os registros de produção desta data. Deseja continuar?')) {
                return;
            }

            try {
                const response = await fetch('api/producao.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        acao: 'save_diario',
                        data: dataSel,
                        dados: dados
                    })
                });
                const data = await response.json();
                if (data.success) {
                    alert('Produção diária salva com sucesso!');
                    location.reload();
                } else {
                    alert('Erro ao salvar: ' + data.error);
                }
            } catch (error) {
                alert('Erro na comunicação com o servidor.');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            carregarRelatorios();
            carregarControleDiario();
            carregarOrcamentos();
        });

        async function cadastrarNovoItem() {
            const form = document.getElementById('formNovoItem');
            const formData = new FormData(form);
            const dados = {
                acao: 'save_item',
                nome: formData.get('nome'),
                categoria: formData.get('categoria'),
                unidade: formData.get('unidade'),
                preco_referencia: formData.get('preco_referencia')
            };

            try {
                const response = await fetch('api/producao.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dados)
                });
                const data = await response.json();
                if (data.success) {
                    alert('Item cadastrado com sucesso!');
                    location.reload();
                } else {
                    alert('Erro ao cadastrar: ' + data.error);
                }
            } catch (error) {
                alert('Erro na comunicação com o servidor.');
            }
        }

        // ==========================================
        // CONTROLE DIÁRIO MELHORADO
        // ==========================================
        async function carregarControleDiario() {
            const data = document.getElementById('dataControle').value;
            const turno = document.getElementById('turnoControle').value;

            // Limpar inputs
            document.querySelectorAll('.controle-planejada, .controle-produzida, .controle-refugo').forEach(i => i.value = '');

            try {
                const response = await fetch(`api/producao.php?get_controle_diario=1&data=${data}&turno=${encodeURIComponent(turno)}`);
                const result = await response.json();

                if (result.success) {
                    // Preencher inputs
                    result.registros.forEach(reg => {
                        const planejada = document.querySelector(`.controle-planejada[data-item-id="${reg.item_id}"]`);
                        const produzida = document.querySelector(`.controle-produzida[data-item-id="${reg.item_id}"]`);
                        const refugo = document.querySelector(`.controle-refugo[data-item-id="${reg.item_id}"]`);
                        if (planejada) planejada.value = reg.quantidade_planejada || '';
                        if (produzida) produzida.value = reg.quantidade_produzida || '';
                        if (refugo) refugo.value = reg.quantidade_refugo || '';
                    });

                    // Atualizar resumo
                    const resumoTurno = result.resumo.find(r => r.turno === turno) || {};
                    document.getElementById('totalProduzido').textContent = formatarNumero(resumoTurno.total_produzido || 0);
                    document.getElementById('totalRefugo').textContent = formatarNumero(resumoTurno.total_refugo || 0);
                    document.getElementById('totalLiquido').textContent = formatarNumero(resumoTurno.total_liquido || 0);
                    document.getElementById('eficienciaGeral').textContent = (resumoTurno.eficiencia_geral || 0) + '%';
                }
            } catch (error) {
                console.error('Erro ao carregar controle diário:', error);
            }
        }

        async function salvarControleDiario() {
            const data = document.getElementById('dataControle').value;
            const turno = document.getElementById('turnoControle').value;
            const registros = [];

            document.querySelectorAll('.controle-produzida').forEach(input => {
                const itemId = input.dataset.itemId;
                const planejada = document.querySelector(`.controle-planejada[data-item-id="${itemId}"]`);
                const refugo = document.querySelector(`.controle-refugo[data-item-id="${itemId}"]`);

                registros.push({
                    item_id: itemId,
                    quantidade_planejada: parseFloat(planejada?.value) || 0,
                    quantidade_produzida: parseFloat(input.value) || 0,
                    quantidade_refugo: parseFloat(refugo?.value) || 0
                });
            });

            try {
                const response = await fetch('api/producao.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        acao: 'save_controle_diario',
                        data: data,
                        turno: turno,
                        registros: registros
                    })
                });
                const result = await response.json();
                if (result.success) {
                    alert('Controle diário salvo com sucesso!');
                    carregarControleDiario();
                } else {
                    alert('Erro: ' + result.error);
                }
            } catch (error) {
                alert('Erro na comunicação com o servidor.');
            }
        }

        function formatarNumero(num) {
            return parseFloat(num).toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        }

        function formatarMoeda(valor) {
            return parseFloat(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }

        // ==========================================
        // ORÇAMENTOS
        // ==========================================
        async function carregarOrcamentos() {
            try {
                // Estatísticas
                const statsResp = await fetch('api/orcamento.php?estatisticas=1');
                const statsData = await statsResp.json();
                if (statsData.success && statsData.estatisticas) {
                    const s = statsData.estatisticas;
                    document.getElementById('orcTotal').textContent = s.total || 0;
                    document.getElementById('orcPendentes').textContent = s.pendentes || 0;
                    document.getElementById('orcAprovados').textContent = s.aprovados || 0;
                    document.getElementById('orcValorAprovado').textContent = formatarMoeda(s.valor_aprovado || 0);
                }

                // Lista
                const listResp = await fetch('api/orcamento.php?listar=1&limite=20');
                const listData = await listResp.json();
                const tbody = document.getElementById('listaOrcamentos');

                if (listData.success && listData.orcamentos.length > 0) {
                    tbody.innerHTML = listData.orcamentos.map(orc => {
                        const statusClass = {
                            'Pendente': 'bg-warning text-dark',
                            'Aprovado': 'bg-success',
                            'Recusado': 'bg-danger',
                            'Expirado': 'bg-secondary'
                        }[orc.status] || 'bg-secondary';

                        return `<tr>
                            <td class="fw-bold text-warning">${orc.numero}</td>
                            <td>${orc.cliente_nome}</td>
                            <td class="small">${formatarData(orc.data_emissao)}</td>
                            <td class="small">${formatarData(orc.data_validade)}</td>
                            <td class="fw-bold">${formatarMoeda(orc.valor_total)}</td>
                            <td><span class="badge ${statusClass}">${orc.status}</span></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="imprimir_orcamento.php?id=${orc.id}" target="_blank" class="btn btn-sm btn-outline-info" title="Imprimir Orçamento">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-dark">
                                            <li><a class="dropdown-item" href="#" onclick="atualizarStatusOrcamento(${orc.id}, 'Aprovado')"><i class="bi bi-check-lg text-success me-2"></i>Aprovar</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="atualizarStatusOrcamento(${orc.id}, 'Recusado')"><i class="bi bi-x-lg text-danger me-2"></i>Recusar</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="excluirOrcamento(${orc.id})"><i class="bi bi-trash me-2"></i>Excluir</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>`;
                    }).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">Nenhum orçamento encontrado</td></tr>';
                }
            } catch (error) {
                console.error('Erro ao carregar orçamentos:', error);
            }
        }

        function formatarData(dataStr) {
            if (!dataStr) return '-';
            const [ano, mes, dia] = dataStr.split('-');
            return `${dia}/${mes}/${ano}`;
        }

        function adicionarItemOrcamento() {
            const container = document.getElementById('itensOrcamento');
            const novoItem = document.createElement('div');
            novoItem.className = 'item-orcamento row g-2 mb-2 align-items-end';
            novoItem.innerHTML = `
                <div class="col-md-4">
                    <input type="text" class="form-control form-control-sm bg-dark text-white border-secondary item-descricao" placeholder="Descrição" required>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control form-control-sm bg-dark text-white border-secondary item-quantidade" value="1" min="0.01" step="0.01" onchange="calcularTotaisOrcamento()">
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control form-control-sm bg-dark text-white border-secondary item-unidade" value="un">
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control form-control-sm bg-dark text-white border-secondary item-valor" step="0.01" min="0" onchange="calcularTotaisOrcamento()">
                </div>
                <div class="col-md-1">
                    <input type="text" class="form-control form-control-sm bg-secondary text-white border-secondary item-total" readonly>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.item-orcamento').remove(); calcularTotaisOrcamento();">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(novoItem);
        }

        function calcularTotaisOrcamento() {
            let subtotal = 0;
            document.querySelectorAll('.item-orcamento').forEach(item => {
                const qtd = parseFloat(item.querySelector('.item-quantidade')?.value) || 0;
                const valor = parseFloat(item.querySelector('.item-valor')?.value) || 0;
                const total = qtd * valor;
                subtotal += total;
                const totalInput = item.querySelector('.item-total');
                if (totalInput) totalInput.value = formatarMoeda(total);
            });

            const desconto = parseFloat(document.getElementById('orc_desconto').value) || 0;
            const valorTotal = subtotal - desconto;
            document.getElementById('orc_valor_total').value = formatarMoeda(valorTotal);
        }

        async function salvarOrcamento() {
            const itens = [];
            document.querySelectorAll('.item-orcamento').forEach(item => {
                const descricao = item.querySelector('.item-descricao')?.value;
                const quantidade = parseFloat(item.querySelector('.item-quantidade')?.value) || 0;
                const unidade = item.querySelector('.item-unidade')?.value || 'un';
                const valor_unitario = parseFloat(item.querySelector('.item-valor')?.value) || 0;

                if (descricao && quantidade > 0) {
                    itens.push({ descricao, quantidade, unidade, valor_unitario });
                }
            });

            if (itens.length === 0) {
                alert('Adicione pelo menos um item ao orçamento.');
                return;
            }

            const clienteNome = document.getElementById('orc_cliente_nome').value;
            if (!clienteNome) {
                alert('Informe o nome do cliente.');
                return;
            }

            const subtotal = itens.reduce((sum, i) => sum + (i.quantidade * i.valor_unitario), 0);
            const desconto = parseFloat(document.getElementById('orc_desconto').value) || 0;

            const dados = {
                acao: 'criar',
                cliente_nome: clienteNome,
                cliente_documento: document.getElementById('orc_cliente_documento').value,
                cliente_contato: document.getElementById('orc_cliente_contato').value,
                cliente_endereco: document.getElementById('orc_cliente_endereco').value,
                data_validade: document.getElementById('orc_data_validade').value,
                condicoes_pagamento: document.getElementById('orc_condicoes').value,
                observacoes: document.getElementById('orc_observacoes').value,
                subtotal: subtotal,
                desconto: desconto,
                valor_total: subtotal - desconto,
                itens: itens
            };

            try {
                const response = await fetch('api/orcamento.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dados)
                });
                const result = await response.json();
                if (result.success) {
                    alert('Orçamento ' + result.numero + ' criado com sucesso!');
                    bootstrap.Modal.getInstance(document.getElementById('modalNovoOrcamento')).hide();
                    document.getElementById('formNovoOrcamento').reset();
                    carregarOrcamentos();
                } else {
                    alert('Erro: ' + result.error);
                }
            } catch (error) {
                alert('Erro na comunicação com o servidor.');
            }
        }

        async function atualizarStatusOrcamento(id, status) {
            if (!confirm(`Deseja alterar o status para "${status}"?`)) return;

            try {
                const response = await fetch('api/orcamento.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ acao: 'atualizar_status', id, status })
                });
                const result = await response.json();
                if (result.success) {
                    carregarOrcamentos();
                } else {
                    alert('Erro: ' + result.error);
                }
            } catch (error) {
                alert('Erro na comunicação.');
            }
        }

        async function excluirOrcamento(id) {
            if (!confirm('Deseja realmente excluir este orçamento?')) return;

            try {
                const response = await fetch('api/orcamento.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ acao: 'excluir', id })
                });
                const result = await response.json();
                if (result.success) {
                    carregarOrcamentos();
                } else {
                    alert('Erro: ' + result.error);
                }
            } catch (error) {
                alert('Erro na comunicação.');
            }
        }

        // ==========================================
        // CONSUMO POR PEÇA (BOM)
        // ==========================================
        async function carregarComposicao() {
            const pecaId = document.getElementById('pecaCalculo').value;
            const tbody = document.getElementById('tabelaComposicao');
            const footer = document.getElementById('footerComposicao');

            if (!pecaId) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-muted">Selecione uma peça</td></tr>';
                footer.classList.add('d-none');
                document.getElementById('resultadoCalculo').classList.add('d-none');
                return;
            }

            try {
                const response = await fetch(`api/consumo.php?composicao=1&peca_id=${pecaId}`);
                const result = await response.json();

                if (result.success) {
                    if (result.composicao.length > 0) {
                        tbody.innerHTML = result.composicao.map(mat => `
                            <tr>
                                <td>${mat.material_nome}</td>
                                <td>${parseFloat(mat.consumo_liquido).toFixed(4)} ${mat.material_unidade}</td>
                                <td>${parseFloat(mat.percentual_perda).toFixed(1)}%</td>
                                <td class="text-warning">${parseFloat(mat.consumo_bruto).toFixed(4)}</td>
                                <td>${formatarMoeda(mat.custo_material)}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-danger" onclick="removerMaterialComposicao(${mat.id})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `).join('');
                        footer.classList.remove('d-none');
                        document.getElementById('custoUnitarioPeca').textContent = formatarMoeda(result.custo?.custo_total || 0);
                    } else {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-muted">Nenhum material cadastrado para esta peça</td></tr>';
                        footer.classList.add('d-none');
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar composição:', error);
            }
        }

        async function calcularConsumo() {
            const pecaId = document.getElementById('pecaCalculo').value;
            const quantidade = parseFloat(document.getElementById('qtdCalculo').value) || 0;

            if (!pecaId || quantidade <= 0) {
                alert('Selecione uma peça e informe a quantidade.');
                return;
            }

            try {
                const response = await fetch(`api/consumo.php?calcular=1&peca_id=${pecaId}&quantidade=${quantidade}`);
                const result = await response.json();

                if (result.success && result.resultado) {
                    const container = document.getElementById('listaMateriais');
                    container.innerHTML = result.resultado.materiais.map(mat => `
                        <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                            <span>${mat.material_nome}</span>
                            <span class="text-info">${parseFloat(mat.consumo_total).toFixed(2)} ${mat.material_unidade}</span>
                        </div>
                    `).join('');

                    document.getElementById('custoTotalCalculo').textContent = formatarMoeda(result.resultado.custo_total);
                    document.getElementById('resultadoCalculo').classList.remove('d-none');
                }
            } catch (error) {
                alert('Erro ao calcular consumo.');
            }
        }

        async function adicionarMaterialComposicao() {
            const pecaId = document.getElementById('pecaCalculo').value;
            if (!pecaId) {
                alert('Selecione uma peça primeiro.');
                return;
            }

            const dados = {
                acao: 'adicionar_material',
                peca_id: pecaId,
                material_id: document.getElementById('mat_material_id').value,
                consumo_liquido: parseFloat(document.getElementById('mat_consumo_liquido').value) || 0,
                percentual_perda: parseFloat(document.getElementById('mat_percentual_perda').value) || 0,
                observacoes: document.getElementById('mat_observacoes').value
            };

            if (!dados.material_id || dados.consumo_liquido <= 0) {
                alert('Preencha todos os campos obrigatórios.');
                return;
            }

            try {
                const response = await fetch('api/consumo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dados)
                });
                const result = await response.json();
                if (result.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalAdicionarMaterial')).hide();
                    document.getElementById('formAdicionarMaterial').reset();
                    carregarComposicao();
                } else {
                    alert('Erro: ' + result.error);
                }
            } catch (error) {
                alert('Erro na comunicação.');
            }
        }

        async function removerMaterialComposicao(id) {
            if (!confirm('Remover este material da composição?')) return;

            try {
                const response = await fetch('api/consumo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ acao: 'remover_material', id })
                });
                const result = await response.json();
                if (result.success) {
                    carregarComposicao();
                } else {
                    alert('Erro: ' + result.error);
                }
            } catch (error) {
                alert('Erro na comunicação.');
            }
        }

        // Cadastro rápido de material
        function toggleQuickAddMaterial() {
            const form = document.getElementById('quickAddMaterialForm');
            form.classList.toggle('d-none');
            if (!form.classList.contains('d-none')) {
                document.getElementById('q_mat_nome').focus();
            }
        }

        async function salvarNovoMaterialRapido() {
            const nome = document.getElementById('q_mat_nome').value;
            const unidade = document.getElementById('q_mat_unidade').value;
            const preco = document.getElementById('q_mat_preco').value;

            if (!nome || !unidade) {
                alert('Nome e Unidade são obrigatórios.');
                return;
            }

            try {
                const response = await fetch('api/producao.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        'acao': 'save_item',
                        'nome': nome,
                        'categoria': 'Material',
                        'unidade': unidade,
                        'preco_referencia': preco
                    })
                });

                const result = await response.json();

                if (result.success) {
                    const select = document.getElementById('mat_material_id');
                    const option = new Option(`${nome} (${unidade})`, result.id);
                    option.dataset.preco = preco;
                    select.add(option);
                    select.value = result.id;

                    toggleQuickAddMaterial();
                    document.getElementById('q_mat_nome').value = '';
                    document.getElementById('q_mat_unidade').value = '';
                    document.getElementById('q_mat_preco').value = '';
                } else {
                    alert('Erro ao cadastrar material: ' + result.error);
                }
            } catch (error) {
                // Fallback for item registration if API is different
                console.error('Erro na API:', error);
                alert('Erro ao processar cadastro. Verifique a conexão.');
            }
        }
    </script>

    <!-- Modal Novo Item -->
    <div class="modal fade" id="modalNovoItem" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark border-secondary bg-opacity-95 shadow-lg"
                style="backdrop-filter: blur(10px);">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title fw-bold text-primary"><i class="bi bi-plus-lg me-2"></i> Cadastrar Novo
                        Produto/Serviço</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="formNovoItem">
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">NOME DO PRODUTO</label>
                            <input type="text" name="nome" class="form-control bg-dark text-white border-secondary"
                                placeholder="Ex: Bloco Vedação (14x19x39)" required>
                            <div class="form-text text-muted" style="font-size: 0.75rem;">
                                <i class="bi bi-info-circle me-1"></i> Use o padrão: <strong>Nome (Dimensões)</strong>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-secondary small fw-bold">CATEGORIA</label>
                                <select name="categoria" class="form-select bg-dark text-white border-secondary"
                                    required>
                                    <option value="Artefato">Artefato</option>
                                    <option value="Serviço">Serviço</option>
                                    <option value="Material">Material</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-secondary small fw-bold">UNIDADE</label>
                                <input type="text" name="unidade"
                                    class="form-control bg-dark text-white border-secondary" placeholder="Ex: un, h, m3"
                                    required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">PREÇO DE REFERÊNCIA
                                (OPCIONAL)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-secondary border-secondary text-white">R$</span>
                                <input type="number" name="preco_referencia" step="0.01"
                                    class="form-control bg-dark text-white border-secondary" placeholder="0,00">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary px-4" onclick="cadastrarNovoItem()">
                        <i class="bi bi-save me-1"></i> Cadastrar Item
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Novo Orçamento -->
    <div class="modal fade" id="modalNovoOrcamento" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content bg-dark border-warning bg-opacity-95 shadow-lg"
                style="backdrop-filter: blur(10px);">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title fw-bold text-warning"><i class="bi bi-file-earmark-text me-2"></i> Novo
                        Orçamento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="formNovoOrcamento">
                        <!-- Dados do Cliente -->
                        <h6 class="text-secondary border-bottom border-secondary pb-2 mb-3"><i
                                class="bi bi-person me-1"></i> Dados do Cliente</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-secondary small fw-bold">NOME / RAZÃO SOCIAL *</label>
                                <input type="text" name="cliente_nome" id="orc_cliente_nome"
                                    class="form-control bg-dark text-white border-secondary" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-secondary small fw-bold">CPF / CNPJ</label>
                                <input type="text" name="cliente_documento" id="orc_cliente_documento"
                                    class="form-control bg-dark text-white border-secondary">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-secondary small fw-bold">CONTATO</label>
                                <input type="text" name="cliente_contato" id="orc_cliente_contato"
                                    class="form-control bg-dark text-white border-secondary"
                                    placeholder="Telefone ou E-mail">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-secondary small fw-bold">VALIDADE</label>
                                <input type="date" name="data_validade" id="orc_data_validade"
                                    class="form-control bg-dark text-white border-secondary"
                                    value="<?= date('Y-m-d', strtotime('+15 days')) ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">ENDEREÇO</label>
                            <input type="text" name="cliente_endereco" id="orc_cliente_endereco"
                                class="form-control bg-dark text-white border-secondary">
                        </div>

                        <!-- Itens do Orçamento -->
                        <h6 class="text-secondary border-bottom border-secondary pb-2 mb-3 mt-4"><i
                                class="bi bi-list-check me-1"></i> Itens do Orçamento</h6>
                        <div id="itensOrcamento">
                            <div class="item-orcamento row g-2 mb-2 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Descrição</label>
                                    <input type="text"
                                        class="form-control form-control-sm bg-dark text-white border-secondary item-descricao"
                                        placeholder="Descrição do item" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">Qtd</label>
                                    <input type="number"
                                        class="form-control form-control-sm bg-dark text-white border-secondary item-quantidade"
                                        value="1" min="0.01" step="0.01" onchange="calcularTotaisOrcamento()">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">Unidade</label>
                                    <input type="text"
                                        class="form-control form-control-sm bg-dark text-white border-secondary item-unidade"
                                        value="un">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">Valor Un.</label>
                                    <input type="number"
                                        class="form-control form-control-sm bg-dark text-white border-secondary item-valor"
                                        step="0.01" min="0" onchange="calcularTotaisOrcamento()">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">Total</label>
                                    <input type="text"
                                        class="form-control form-control-sm bg-secondary text-white border-secondary item-total"
                                        readonly>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-warning mt-2"
                            onclick="adicionarItemOrcamento()">
                            <i class="bi bi-plus-lg"></i> Adicionar Item
                        </button>

                        <!-- Totais -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold">CONDIÇÕES DE PAGAMENTO</label>
                                <select name="condicoes_pagamento" id="orc_condicoes"
                                    class="form-select bg-dark text-white border-secondary">
                                    <option value="À Vista">À Vista</option>
                                    <option value="PIX">PIX</option>
                                    <option value="Boleto 7 dias">Boleto 7 dias</option>
                                    <option value="Boleto 14 dias">Boleto 14 dias</option>
                                    <option value="Boleto 28 dias">Boleto 28 dias</option>
                                    <option value="Cartão">Cartão</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold">DESCONTO</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-secondary border-secondary text-white">R$</span>
                                    <input type="number" name="desconto" id="orc_desconto" step="0.01"
                                        class="form-control bg-dark text-white border-secondary" value="0"
                                        onchange="calcularTotaisOrcamento()">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold">VALOR TOTAL</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-warning border-warning text-dark fw-bold">R$</span>
                                    <input type="text" id="orc_valor_total"
                                        class="form-control bg-warning text-dark border-warning fw-bold" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label text-secondary small fw-bold">OBSERVAÇÕES</label>
                            <textarea name="observacoes" id="orc_observacoes"
                                class="form-control bg-dark text-white border-secondary" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning px-4" onclick="salvarOrcamento()">
                        <i class="bi bi-save me-1"></i> Salvar Orçamento
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar Material à Composição -->
    <div class="modal fade" id="modalAdicionarMaterial" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark border-info bg-opacity-95 shadow-lg" style="backdrop-filter: blur(10px);">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title fw-bold text-info"><i class="bi bi-plus-lg me-2"></i> Adicionar Material à
                        Composição</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="formQuickMaterial">
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">MATERIAL / INSUMO</label>
                            <div class="input-group">
                                <select name="material_id" id="mat_material_id"
                                    class="form-select bg-dark text-white border-secondary" required>
                                    <option value="">Selecione...</option>
                                    <?php
                                    $materiais = array_filter($itens, fn($i) => $i['categoria'] === 'Material');
                                    foreach ($materiais as $mat):
                                        ?>
                                        <option value="<?= $mat['id'] ?>" data-preco="<?= $mat['preco_referencia'] ?>">
                                            <?= $mat['nome'] ?> (<?= $mat['unidade'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-info" onclick="toggleQuickAddMaterial()">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Mini Formulário para Novo Material (Escondido) -->
                        <div id="quickAddMaterialForm"
                            class="p-3 mb-3 bg-info bg-opacity-10 border border-info rounded d-none">
                            <h6 class="text-info mb-2 small fw-bold">CADASTRAR NOVO MATERIAL</h6>
                            <div class="mb-2">
                                <input type="text" id="q_mat_nome"
                                    class="form-control form-control-sm bg-dark text-white border-info"
                                    placeholder="Nome do Material">
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <input type="text" id="q_mat_unidade"
                                        class="form-control form-control-sm bg-dark text-white border-info"
                                        placeholder="Un: kg, m3, un">
                                </div>
                                <div class="col-6">
                                    <input type="number" id="q_mat_preco" step="0.01"
                                        class="form-control form-control-sm bg-dark text-white border-info"
                                        placeholder="Preço Ref.">
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-info w-100"
                                    onclick="salvarNovoMaterialRapido()">
                                    <i class="bi bi-save"></i> Salvar e Usar
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                    onclick="toggleQuickAddMaterial()">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-secondary small fw-bold">CONSUMO LÍQUIDO POR
                                    UNIDADE</label>
                                <input type="number" name="consumo_liquido" id="mat_consumo_liquido" step="0.0001"
                                    min="0" class="form-control bg-dark text-white border-secondary" required>
                                <div class="form-text text-muted small">Quantidade necessária para produzir 1 peça</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-secondary small fw-bold">MARGEM DE PERDA (%)</label>
                                <input type="number" name="percentual_perda" id="mat_percentual_perda" step="0.01"
                                    min="0" max="100" class="form-control bg-dark text-white border-secondary"
                                    value="5">
                                <div class="form-text text-muted small">Perda no processo produtivo</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">OBSERVAÇÕES</label>
                            <input type="text" name="observacoes" id="mat_observacoes"
                                class="form-control bg-dark text-white border-secondary">
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-info px-4" onclick="adicionarMaterialComposicao()">
                        <i class="bi bi-plus-lg me-1"></i> Adicionar
                    </button>
                </div>
            </div>
        </div>
    </div>

</body>

</html>