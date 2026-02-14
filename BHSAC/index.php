<?php
// Incluir arquivos necessários
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/funcionarios.php';
require_once __DIR__ . '/models/FuncionarioDAO.php';

// Exigir login para acessar esta página
Auth::exigirLogin();

$usuarioLogado = Auth::getUsuario();

$dao = new FuncionarioDAO();
$mensagem = '';
$funcionarioDetalhes = null;
$resultadoBusca = null;
$termoBusca = '';

// Função para processar upload de arquivo
function processarUpload($campo, $cpfFuncionario)
{
    if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $arquivo = $_FILES[$campo];

    // Verificar erros
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Erro no upload do arquivo: " . $campo);
    }

    // Verificar tipo de arquivo (apenas imagens e PDF)
    $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $tipoArquivo = finfo_file($finfo, $arquivo['tmp_name']);
    finfo_close($finfo);

    if (!in_array($tipoArquivo, $tiposPermitidos)) {
        throw new Exception("Tipo de arquivo não permitido para $campo. Use JPG, PNG, GIF, WEBP ou PDF.");
    }

    // Verificar tamanho (máx 5MB)
    if ($arquivo['size'] > 5 * 1024 * 1024) {
        throw new Exception("Arquivo $campo muito grande. Máximo 5MB.");
    }

    // Gerar nome único
    $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
    $cpfLimpo = preg_replace('/[^0-9]/', '', $cpfFuncionario);
    $nomeArquivo = $campo . '_' . $cpfLimpo . '_' . time() . '.' . $extensao;
    $destino = __DIR__ . '/uploads/documentos/' . $nomeArquivo;

    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        throw new Exception("Erro ao salvar arquivo $campo.");
    }

    return 'uploads/documentos/' . $nomeArquivo;
}

// Processar cadastro de novo funcionário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $funcionario = new Funcionario();
    try {
        // Processar uploads
        $anexos = [];
        $cpf = $_POST['num-cpf'] ?? '';
        $anexos['cpf'] = processarUpload('anexo_cpf', $cpf);
        $anexos['rg'] = processarUpload('anexo_rg', $cpf);
        $anexos['cnh'] = processarUpload('anexo_cnh', $cpf);
        $anexos['nrs'] = processarUpload('anexo_nrs', $cpf);
        $anexos['certificados'] = processarUpload('anexo_certificados', $cpf);

        $dao->cadastrar($funcionario, $anexos);
        $mensagem = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Funcionário cadastrado com sucesso!</div>';
    } catch (Exception $e) {
        $mensagem = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Erro: ' . $e->getMessage() . '</div>';
    }
}

// Processar edição de funcionário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    try {
        $funcionario = new Funcionario();

        // Processar uploads
        $anexos = [];
        $cpf = $_POST['num-cpf'] ?? '';
        $anexos['cpf'] = processarUpload('anexo_cpf', $cpf);
        $anexos['rg'] = processarUpload('anexo_rg', $cpf);
        $anexos['cnh'] = processarUpload('anexo_cnh', $cpf);
        $anexos['nrs'] = processarUpload('anexo_nrs', $cpf);
        $anexos['certificados'] = processarUpload('anexo_certificados', $cpf);

        $dao->atualizar($_POST['id'], $funcionario, $anexos);
        $mensagem = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Funcionário atualizado com sucesso!</div>';
    } catch (Exception $e) {
        $mensagem = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Erro: ' . $e->getMessage() . '</div>';
    }
}


// Processar exclusão de funcionário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir'])) {
    try {
        $dao->excluir($_POST['id']);
        $mensagem = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Funcionário excluído com sucesso!</div>';
    } catch (Exception $e) {
        $mensagem = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Erro: ' . $e->getMessage() . '</div>';
    }
}

// Processar busca de funcionário
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['busca']) && !empty($_GET['busca'])) {
    $termoBusca = trim($_GET['busca']);
    $resultadoBusca = $dao->buscar($termoBusca);
}

// Buscar detalhes de um funcionário específico
if (isset($_GET['detalhes'])) {
    $funcionarioDetalhes = $dao->buscarPorId($_GET['detalhes']);
}

// Buscar lista de funcionários
$funcionarios = $dao->listarTodos();
$totalSalarios = array_sum(array_column($funcionarios, 'salario'));
$mediaSalario = count($funcionarios) > 0 ? $totalSalarios / count($funcionarios) : 0;
$funcionariosAtivos = count(array_filter($funcionarios, fn($f) => empty($f['data_rescisao'])));

// Agrupar cargos para relatório
$cargoCount = [];
foreach ($funcionarios as $f) {
    $cargo = $f['cargo'];
    $cargoCount[$cargo] = ($cargoCount[$cargo] ?? 0) + 1;
}
arsort($cargoCount);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Funcionários | BHSAC</title>
    <meta name="description" content="Sistema de Gestão de Funcionários - BH Service e Artefatos de Concreto">

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

            .employees-table th,
            .employees-table td {
                border: 1px solid #ddd !important;
                color: black !important;
            }

            .badge {
                border: 1px solid #999 !important;
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
            <div class="logo">
                <div class="logo-icon">
                    <i class="bi bi-bricks"></i>
                </div>
                <div class="logo-text">
                    <h1>BHSAC - Funcionários</h1>
                    <span>BH Service e Artefatos de Concreto</span>
                </div>
            </div>

            <!-- Campo de Busca no Header -->
            <form class="search-form" action="index.php" method="GET">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" name="busca" placeholder="Buscar funcionário..."
                        value="<?= htmlspecialchars($termoBusca) ?>" autocomplete="off">
                    <button type="submit" class="btn-search">Buscar</button>
                </div>
                <div id="linksAi">
                    <a href="assistente_voz.html" class="btn btn-outline btn-sm" title="Assistente de Voz">
                        <i class="bi bi-mic"></i> Assistente de Virtual
                    </a>
                </div>
            </form>

            <div class="header-actions">
                <a href="manual.php" class="btn btn-outline btn-sm" title="Manual de Uso">
                    <i class="bi bi-book"></i> Manual
                </a>
                <a href="producao.php" class="btn btn-outline btn-sm" title="Produção e Serviços">
                    <i class="bi bi-bricks"></i> Produção
                </a>
                <a href="financeiro.php" class="btn btn-outline btn-sm" title="Gestão Financeira">
                    <i class="bi bi-wallet2"></i> Financeiro
                </a>
                <button class="btn btn-outline btn-sm" onclick="openReportModal('simple')" title="Relatório Simples">
                    <i class="bi bi-file-text"></i>
                </button>
                <button class="btn btn-outline btn-sm" onclick="openReportModal('complete')" title="Relatório Completo">
                    <i class="bi bi-file-earmark-text"></i>
                </button>
                <span class="text-secondary ms-2">
                    <i class="bi bi-calendar3"></i>
                    <?= date('d/m/Y') ?>
                </span>

                <!-- Menu do Usuário -->
                <div class="dropdown ms-3">
                    <button class="btn btn-outline btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= htmlspecialchars($usuarioLogado['nome']) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span
                                class="dropdown-item-text text-muted small"><?= htmlspecialchars($usuarioLogado['email']) ?></span>
                        </li>
                        <li><span class="dropdown-item-text"><span
                                    class="badge bg-warning"><?= ucfirst($usuarioLogado['nivel']) ?></span></span></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <?php if (Auth::ehAdmin()): ?>
                            <li><a class="dropdown-item" href="usuarios.php"><i class="bi bi-people me-2"></i>Gerenciar
                                    Usuários</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                        <?php endif; ?>
                        <li><a class="dropdown-item text-danger" href="login.php?logout=1"><i
                                    class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- Header para impressão -->
    <div class="print-only" style="text-align: center; padding: 20px; border-bottom: 2px solid #f59e0b;">
        <img src="img/logo-bh-service002.ico" alt="BHSAC Logo" style="height: 50px;">
        <h1 style="color: #f59e0b; margin: 0;">BH Service</h1>
        <p style="margin: 5px 0;">BH Service e Artefatos de Concreto</p>
        <p style="font-size: 12px; color: #666;">Relatório gerado em: <?= date('d/m/Y H:i') ?></p>
    </div>

    <!-- Container Principal -->
    <div class="main-container fade-in">

        <?= $mensagem ?>

        <!-- Resultados da Busca -->
        <?php if ($resultadoBusca !== null): ?>
            <div class="card mb-4 search-results no-print">
                <div class="card-header">
                    <h2><i class="bi bi-search"></i> Resultados da busca: "<?= htmlspecialchars($termoBusca) ?>"</h2>
                    <a href="index.php" class="btn btn-outline btn-sm">
                        <i class="bi bi-x-lg"></i> Limpar busca
                    </a>
                </div>
                <div class="card-body">
                    <?php if (count($resultadoBusca) > 0): ?>
                        <div class="search-results-list">
                            <?php foreach ($resultadoBusca as $f): ?>
                                <?php
                                $iniciais = strtoupper(substr($f['nome'], 0, 1));
                                $nomePartes = explode(' ', $f['nome']);
                                if (count($nomePartes) > 1) {
                                    $iniciais .= strtoupper(substr(end($nomePartes), 0, 1));
                                }
                                ?>
                                <div class="search-result-item" onclick="showDetails(<?= $f['id'] ?>)">
                                    <div class="employee-avatar"><?= $iniciais ?></div>
                                    <div class="result-info">
                                        <div class="result-name"><?= htmlspecialchars($f['nome']) ?></div>
                                        <div class="result-meta">
                                            <span class="badge badge-cargo"><?= htmlspecialchars($f['cargo']) ?></span>
                                            <span class="result-salary">R$ <?= number_format($f['salario'], 2, ',', '.') ?></span>
                                        </div>
                                    </div>
                                    <div class="result-actions">
                                        <button class="btn btn-outline btn-sm"
                                            onclick="event.stopPropagation(); showDetails(<?= $f['id'] ?>)">
                                            <i class="bi bi-eye"></i> Ver detalhes
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-search"></i>
                            <p>Nenhum funcionário encontrado para "<?= htmlspecialchars($termoBusca) ?>"</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Cards de Estatísticas -->
        <div class="stats-grid no-print">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-value"><?= count($funcionarios) ?></div>
                <div class="stat-label">Total de Funcionários</div>
            </div>

            <div class="stat-card green">
                <div class="stat-icon">
                    <i class="bi bi-person-check"></i>
                </div>
                <div class="stat-value"><?= $funcionariosAtivos ?></div>
                <div class="stat-label">Contratos Ativos</div>
            </div>

            <div class="stat-card blue">
                <div class="stat-icon">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="stat-value">R$ <?= number_format($totalSalarios, 2, ',', '.') ?></div>
                <div class="stat-label">Folha de Pagamento</div>
            </div>

            <div class="stat-card purple">
                <div class="stat-icon">
                    <i class="bi bi-graph-up"></i>
                </div>
                <div class="stat-value">R$ <?= number_format($mediaSalario, 2, ',', '.') ?></div>
                <div class="stat-label">Salário Médio</div>
            </div>
        </div>

        <!-- Grid de Conteúdo -->
        <div class="content-grid">

            <!-- Lista de Funcionários -->
            <div class="card">
                <div class="card-header">
                    <h2 class="text-primary"><i class="bi bi-person-badge"></i> Funcionários</h2>
                    <span class="badge badge-cargo"><?= count($funcionarios) ?> registros</span>
                </div>
                <div class="card-body">
                    <?php if (count($funcionarios) > 0): ?>
                        <div class="table-container">
                            <table class="employees-table">
                                <thead>
                                    <tr>
                                        <th>Funcionário</th>
                                        <th>Cargo</th>
                                        <th>Salário</th>
                                        <th>Status</th>
                                        <th class="no-print">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($funcionarios as $f): ?>
                                        <?php
                                        $iniciais = strtoupper(substr($f['nome'], 0, 1));
                                        $nomePartes = explode(' ', $f['nome']);
                                        if (count($nomePartes) > 1) {
                                            $iniciais .= strtoupper(substr(end($nomePartes), 0, 1));
                                        }
                                        $contratoAtivo = empty($f['data_rescisao']);
                                        ?>
                                        <tr class="<?= !$contratoAtivo ? 'row-inactive' : '' ?>">
                                            <td>
                                                <div class="employee-info">
                                                    <div class="employee-avatar"><?= $iniciais ?></div>
                                                    <div>
                                                        <div class="employee-name text-primary">
                                                            <?= htmlspecialchars($f['nome']) ?>
                                                        </div>
                                                        <div class="employee-email text-secondary">
                                                            <?= htmlspecialchars($f['email']) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge badge-cargo"><?= htmlspecialchars($f['cargo']) ?></span></td>
                                            <td class="salary">R$ <?= number_format($f['salario'], 2, ',', '.') ?></td>
                                            <td>
                                                <?php if ($contratoAtivo): ?>
                                                    <span class="badge badge-active"><i class="bi bi-check-circle"></i> Ativo</span>
                                                <?php else: ?>
                                                    <span class="badge badge-inactive"><i class="bi bi-x-circle"></i>
                                                        Desligado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="no-print">
                                                <div class="action-buttons">
                                                    <button class="btn btn-outline btn-sm"
                                                        onclick="showDetails(<?= $f['id'] ?>)" title="Ver detalhes">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline btn-sm"
                                                        onclick="editEmployee(<?= $f['id'] ?>)" title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-outline btn-sm btn-danger-outline"
                                                        onclick="confirmDelete(<?= $f['id'] ?>, '<?= htmlspecialchars($f['nome'], ENT_QUOTES) ?>')"
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
                            <i class="bi bi-people"></i>
                            <p>Nenhum funcionário cadastrado ainda.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Formulário de Cadastro -->
            <div class="card no-print">
                <div class="card-header">
                    <h2 class="text-primary"><i class="bi bi-person-plus"></i> Novo Funcionário</h2>
                </div>
                <div class="card-body">
                    <form action="index.php" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="nome"><i class="bi bi-person"></i> Nome Completo</label>
                            <input type="text" class="form-control" id="nome" name="nome" required
                                placeholder="Digite o nome completo">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="cargo"><i class="bi bi-briefcase"></i> Cargo</label>
                                <input type="text" class="form-control" id="cargo" name="cargo" required
                                    placeholder="Ex: Pedreiro">
                            </div>
                            <div class="form-group">
                                <label for="salario"><i class="bi bi-currency-dollar"></i> Salário</label>
                                <input type="number" step="0.01" class="form-control" id="salario" name="salario"
                                    required placeholder="0,00">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="endereco"><i class="bi bi-geo-alt"></i> Endereço</label>
                            <input type="text" class="form-control" id="endereco" name="endereco" required
                                placeholder="Rua, número - Bairro">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email"><i class="bi bi-envelope"></i> Email</label>
                                <input type="email" class="form-control" id="email" name="email" required
                                    placeholder="email@exemplo.com">
                            </div>
                            <div class="form-group">
                                <label for="telefone"><i class="bi bi-telephone"></i> Telefone</label>
                                <input type="tel" class="form-control" id="telefone" name="telefone" required
                                    placeholder="(00) 00000-0000">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="num-cpf"><i class="bi bi-card-text"></i> CPF</label>
                                <input type="text" class="form-control" id="num-cpf" name="num-cpf" required
                                    placeholder="000.000.000-00">
                            </div>
                            <div class="form-group">
                                <label for="data-nascimento"><i class="bi bi-calendar-event"></i> Nascimento</label>
                                <input type="date" class="form-control" id="data-nascimento" name="data-nascimento"
                                    required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="rg"><i class="bi bi-card-heading"></i> RG</label>
                                <input type="text" class="form-control" id="rg" name="rg" placeholder="00.000.000-0">
                            </div>
                            <div class="form-group">
                                <label for="cnh"><i class="bi bi-car-front"></i> CNH</label>
                                <input type="text" class="form-control" id="cnh" name="cnh" placeholder="00000000000">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="data-contratacao"><i class="bi bi-calendar-check"></i> Data de
                                    Contratação</label>
                                <input type="date" class="form-control" id="data-contratacao" name="data-contratacao">
                            </div>
                            <div class="form-group">
                                <label for="data-rescisao"><i class="bi bi-calendar-x"></i> Data de Rescisão</label>
                                <input type="date" class="form-control" id="data-rescisao" name="data-rescisao">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="observacoes"><i class="bi bi-chat-text"></i> Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="2"
                                placeholder="Observações adicionais..."></textarea>
                        </div>

                        <!-- Seção de Anexos de Documentos -->
                        <div class="form-section">
                            <h4 class="form-section-title text-primary"><i class="bi bi-paperclip"></i> Anexar
                                Documentos</h4>
                            <p class="form-section-subtitle text-secondary small">Formatos aceitos: JPG, PNG, GIF, WEBP
                                ou PDF (máx. 5MB)</p>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="anexo_cpf"><i class="bi bi-file-earmark-image"></i> Cópia do CPF</label>
                                    <input type="file" class="form-control" id="anexo_cpf" name="anexo_cpf"
                                        accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="anexo_rg"><i class="bi bi-file-earmark-image"></i> Cópia do RG</label>
                                    <input type="file" class="form-control" id="anexo_rg" name="anexo_rg"
                                        accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="anexo_cnh"><i class="bi bi-file-earmark-image"></i> Cópia da CNH</label>
                                    <input type="file" class="form-control" id="anexo_cnh" name="anexo_cnh"
                                        accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="anexo_nrs"><i class="bi bi-shield-check"></i> NRs (Normas
                                        Regulamentadoras)</label>
                                    <input type="file" class="form-control" id="anexo_nrs" name="anexo_nrs"
                                        accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="anexo_certificados"><i class="bi bi-award"></i> Certificados</label>
                                    <input type="file" class="form-control" id="anexo_certificados"
                                        name="anexo_certificados" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="submit" class="btn btn-primary btn-block">
                            <i class="bi bi-plus-circle"></i>
                            Cadastrar Funcionário
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes do Funcionário -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bg-dark">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-badge"></i> Detalhes do Funcionário</h5>
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
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Funcionário</h5>
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
                    <p>Tem certeza que deseja excluir o funcionário <strong id="deleteEmployeeName"></strong>?</p>
                    <p class="text-secondary small">Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="id" id="deleteEmployeeId">
                        <button type="submit" name="excluir" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Excluir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Relatórios -->
    <div class="modal fade" id="reportModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content bg-dark">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-file-earmark-text"></i> <span
                            id="reportTitle">Relatório</span></h5>
                    <div class="modal-header-actions">
                        <button class="btn btn-outline btn-sm me-2" onclick="printReport()">
                            <i class="bi bi-printer"></i> Imprimir
                        </button>
                        <button class="btn btn-primary btn-sm me-2" onclick="exportPDF()">
                            <i class="bi bi-file-pdf"></i> Exportar PDF
                        </button>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <div class="modal-body" id="reportContent">
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer no-print">
        <p>© <?= date('Y') ?> BHSAC - BH Service e Artefatos de Concreto</p>
        <p>Desenvolvido por <a href="https://github.com/losmanim" target="_blank">Luiz Antonio</a></p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <!-- html2pdf.js para exportar PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <script>
        // Dados dos funcionários para JavaScript
        const funcionariosData = <?= json_encode($funcionarios) ?>;
        const totalSalarios = <?= $totalSalarios ?>;
        const funcionariosAtivos = <?= $funcionariosAtivos ?>;
        const cargoCount = <?= json_encode($cargoCount) ?>;

        // Função para mostrar detalhes do funcionário
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

            fetch('api/funcionario.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const f = data.funcionario;
                        const iniciais = f.nome.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                        const statusClass = f.data_rescisao ? 'badge-inactive' : 'badge-active';
                        const statusText = f.data_rescisao ? 'Desligado' : 'Ativo';

                        modalContent.innerHTML = `
                            <div class="details-header">
                                <div class="details-avatar">${iniciais}</div>
                                <div class="details-info">
                                    <h3>${f.nome}</h3>
                                    <span class="badge badge-cargo">${f.cargo}</span>
                                    <span class="badge ${statusClass}">${statusText}</span>
                                </div>
                            </div>
                            
                            <div class="details-grid">
                                <div class="detail-item">
                                    <i class="bi bi-currency-dollar"></i>
                                    <div>
                                        <span class="detail-label">Salário</span>
                                        <span class="detail-value salary">R$ ${parseFloat(f.salario).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</span>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <i class="bi bi-card-text"></i>
                                    <div>
                                        <span class="detail-label">CPF</span>
                                        <span class="detail-value">${f.cpf}</span>
                                    </div>
                                </div>
                                
                                ${f.rg ? `
                                <div class="detail-item">
                                    <i class="bi bi-card-heading"></i>
                                    <div>
                                        <span class="detail-label">RG</span>
                                        <span class="detail-value">${f.rg}</span>
                                    </div>
                                </div>
                                ` : ''}
                                
                                ${f.cnh ? `
                                <div class="detail-item">
                                    <i class="bi bi-car-front"></i>
                                    <div>
                                        <span class="detail-label">CNH</span>
                                        <span class="detail-value">${f.cnh}</span>
                                    </div>
                                </div>
                                ` : ''}
                                
                                <div class="detail-item">
                                    <i class="bi bi-envelope"></i>
                                    <div>
                                        <span class="detail-label">Email</span>
                                        <span class="detail-value">${f.email}</span>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <i class="bi bi-telephone"></i>
                                    <div>
                                        <span class="detail-label">Telefone</span>
                                        <span class="detail-value">${f.telefone}</span>
                                    </div>
                                </div>
                                
                                <div class="detail-item full-width">
                                    <i class="bi bi-geo-alt"></i>
                                    <div>
                                        <span class="detail-label">Endereço</span>
                                        <span class="detail-value">${f.endereco}</span>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <i class="bi bi-calendar-heart"></i>
                                    <div>
                                        <span class="detail-label">Data de Nascimento</span>
                                        <span class="detail-value">${formatDate(f.data_nascimento)}</span>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <i class="bi bi-calendar-check"></i>
                                    <div>
                                        <span class="detail-label">Contratação</span>
                                        <span class="detail-value">${f.data_contratacao ? formatDate(f.data_contratacao) : 'Não informada'}</span>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <i class="bi bi-clock-history"></i>
                                    <div>
                                        <span class="detail-label">Tempo de Empresa</span>
                                        <span class="detail-value">${f.tempo_empresa}</span>
                                    </div>
                                </div>
                                
                                ${f.data_rescisao ? `
                                <div class="detail-item">
                                    <i class="bi bi-calendar-x text-danger"></i>
                                    <div>
                                        <span class="detail-label">Data de Rescisão</span>
                                        <span class="detail-value text-danger">${formatDate(f.data_rescisao)}</span>
                                    </div>
                                </div>
                                ` : ''}
                                
                                ${f.observacoes ? `
                                <div class="detail-item full-width">
                                    <i class="bi bi-chat-text"></i>
                                    <div>
                                        <span class="detail-label">Observações</span>
                                        <span class="detail-value">${f.observacoes}</span>
                                    </div>
                                </div>
                                ` : ''}
                                
                                ${(f.anexo_cpf || f.anexo_rg || f.anexo_cnh || f.anexo_nrs || f.anexo_certificados) ? `
                                <div class="detail-item full-width">
                                    <i class="bi bi-paperclip"></i>
                                    <div>
                                        <span class="detail-label">Documentos Anexados</span>
                                        <div class="document-links">
                                            ${f.anexo_cpf ? `<a href="${f.anexo_cpf}" target="_blank" class="btn btn-outline btn-sm"><i class="bi bi-file-earmark"></i> CPF</a>` : ''}
                                            ${f.anexo_rg ? `<a href="${f.anexo_rg}" target="_blank" class="btn btn-outline btn-sm"><i class="bi bi-file-earmark"></i> RG</a>` : ''}
                                            ${f.anexo_cnh ? `<a href="${f.anexo_cnh}" target="_blank" class="btn btn-outline btn-sm"><i class="bi bi-file-earmark"></i> CNH</a>` : ''}
                                            ${f.anexo_nrs ? `<a href="${f.anexo_nrs}" target="_blank" class="btn btn-outline btn-sm"><i class="bi bi-shield-check"></i> NRs</a>` : ''}
                                            ${f.anexo_certificados ? `<a href="${f.anexo_certificados}" target="_blank" class="btn btn-outline btn-sm"><i class="bi bi-award"></i> Certificados</a>` : ''}
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        `;
                    } else {
                        modalContent.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Erro ao carregar detalhes.</div>`;
                    }
                })
                .catch(error => {
                    modalContent.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Erro de conexão.</div>`;
                });
        }

        // Função para editar funcionário
        function editEmployee(id) {
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            const modalContent = document.getElementById('editModalContent');

            modalContent.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>`;
            modal.show();

            fetch('api/funcionario.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const f = data.funcionario;
                        modalContent.innerHTML = `
                            <form action="index.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="id" value="${f.id}">
                                
                                <div class="form-group">
                                    <label><i class="bi bi-person"></i> Nome Completo</label>
                                    <input type="text" class="form-control" name="nome" value="${f.nome}" required>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label><i class="bi bi-briefcase"></i> Cargo</label>
                                        <input type="text" class="form-control" name="cargo" value="${f.cargo}" required>
                                    </div>
                                    <div class="form-group">
                                        <label><i class="bi bi-currency-dollar"></i> Salário</label>
                                        <input type="number" step="0.01" class="form-control" name="salario" value="${f.salario}" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="bi bi-geo-alt"></i> Endereço</label>
                                    <input type="text" class="form-control" name="endereco" value="${f.endereco}" required>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label><i class="bi bi-envelope"></i> Email</label>
                                        <input type="email" class="form-control" name="email" value="${f.email}" required>
                                    </div>
                                    <div class="form-group">
                                        <label><i class="bi bi-telephone"></i> Telefone</label>
                                        <input type="tel" class="form-control" name="telefone" value="${f.telefone}" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label><i class="bi bi-card-text"></i> CPF</label>
                                        <input type="text" class="form-control" name="num-cpf" value="${f.cpf}" required>
                                    </div>
                                    <div class="form-group">
                                        <label><i class="bi bi-calendar-event"></i> Nascimento</label>
                                        <input type="date" class="form-control" name="data-nascimento" value="${f.data_nascimento}" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label><i class="bi bi-card-heading"></i> RG</label>
                                        <input type="text" class="form-control" name="rg" value="${f.rg || ''}" placeholder="00.000.000-0">
                                    </div>
                                    <div class="form-group">
                                        <label><i class="bi bi-car-front"></i> CNH</label>
                                        <input type="text" class="form-control" name="cnh" value="${f.cnh || ''}" placeholder="00000000000">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label><i class="bi bi-calendar-check"></i> Contratação</label>
                                        <input type="date" class="form-control" name="data-contratacao" value="${f.data_contratacao || ''}">
                                    </div>
                                    <div class="form-group">
                                        <label><i class="bi bi-calendar-x"></i> Rescisão</label>
                                        <input type="date" class="form-control" name="data-rescisao" value="${f.data_rescisao || ''}">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="bi bi-chat-text"></i> Observações</label>
                                    <textarea class="form-control" name="observacoes" rows="2">${f.observacoes || ''}</textarea>
                                </div>
                                
                                <div class="form-section">
                                    <h5 class="form-section-title"><i class="bi bi-paperclip"></i> Atualizar Documentos</h5>
                                    <p class="text-secondary small">Anexe novos arquivos para substituir os existentes</p>
                                    
                                    <div class="form-group">
                                        <label><i class="bi bi-file-earmark-image"></i> Cópia do CPF ${f.anexo_cpf ? '<span class="badge badge-active">Anexado</span>' : ''}</label>
                                        <input type="file" class="form-control" name="anexo_cpf" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label><i class="bi bi-file-earmark-image"></i> Cópia do RG ${f.anexo_rg ? '<span class="badge badge-active">Anexado</span>' : ''}</label>
                                        <input type="file" class="form-control" name="anexo_rg" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label><i class="bi bi-file-earmark-image"></i> Cópia da CNH ${f.anexo_cnh ? '<span class="badge badge-active">Anexado</span>' : ''}</label>
                                        <input type="file" class="form-control" name="anexo_cnh" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label><i class="bi bi-shield-check"></i> NRs (Normas Regulamentadoras) ${f.anexo_nrs ? '<span class="badge badge-active">Anexado</span>' : ''}</label>
                                        <input type="file" class="form-control" name="anexo_nrs" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label><i class="bi bi-award"></i> Certificados ${f.anexo_certificados ? '<span class="badge badge-active">Anexado</span>' : ''}</label>
                                        <input type="file" class="form-control" name="anexo_certificados" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
                                    </div>
                                </div>
                                
                                <button type="submit" name="editar" class="btn btn-primary btn-block">
                                    <i class="bi bi-check-circle"></i> Salvar Alterações
                                </button>
                            </form>
                        `;
                    }
                });
        }

        // Função para confirmar exclusão
        function confirmDelete(id, nome) {
            document.getElementById('deleteEmployeeId').value = id;
            document.getElementById('deleteEmployeeName').textContent = nome;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        // Função para abrir modal de relatório
        function openReportModal(type) {
            const modal = new bootstrap.Modal(document.getElementById('reportModal'));
            const content = document.getElementById('reportContent');
            const title = document.getElementById('reportTitle');

            if (type === 'simple') {
                title.innerHTML = `<i class="text-primary m-2">Relatório Simples</i>`;
                content.innerHTML = generateSimpleReport();
            } else {
                title.innerHTML = `<i class="text-primary m-2">Relatório Completo</i>`;
                content.innerHTML = generateCompleteReport();
            }

            modal.show();
        }

        // Gerar relatório simples
        function generateSimpleReport() {
            let html = `
                <div id="printableReport" class="report-container">
                    <div class="report-header" style="text-align: center;">
                        <img src="img/logo-bh-service002.ico" alt="BHSAC Logo" style="height: 60px; margin-bottom: 10px;">
                        <h2><i class="bi bi-bricks"></i> BHSAC - BH Service e Artefatos de Concreto</h2>
                        <p>Relatório Simples de Funcionários - ${new Date().toLocaleDateString('pt-BR')}</p>
                    </div>
                    
                    <div class="report-summary">
                        <div class="summary-item">
                            <span class="summary-label">Total de Funcionários:</span>
                            <span class="summary-value">${funcionariosData.length}</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Contratos Ativos:</span>
                            <span class="summary-value">${funcionariosAtivos}</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Folha de Pagamento:</span>
                            <span class="summary-value">R$ ${totalSalarios.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</span>
                        </div>
                    </div>
                    
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Cargo</th>
                                <th>Salário</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            funcionariosData.forEach(f => {
                const status = f.data_rescisao ? 'Desligado' : 'Ativo';
                html += `
                    <tr>
                        <td>${f.nome}</td>
                        <td>${f.cargo}</td>
                        <td>R$ ${parseFloat(f.salario).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</td>
                        <td>${status}</td>
                    </tr>
                `;
            });

            html += `</tbody></table></div>`;
            return html;
        }

        // Gerar relatório completo
        function generateCompleteReport() {
            let html = `
                <div id="printableReport" class="report-container">
                    <div class="report-header" style="text-align: center;">
                        <img src="img/logo bh service002.png" alt="BHSAC Logo" style="height: 60px; margin-bottom: 10px;">
                        <h2><i class="bi bi-bricks"></i> BHSAC - BH Service e Artefatos de Concreto</h2>
                        <p>Relatório Completo de Funcionários - ${new Date().toLocaleDateString('pt-BR')}</p>
                    </div>
                    
                    <div class="report-summary">
                        <div class="summary-item">
                            <span class="summary-label">Total de Funcionários:</span>
                            <span class="summary-value">${funcionariosData.length}</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Contratos Ativos:</span>
                            <span class="summary-value">${funcionariosAtivos}</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Desligados:</span>
                            <span class="summary-value">${funcionariosData.length - funcionariosAtivos}</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Folha de Pagamento:</span>
                            <span class="summary-value">R$ ${totalSalarios.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</span>
                        </div>
                    </div>
                    
                    <h4 class="mt-4 text-success"><i class="bi bi-pie-chart"></i> Funcionários por Cargo</h4>
                    <div class="cargo-grid">
            `;

            for (const [cargo, count] of Object.entries(cargoCount)) {
                html += `<div class="cargo-item"><span class="cargo-name">${cargo}</span><span class="cargo-count">${count}</span></div>`;
            }

            html += `</div>
                    
                    <h4 class="mt-4 text-success"><i class="bi bi-table"></i> Lista Detalhada</h4>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Cargo</th>
                                <th>Salário</th>
                                <th>CPF</th>
                                <th>Telefone</th>
                                <th>Contratação</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            funcionariosData.forEach(f => {
                const status = f.data_rescisao ? 'Desligado' : 'Ativo';
                const contratacao = f.data_contratacao ? formatDate(f.data_contratacao) : '-';
                html += `
                    <tr>
                        <td>${f.nome}</td>
                        <td>${f.cargo}</td>
                        <td>R$ ${parseFloat(f.salario).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</td>
                        <td>${f.cpf}</td>
                        <td>${f.telefone}</td>
                        <td>${contratacao}</td>
                        <td>${status}</td>
                    </tr>
                `;
            });

            html += `</tbody></table></div>`;
            return html;
        }

        // Função para imprimir
        function printReport() {
            const content = document.getElementById('printableReport').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Relatório BHSAC</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .report-header { text-align: center; border-bottom: 2px solid #f59e0b; padding-bottom: 15px; margin-bottom: 20px; }
                        .report-header h2 { color: #f59e0b; margin: 0; }
                        .report-summary { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px; }
                        .summary-item { background: #f5f5f5; padding: 10px 15px; border-radius: 8px; }
                        .summary-label { font-weight: bold; margin-right: 5px; }
                        .report-table { width: 100%; border-collapse: collapse; }
                        .report-table th, .report-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        .report-table th { background: #f59e0b; color: white; }
                        .cargo-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
                        .cargo-item { background: #f5f5f5; padding: 8px 12px; border-radius: 5px; }
                        .cargo-count { background: #f59e0b; color: white; padding: 2px 8px; border-radius: 10px; margin-left: 8px; }
                    </style>
                </head>
                <body>${content}</body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        // Função para exportar PDF
        function exportPDF() {
            const element = document.getElementById('printableReport');
            const opt = {
                margin: 10,
                filename: 'relatorio_bhsac_' + new Date().toISOString().split('T')[0] + '.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
            };
            html2pdf().set(opt).from(element).save();
        }

        function formatDate(dateStr) {
            if (!dateStr) return 'Não informada';
            const date = new Date(dateStr);
            return date.toLocaleDateString('pt-BR');
        }
    </script>
</body>

</html>