<?php
/**
 * Manual de Instruções Interativo
 * Sistema de Gestão - BHSAC
 */

require_once __DIR__ . '/config/auth.php';

// Exigir login para acessar
Auth::exigirLogin();

$usuarioLogado = Auth::getUsuario();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual de Instruções | BHSAC</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS Customizado -->
    <link rel="stylesheet" href="css/style.css">

    <style>
        :root {
            --primary: #f59e0b;
            /* Amarelo BHSAC */
            --primary-dark: #d97706;
            --primary-light: #fbbf24;
            --bg-dark: #0f172a;
            /* Preto/Azul Escuro */
            --bg-card: #1e293b;
            --text-primary: #ffffff;
            /* Branco puro para contraste máximo */
            --text-secondary: #cbd5e1;
            /* Cinza claro para subtítulos */
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--bg-dark) 0%, #1a1a2e 100%);
            color: var(--text-primary);
            line-height: 1.6;
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
            margin-bottom: 2rem;
        }

        .card-header {
            background: rgba(245, 158, 11, 0.1);
            border-bottom: 1px solid rgba(245, 158, 11, 0.2);
            padding: 1.25rem;
        }

        .section-title {
            color: var(--primary);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.25rem;
        }

        .manual-section {
            padding: 2rem;
            color: var(--text-primary);
            /* Ensure all text in this section is white */
        }

        .manual-section p,
        .manual-section ul,
        .manual-section li {
            color: var(--text-primary) !important;
        }

        .manual-section h5 {
            color: var(--primary-light);
            font-weight: 700;
        }

        .step-badge {
            background-color: var(--primary);
            color: #000;
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: bold;
            margin-right: 10px;
            flex-shrink: 0;
        }

        .highlight-box {
            background: rgba(245, 158, 11, 0.1);
            border-left: 4px solid var(--primary);
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
            color: #ffffff;
        }

        .text-muted {
            color: var(--text-secondary) !important;
        }

        .nav-link {
            color: var(--text-secondary);
            transition: color 0.2s;
        }

        .nav-link:hover {
            color: var(--primary);
        }

        .icon-box {
            color: var(--primary);
            font-size: 1.2rem;
        }

        footer {
            padding: 2rem;
            text-align: center;
            color: var(--text-secondary);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>

<body>

    <header class="header mb-4 no-print">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="logo-icon me-3">
                    <i class="bi bi-book"></i>
                </div>
                <div class="logo-text">
                    <h1 class="mb-0">BHSAC</h1>
                    <span>Manual do Sistema</span>
                </div>
            </div>

            <nav class="d-flex gap-3 align-items-center">
                <a href="index.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-people pe-1"></i> Funcionários
                </a>
                <a href="producao.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-bricks pe-1"></i> Produção
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

    <main class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                <!-- Introdução -->
                <div class="text-center mb-5">
                    <h2 class="fw-bold" style="color: var(--primary-color)">Bem-vindo ao BHSAC</h2>
                    <p class="text-muted">Este guia ajuda você a configurar e operar o sistema de forma simples. Agora
                        com funcionalidades otimizadas para o seu dia a dia.</p>
                </div>

                <!-- Seção 0: Configuração e Acesso -->
                <div class="card border-primary">
                    <div class="card-header bg-primary bg-opacity-10">
                        <h3 class="section-title mb-0 text-primary"><i class="bi bi-rocket-takeoff"></i> 1. Configuração
                            e Acesso Inicial</h3>
                    </div>
                    <div class="manual-section">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="fw-bold text-primary-light">Acesso de Administrador</h5>
                                <div class="highlight-box bg-dark border-primary">
                                    <p class="mb-1"><strong>Email:</strong> admin@bhsac.com</p>
                                    <p class="mb-0"><strong>Senha:</strong> admin123</p>
                                </div>
                                <p class="small text-muted"><i class="bi bi-info-circle"></i> Recomendamos alterar a
                                    senha após o primeiro acesso.</p>
                            </div>
                            <div class="col-md-6">
                                <h5 class="fw-bold text-primary-light">Configuração Técnica</h5>
                                <ul>
                                    <li><strong>Host:</strong> 127.0.0.1</li>
                                    <li><strong>Porta:</strong> 3307 (MariaDB LAMPP)</li>
                                    <li><strong>Usuário:</strong> bhsac_app</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção 1: Produção -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="section-title mb-0"><i class="bi bi-bricks"></i> 2. Gestão de Produção e Serviços
                        </h3>
                    </div>
                    <div class="manual-section">
                        <p>Este módulo foi otimizado para que você não precise mudar de tela constantemente.</p>

                        <div class="row g-4 mt-2">
                            <div class="col-md-6">
                                <h5 class="fw-bold"><i class="bi bi-lightning-charge-fill pe-2 icon-box"></i> Atalhos de
                                    Cadastro</h5>
                                <p>Cadastre novos produtos/artefatos diretamente onde você estiver:</p>
                                <ul>
                                    <li>No formulário de <strong>Novo Lançamento</strong>.</li>
                                    <li>Na aba <strong>Controle Diário</strong> (botão Novo Produto).</li>
                                    <li>Na <strong>Calculadora de Consumo</strong> (botão Novo Item).</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5 class="fw-bold"><i class="bi bi-calculator pe-2 icon-box"></i> Calculadora de
                                    Consumo</h5>
                                <p>Na aba "Consumo por Peça", o sistema calcula quanto material você precisa para uma
                                    produção e estima o <strong>custo total</strong> automaticamente.</p>
                            </div>
                            <div class="col-md-6">
                                <h5 class="fw-bold"><i class="bi bi-shield-check pe-2 icon-box"></i> Segurança e Revisão
                                </h5>
                                <p>Para evitar erros de digitação:</p>
                                <ul>
                                    <li><strong>Revisão</strong>: Uma janela abrirá para conferir os dados antes de
                                        salvar.</li>
                                    <li><strong>Edição</strong>: Use o lápis <i class="bi bi-pencil text-warning"></i>
                                        para corrigir.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção 2: Funcionários -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="section-title mb-0"><i class="bi bi-people"></i> 3. Gestão de Funcionários</h3>
                    </div>
                    <div class="manual-section">
                        <div class="row g-4">
                            <div class="col-md-12">
                                <p>Gerencie sua equipe, documentos e tempo de casa.</p>
                                <div class="d-flex align-items-start mb-3">
                                    <span class="step-badge">1</span>
                                    <div><strong>Cadastro:</strong> Preencha os dados e anexe documentos (RG, CPF, CNH,
                                        NRs).</div>
                                </div>
                                <div class="d-flex align-items-start mb-3">
                                    <span class="step-badge">2</span>
                                    <div><strong>Tempo de Casa:</strong> O sistema calcula automaticamente o tempo de
                                        empresa na visualização do perfil.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção 3: Financeiro -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="section-title mb-0"><i class="bi bi-wallet2"></i> 4. Gestão Financeira</h3>
                    </div>
                    <div class="manual-section">
                        <p>Controle preciso de fluxo de caixa.</p>
                        <div class="highlight-box border-warning">
                            <i class="bi bi-bell-fill pe-2 text-warning"></i>
                            <strong>Alerta de Salários:</strong> Do dia <strong>01 ao 05</strong>, o sistema mostra quem
                            ainda não recebeu.
                        </div>
                        <ul class="mt-3">
                            <li><strong>Entradas e Saídas:</strong> Organize despesas por categorias fixas.</li>
                            <li><strong>Revisão:</strong> Revise cada lançamento antes de confirmar.</li>
                        </ul>
                    </div>
                </div>

                <!-- Seção 4: Sistema de Login e Usuários -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="section-title mb-0"><i class="bi bi-shield-lock"></i> 5. Usuários e Permissões</h3>
                    </div>
                    <div class="manual-section">
                        <p>O sistema possui controle de acesso com diferentes níveis de permissão.</p>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="highlight-box bg-danger bg-opacity-10 border-danger">
                                    <h5 class="text-danger"><i class="bi bi-shield-fill-check"></i> Admin</h5>
                                    <p class="mb-0 small">Acesso total ao sistema, incluindo gerenciamento de usuários.
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="highlight-box bg-warning bg-opacity-10 border-warning">
                                    <h5 class="text-warning"><i class="bi bi-person-badge"></i> Gerente</h5>
                                    <p class="mb-0 small">Acesso a funcionários, produção e financeiro.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="highlight-box bg-secondary bg-opacity-10 border-secondary">
                                    <h5 class="text-secondary"><i class="bi bi-person"></i> Operador</h5>
                                    <p class="mb-0 small">Acesso a funcionários e produção (sem financeiro).</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h6><i class="bi bi-key"></i> Como acessar:</h6>
                            <div class="d-flex align-items-start mb-2">
                                <span class="step-badge">1</span>
                                <div>Acesse a página de login com seu email e senha.</div>
                            </div>
                            <div class="d-flex align-items-start mb-2">
                                <span class="step-badge">2</span>
                                <div>Após login, você será direcionado para este manual.</div>
                            </div>
                            <div class="d-flex align-items-start mb-2">
                                <span class="step-badge">3</span>
                                <div>Use o menu do usuário (canto superior direito) para navegar ou sair.</div>
                            </div>
                        </div>

                        <?php if (Auth::ehAdmin()): ?>
                            <div class="highlight-box mt-3">
                                <i class="bi bi-info-circle pe-2"></i>
                                <strong>Dica para Admin:</strong> Acesse "Gerenciar Usuários" no menu para criar, editar ou
                                desativar usuários.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button onclick="window.print()" class="btn btn-outline-warning">
                        <i class="bi bi-printer pe-2"></i> Imprimir Este Manual
                    </button>
                </div>

            </div>
        </div>
    </main>

    <footer>
        <p>©
            <?= date('Y') ?> BHSAC - BH Service e Artefatos de Concreto
        <p>Desenvolvido por <a href="https://github.com/losmanim" target="_blank">Luiz Antonio</a></p>
        </p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>