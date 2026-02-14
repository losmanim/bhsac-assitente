# 🏗️ Manual de Instruções - Sistema BHSAC

Bem-vindo ao manual do sistema **BHSAC (BH Service e Artefatos de Concreto)**. Este guia foi criado para ajudar você a configurar e operar o sistema de forma simples e eficiente.

> [!NOTE]
> Embora o sistema seja identificado como **BHSAC**, os documentos impressos (orçamentos e relatórios) utilizam o nome fantasia **BH Service** para fins comerciais.

---

## 🚀 1. Configuração e Acesso Inicial

O sistema agora possui um controle de acesso seguro. Para rodar e acessar pela primeira vez, siga os detalhes abaixo:

### Configuração Técnica (Porta 3307):
O banco de dados utiliza uma porta específica no servidor LAMPP. Verifique o arquivo `config/database.php`:
*   **Host**: `127.0.0.1`
*   **Porta**: `3307`
*   **Usuário**: `bhsac_app`
*   **Senha**: `app123`

### Login de Administrador:
Use as credenciais abaixo para sua primeira entrada:
*   **Email**: `admin@bhsac.com`
*   **Senha**: `admin123`

> [!IMPORTANT]
> **Segurança**: Recomenda-se criar seu próprio usuário administrativo no menu "Gerenciar Usuários" e desativar o acesso padrão após a configuração.

---

## 🏗️ 2. Gestão de Produção e Serviços

Este módulo é o coração operacional da empresa. Ele foi otimizado para que você não precise mudar de tela constantemente.

### Lançamentos Dinâmicos:
*   **Atalhos de Cadastro**: Agora você pode cadastrar novos produtos/serviços em três lugares:
    1.  No formulário de **Novo Lançamento**.
    2.  Na aba de **Controle de Produção Diária** (botão "Novo Produto").
    3.  Na **Calculadora de Consumo** (aba Consumo por Peça).
*   **Tipos de Operação**:
    *   **Produção**: Fabricação de novos itens.
    *   **Venda**: Saída de estoque para clientes.
    *   **Consumo**: Uso interno de materiais (ex: cimento para blocos).
    *   **Serviço**: Prestação de mão de obra ou fretes (agora medidos em **km**).

### Calculadora de Consumo:
Na aba **Consumo por Peça**, você pode selecionar uma peça e a quantidade desejada. O sistema calculará automaticamente o material necessário e o **custo total estimado**, ajudando no planejamento de compras.

---

## 👥 3. Gestão de Funcionários

Acesse a página inicial para gerenciar sua equipe e documentos.

### Principais Ações:
*   **Cadastro com Anexos**: Você pode salvar fotos de documentos (RG, CPF, CNH) e certificados de cursos (NRs).
*   **Tempo de Casa**: O sistema calcula automaticamente o tempo de empresa de cada colaborador.
*   **Impressão**: Gere relatórios profissionais clicando nos botões de relatório simples ou completo.

---

## 💰 4. Gestão Financeira

Clique em **"Financeiro"** para controlar o fluxo de caixa.

### Recursos:
*   **Resumo de Salários**: Do dia **01 ao 05 de cada mês**, o sistema exibirá automaticamente um alerta com a lista de funcionários que ainda não receberam.
*   **Categorias Fixas**: As movimentações são organizadas por categorias para que você saiba exatamente onde está gastando.
*   **Segurança**: Ao salvar qualquer valor, o sistema solicita uma revisão dos dados para evitar erros de digitação.

---

## 🛠️ 5. Dicas de Uso

*   **Impressão (Ctrl + P)**: Todas as páginas escondem menus e botões automaticamente ao imprimir, deixando o relatório limpo.
*   **Organização de Arquivos**: Documentos anexados ficam salvos com segurança em `uploads/documentos/`.
*   **Níveis de Acesso**: 
    *   **Admin**: Tudo liberado + Gerenciar usuários.
    *   **Gerente**: Acesso total às operações (Produção, Financeiro).
    *   **Operador**: Acesso apenas à Produção e Cadastro.

---
*Desenvolvido por Luiz Antonio*
