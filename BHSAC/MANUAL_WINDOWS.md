# 🏗️ Manual de Instruções (Windows/WampServer) - BHSAC

Este manual descreve o uso do sistema **BHSAC** em ambiente Windows. O sistema é idêntico à versão Linux, mas este guia utiliza nomenclaturas e caminhos específicos do Windows.

---

## 🚀 1. Acesso e Login

Após a instalação, você pode acessar o sistema abrindo seu navegador e digitando:
`http://localhost/BHSAC/`

### Credenciais Iniciais:
*   **Email**: `admin@bhsac.com`
*   **Senha**: `admin123`

---

## 🏗️ 2. Módulo de Produção

Gerencie a fabricação de blocos, manilhas e prestação de serviços como fretes.

### Atalhos Rápidos:
Para facilitar seu trabalho, adicionamos botões de "Novo Produto" em três locais:
1.  No formulário de **Novo Lançamento** (à esquerda).
2.  Na aba de **Controle Diário** (botão amarelo/azul).
3.  Na **Calculadora de Consumo** (link azul ao selecionar peça).

### Tipos de Registros:
*   **Produção**: Use quando fabricar itens novos.
*   **Serviço**: Use para fretes (selecione o item "Frete" e informe a quilometragem em **km**).
*   **Consumo**: Registre o uso de materiais (areia, cimento).

---

## 👥 3. Gestão de Funcionários

Acesse a página inicial para gerenciar o RH da empresa.
*   **Documentos**: Você pode anexar fotos ou PDFs dos documentos dos funcionários. Os arquivos ficam guardados em `C:\wamp64\www\BHSAC\uploads\documentos\`.
*   **Relatórios**: Utilize os botões de relatório na parte superior para imprimir a ficha dos colaboradores.

---

## 💰 4. Gestão Financeira

Controle as contas a pagar e a receber.
*   **Alerta de Pagamento**: O sistema avisa automaticamente entre os dias **01 e 05** sobre salários pendentes.
*   **Categorias**: Organize seus gastos para saber onde o dinheiro está sendo investido.

---

## 🛠️ 5. Dicas Úteis no Windows

*   **Backup**: Para fazer backup, abra o phpMyAdmin, selecione o banco e use a aba "Exportar". Salve o arquivo `.sql` em um local seguro (pendrive ou nuvem).
*   **Impressão (Ctrl + P)**: O sistema remove menus e botões vazios ao imprimir, facilitando a geração de documentos físicos.
*   **Atalho na Área de Trabalho**: Você pode criar um atalho no seu navegador para o endereço `http://localhost/BHSAC/` e arrastá-lo para a sua área de Trabalho para acesso rápido.

---
*Desenvolvido por Luiz Antonio*
