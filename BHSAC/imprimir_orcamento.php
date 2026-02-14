<?php
/**
 * Página de Impressão de Orçamento
 * Sistema de Gestão - BHSAC
 */

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/OrcamentoDAO.php';

Auth::exigirLogin();

$id = $_GET['id'] ?? null;
if (!$id) {
    die('ID do orçamento não informado.');
}

$dao = new OrcamentoDAO();
$orcamento = $dao->getById($id);

if (!$orcamento) {
    die('Orçamento não encontrado.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento <?= $orcamento['numero'] ?> | BH Service</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Cabeçalho */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #f59e0b;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            font-weight: bold;
        }

        .company-info h1 {
            font-size: 24px;
            color: #f59e0b;
            margin-bottom: 5px;
        }

        .company-info p {
            color: #666;
            font-size: 11px;
        }

        .orcamento-info {
            text-align: right;
        }

        .orcamento-numero {
            font-size: 20px;
            font-weight: bold;
            color: #f59e0b;
        }

        .orcamento-data {
            color: #666;
            margin-top: 5px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            margin-top: 8px;
        }

        .status-Pendente {
            background: #fef3c7;
            color: #92400e;
        }

        .status-Aprovado {
            background: #d1fae5;
            color: #065f46;
        }

        .status-Recusado {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-Expirado {
            background: #e5e7eb;
            color: #374151;
        }

        /* Cliente */
        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 11px;
            text-transform: uppercase;
            color: #666;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }

        .cliente-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 10px;
            color: #888;
            text-transform: uppercase;
        }

        .info-value {
            font-weight: 500;
            color: #333;
        }

        /* Tabela de Itens */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background: #1f2937;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }

        th:last-child,
        th:nth-child(3),
        th:nth-child(4),
        th:nth-child(5) {
            text-align: right;
        }

        td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        td:last-child,
        td:nth-child(3),
        td:nth-child(4),
        td:nth-child(5) {
            text-align: right;
        }

        tr:nth-child(even) {
            background: #f9fafb;
        }

        .item-numero {
            color: #666;
            font-weight: bold;
        }

        /* Totais */
        .totais {
            display: flex;
            justify-content: flex-end;
        }

        .totais-box {
            width: 280px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .total-row.final {
            border-bottom: none;
            background: #f59e0b;
            color: white;
            margin-top: 5px;
            padding: 12px 10px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
        }

        /* Condições */
        .condicoes {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }

        .observacoes {
            background: #f9fafb;
            padding: 10px;
            border-radius: 5px;
            border-left: 3px solid #f59e0b;
        }

        /* Rodapé */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
        }

        .assinatura {
            display: flex;
            justify-content: space-around;
            margin-top: 60px;
        }

        .assinatura-box {
            text-align: center;
            width: 200px;
        }

        .assinatura-linha {
            border-top: 1px solid #333;
            padding-top: 5px;
            font-size: 11px;
            color: #666;
        }

        .validade-aviso {
            background: #fef3c7;
            color: #92400e;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-top: 20px;
            font-size: 11px;
        }

        /* Print */
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .no-print {
                display: none;
            }

            .container {
                padding: 0;
                max-width: 100%;
            }
        }

        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #f59e0b;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }

        .btn-print:hover {
            background: #d97706;
        }
    </style>
</head>

<body>
    <button class="btn-print no-print" onclick="window.print()">🖨️ Imprimir</button>

    <div class="container">
        <!-- Cabeçalho -->
        <div class="header">
            <div class="logo-area">
                <div class="logo-icon">B</div>
                <div class="company-info">
                    <h1>BH Service</h1>
                    <p>BH Service e Artefatos de Concreto</p>
                    <p>CNPJ: 00.000.000/0001-00</p>
                </div>
            </div>
            <div class="orcamento-info">
                <div class="orcamento-numero">ORÇAMENTO #<?= htmlspecialchars($orcamento['numero']) ?></div>
                <div class="orcamento-data">
                    Emissão: <?= date('d/m/Y', strtotime($orcamento['data_emissao'])) ?><br>
                    Validade: <?= date('d/m/Y', strtotime($orcamento['data_validade'])) ?>
                </div>
                <span class="status-badge status-<?= $orcamento['status'] ?>"><?= $orcamento['status'] ?></span>
            </div>
        </div>

        <!-- Dados do Cliente -->
        <div class="section">
            <div class="section-title">Dados do Cliente</div>
            <div class="cliente-info">
                <div class="info-item">
                    <span class="info-label">Nome / Razão Social</span>
                    <span class="info-value"><?= htmlspecialchars($orcamento['cliente_nome']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">CPF / CNPJ</span>
                    <span class="info-value"><?= htmlspecialchars($orcamento['cliente_documento'] ?: '-') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Contato</span>
                    <span class="info-value"><?= htmlspecialchars($orcamento['cliente_contato'] ?: '-') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Endereço</span>
                    <span class="info-value"><?= htmlspecialchars($orcamento['cliente_endereco'] ?: '-') ?></span>
                </div>
            </div>
        </div>

        <!-- Itens do Orçamento -->
        <div class="section">
            <div class="section-title">Itens do Orçamento</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Descrição</th>
                        <th style="width: 80px;">Qtd</th>
                        <th style="width: 60px;">Un</th>
                        <th style="width: 100px;">Valor Un.</th>
                        <th style="width: 100px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orcamento['itens'] as $i => $item): ?>
                        <tr>
                            <td class="item-numero"><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($item['descricao']) ?></td>
                            <td><?= number_format($item['quantidade'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($item['unidade']) ?></td>
                            <td>R$ <?= number_format($item['valor_unitario'], 2, ',', '.') ?></td>
                            <td><strong>R$ <?= number_format($item['valor_total'], 2, ',', '.') ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Totais -->
            <div class="totais">
                <div class="totais-box">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>R$ <?= number_format($orcamento['subtotal'], 2, ',', '.') ?></span>
                    </div>
                    <?php if ($orcamento['desconto'] > 0): ?>
                        <div class="total-row">
                            <span>Desconto:</span>
                            <span style="color: #dc2626;">- R$
                                <?= number_format($orcamento['desconto'], 2, ',', '.') ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="total-row final">
                        <span>VALOR TOTAL:</span>
                        <span>R$ <?= number_format($orcamento['valor_total'], 2, ',', '.') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Condições -->
        <div class="condicoes">
            <div>
                <div class="info-item">
                    <span class="info-label">Condições de Pagamento</span>
                    <span
                        class="info-value"><?= htmlspecialchars($orcamento['condicoes_pagamento'] ?: 'A combinar') ?></span>
                </div>
            </div>
            <?php if (!empty($orcamento['observacoes'])): ?>
                <div class="observacoes">
                    <span class="info-label">Observações</span>
                    <p style="margin-top: 5px;"><?= nl2br(htmlspecialchars($orcamento['observacoes'])) ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Validade -->
        <div class="validade-aviso">
            ⚠️ Este orçamento tem validade até
            <strong><?= date('d/m/Y', strtotime($orcamento['data_validade'])) ?></strong>.
            Após esta data, os valores e condições podem ser alterados.
        </div>

        <!-- Assinaturas -->
        <div class="assinatura">
            <div class="assinatura-box">
                <div class="assinatura-linha">BH Service - Responsável</div>
            </div>
            <div class="assinatura-box">
                <div class="assinatura-linha">Cliente - De Acordo</div>
            </div>
        </div>

        <!-- Rodapé -->
        <div class="footer">
            <p style="color: #888; font-size: 10px;">
                Documento gerado em <?= date('d/m/Y H:i') ?> | BH Service - Sistema de Gestão
            </p>
        </div>
    </div>
</body>

</html>