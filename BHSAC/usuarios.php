<?php
/**
 * Módulo de Gerenciamento de Usuários
 * Sistema de Gestão - BHSAC
 */

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/UsuarioDAO.php';

// Apenas admin pode acessar
Auth::exigirNivel('admin');

$usuarioLogado = Auth::getUsuario();
$dao = new UsuarioDAO();
$mensagem = '';

// Processar cadastro de novo usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar'])) {
    try {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $nivel = $_POST['nivel'] ?? 'operador';

        if (empty($nome) || empty($email) || empty($senha)) {
            throw new Exception('Preencha todos os campos obrigatórios');
        }

        if ($dao->emailExiste($email)) {
            throw new Exception('Este email já está cadastrado');
        }

        if (strlen($senha) < 6) {
            throw new Exception('A senha deve ter pelo menos 6 caracteres');
        }

        $dao->criar($nome, $email, $senha, $nivel);
        $mensagem = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Usuário cadastrado com sucesso!</div>';
    } catch (Exception $e) {
        $mensagem = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Erro: ' . $e->getMessage() . '</div>';
    }
}

// Processar edição de usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    try {
        $id = (int) $_POST['id'];
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $nivel = $_POST['nivel'] ?? 'operador';
        $ativo = isset($_POST['ativo']);

        if (empty($nome) || empty($email)) {
            throw new Exception('Preencha todos os campos obrigatórios');
        }

        if ($dao->emailExiste($email, $id)) {
            throw new Exception('Este email já está sendo usado por outro usuário');
        }

        $dao->atualizar($id, $nome, $email, $nivel, $ativo);

        // Alterar senha se informada
        if (!empty($_POST['nova_senha'])) {
            if (strlen($_POST['nova_senha']) < 6) {
                throw new Exception('A nova senha deve ter pelo menos 6 caracteres');
            }
            $dao->alterarSenha($id, $_POST['nova_senha']);
        }

        $mensagem = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Usuário atualizado com sucesso!</div>';
    } catch (Exception $e) {
        $mensagem = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Erro: ' . $e->getMessage() . '</div>';
    }
}

// Processar exclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir'])) {
    try {
        $id = (int) $_POST['id'];

        // Não permitir excluir o próprio usuário
        if ($id === $usuarioLogado['id']) {
            throw new Exception('Você não pode excluir seu próprio usuário');
        }

        $dao->excluir($id);
        $mensagem = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Usuário excluído com sucesso!</div>';
    } catch (Exception $e) {
        $mensagem = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Erro: ' . $e->getMessage() . '</div>';
    }
}

// Buscar lista de usuários
$usuarios = $dao->listarTodos();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários | BHSAC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="logo-text">
                    <h1>BHSAC - Usuários</h1>
                    <span>Gerenciamento de Acesso</span>
                </div>
            </div>

            <div class="header-actions">
                <a href="index.php" class="btn btn-outline btn-sm" title="Funcionários">
                    <i class="bi bi-person-badge"></i> Funcionários
                </a>
                <a href="producao.php" class="btn btn-outline btn-sm" title="Produção">
                    <i class="bi bi-bricks"></i> Produção
                </a>
                <a href="financeiro.php" class="btn btn-outline btn-sm" title="Financeiro">
                    <i class="bi bi-wallet2"></i> Financeiro
                </a>
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
                        <li><a class="dropdown-item text-danger" href="login.php?logout=1"><i
                                    class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- Container Principal -->
    <div class="main-container fade-in">
        <?= $mensagem ?>

        <!-- Cards de Estatísticas -->
        <div class="stats-grid mb-4">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-people"></i></div>
                <div class="stat-info">
                    <span class="stat-value"><?= count($usuarios) ?></span>
                    <span class="stat-label">Total de Usuários</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-person-check"></i></div>
                <div class="stat-info">
                    <span class="stat-value"><?= count(array_filter($usuarios, fn($u) => $u['ativo'])) ?></span>
                    <span class="stat-label">Usuários Ativos</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-shield-check"></i></div>
                <div class="stat-info">
                    <span
                        class="stat-value"><?= count(array_filter($usuarios, fn($u) => $u['nivel'] === 'admin')) ?></span>
                    <span class="stat-label">Administradores</span>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Formulário de Cadastro -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="bi bi-person-plus"></i> Novo Usuário</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label><i class="bi bi-person"></i> Nome Completo *</label>
                                <input type="text" class="form-control" name="nome" required>
                            </div>

                            <div class="form-group">
                                <label><i class="bi bi-envelope"></i> Email *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>

                            <div class="form-group">
                                <label><i class="bi bi-lock"></i> Senha *</label>
                                <input type="password" class="form-control" name="senha" required minlength="6">
                                <small class="text-muted">Mínimo 6 caracteres</small>
                            </div>

                            <div class="form-group">
                                <label><i class="bi bi-shield"></i> Nível de Acesso *</label>
                                <select class="form-control" name="nivel" required>
                                    <option value="operador">Operador - Apenas visualização</option>
                                    <option value="gerente">Gerente - Acesso ao financeiro</option>
                                    <option value="admin">Admin - Acesso total</option>
                                </select>
                            </div>

                            <button type="submit" name="cadastrar" class="btn btn-primary btn-block">
                                <i class="bi bi-plus-circle"></i> Cadastrar Usuário
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Legenda de Níveis -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title"><i class="bi bi-info-circle"></i> Níveis de Acesso</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <span class="badge bg-danger me-2">Admin</span>
                            <small>Acesso total ao sistema, incluindo gerenciamento de usuários</small>
                        </div>
                        <div class="mb-3">
                            <span class="badge bg-warning me-2">Gerente</span>
                            <small>Acesso a funcionários, produção e financeiro</small>
                        </div>
                        <div>
                            <span class="badge bg-secondary me-2">Operador</span>
                            <small>Acesso a funcionários e produção (sem financeiro)</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Usuários -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="bi bi-list-ul"></i> Usuários Cadastrados</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table employees-table">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Nível</th>
                                        <th>Status</th>
                                        <th>Último Acesso</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $u): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($u['nome']) ?></strong>
                                                <?php if ($u['id'] == $usuarioLogado['id']): ?>
                                                    <span class="badge bg-info ms-1">Você</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($u['email']) ?></td>
                                            <td>
                                                <?php
                                                $badgeClass = match ($u['nivel']) {
                                                    'admin' => 'bg-danger',
                                                    'gerente' => 'bg-warning',
                                                    default => 'bg-secondary'
                                                };
                                                ?>
                                                <span class="badge <?= $badgeClass ?>"><?= ucfirst($u['nivel']) ?></span>
                                            </td>
                                            <td>
                                                <?php if ($u['ativo']): ?>
                                                    <span class="badge badge-active">Ativo</span>
                                                <?php else: ?>
                                                    <span class="badge badge-inactive">Inativo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($u['ultimo_acesso']): ?>
                                                    <?= date('d/m/Y H:i', strtotime($u['ultimo_acesso'])) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Nunca</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-secondary"
                                                    onclick="editarUsuario(<?= htmlspecialchars(json_encode($u)) ?>)"
                                                    title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <?php if ($u['id'] != $usuarioLogado['id']): ?>
                                                    <button class="btn btn-sm btn-danger text-danger"
                                                        onclick="confirmarExclusao(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nome']) ?>')"
                                                        title="Excluir">
                                                        <i class="bi bi-trash text-white"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Usuário -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editId">

                        <div class="form-group">
                            <label><i class="bi bi-person"></i> Nome Completo *</label>
                            <input type="text" class="form-control" name="nome" id="editNome" required>
                        </div>

                        <div class="form-group">
                            <label><i class="bi bi-envelope"></i> Email *</label>
                            <input type="email" class="form-control" name="email" id="editEmail" required>
                        </div>

                        <div class="form-group">
                            <label><i class="bi bi-lock"></i> Nova Senha</label>
                            <input type="password" class="form-control" name="nova_senha" minlength="6">
                            <small class="text-muted">Deixe em branco para manter a senha atual</small>
                        </div>

                        <div class="form-group">
                            <label><i class="bi bi-shield"></i> Nível de Acesso *</label>
                            <select class="form-control" name="nivel" id="editNivel" required>
                                <option value="operador">Operador</option>
                                <option value="gerente">Gerente</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <div class="form-check mt-3">
                            <input type="checkbox" class="form-check-input" name="ativo" id="editAtivo">
                            <label class="form-check-label" for="editAtivo">Usuário Ativo</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="editar" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Exclusão -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle"></i> Confirmar Exclusão
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o usuário <strong id="deleteNome"></strong>?</p>
                    <p class="text-muted">Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="excluir" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Excluir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarUsuario(usuario) {
            document.getElementById('editId').value = usuario.id;
            document.getElementById('editNome').value = usuario.nome;
            document.getElementById('editEmail').value = usuario.email;
            document.getElementById('editNivel').value = usuario.nivel;
            document.getElementById('editAtivo').checked = usuario.ativo == 1;

            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
        }

        function confirmarExclusao(id, nome) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteNome').textContent = nome;

            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
    </script>
</body>

</html>