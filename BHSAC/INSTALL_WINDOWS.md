# 🏁 Guia de Instalação no Windows (WampServer)

Este documento descreve como configurar o ambiente e instalar o sistema **BHSAC** em um computador com sistema operacional Windows utilizando o **WampServer**.

---

## 🛠️ Passo 1: Preparação do Ambiente

1.  **Baixar WampServer**: Acesse [wampserver.com](https://www.wampserver.com/) e baixe a versão compatível com seu sistema (64 bits ou 32 bits).
2.  **Instalação**: Execute o instalador. Escolha um diretório (o padrão é `C:\wamp64`).
    *   *Dica: Certifique-se de ter os pacotes Visual C++ redistribuíveis instalados (o instalador do WAMP costuma avisar sobre isso).*
3.  **Iniciar**: Abra o WampServer. O ícone na barra de tarefas deve ficar **verde**.

---

## 📂 Passo 2: Copiar os Arquivos

1.  Localize a pasta `www` dentro do diretório do WampServer (Geralmente `C:\wamp64\www`).
2.  Crie uma pasta chamada `BHSAC`.
3.  Copie todos os arquivos do projeto para `C:\wamp64\www\BHSAC`.

---

## 🗄️ Passo 3: Configurar o Banco de Dados

1.  **Acesse o phpMyAdmin**: Clique no ícone do WAMP -> `phpMyAdmin` (ou acesse `http://localhost/phpmyadmin` no navegador).
    *   *Usuário padrão*: `root`
    *   *Senha*: (em branco)
2.  **Criar Banco**: No menu lateral, clique em "Novo" e crie um banco de dados chamado `gestao_funcionarios` com a codificação `utf8mb4_unicode_ci`.
3.  **Importar Tabelas**:
    *   Selecione o banco `gestao_funcionarios`.
    *   Vá na aba **Importar**.
    *   Selecione os arquivos da pasta `config/` na seguinte ordem de preferência:
        1.  `setup_database.sql`
        2.  `create_financeiro.sql`
        3.  `create_producao.sql`
        4.  `create_orcamento_consumo.sql`
        5.  `create_usuarios.sql`

---

## ⚙️ Passo 4: Ajustar Conexão no PHP

Abra o arquivo `C:\wamp64\www\BHSAC\config\database.php` e ajuste as constantes para o padrão do WAMP:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gestao_funcionarios');
define('DB_PORT', '3306'); // WAMP geralmente usa 3306
```

---

## 🚀 Passo 5: Acesso Final

1.  Abra o navegador e digite: `http://localhost/BHSAC/`
2.  Para o primeiro acesso, use as credenciais padrão:
    *   **Login**: `admin@bhsac.com`
    *   **Senha**: `admin123`

---
> [!NOTE]
> **Suporte**: Se o ícone do WAMP estiver laranja, pode haver conflito de porta (ex: Skype ou IIS usando a porta 80). Verifique as configurações de serviços.
