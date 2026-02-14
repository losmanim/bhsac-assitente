# Guia de Alterações - Sistema BHSAC

## 📋 Resumo das Alterações

Este documento descreve todas as alterações realizadas no sistema de gestão BHSAC, incluindo o novo sistema de login, controle de usuários e melhorias na interface.

---

## 🔐 1. Sistema de Autenticação e Login

### Arquivos Criados:
- **`config/auth.php`** - Classe de autenticação com controle de sessão
- **`models/UsuarioDAO.php`** - Model para operações de usuário no banco
- **`login.php`** - Página de login
- **`usuarios.php`** - Página de gerenciamento de usuários (apenas admin)

### Tabela de Usuários:
```sql
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel ENUM('admin', 'gerente', 'operador') DEFAULT 'operador',
    ativo BOOLEAN DEFAULT TRUE,
    ultimo_acesso DATETIME NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Níveis de Acesso:

| Nível | Funcionários | Produção | Financeiro | Usuários |
|-------|:------------:|:--------:|:----------:|:--------:|
| **Admin** | ✅ | ✅ | ✅ | ✅ |
| **Gerente** | ✅ | ✅ | ✅ | ❌ |
| **Operador** | ✅ | ✅ | ❌ | ❌ |

### Credenciais Iniciais:
- **Email:** `admin@bhsac.com`
- **Senha:** `bhservice2026`

---

## 🛡️ 2. Proteção das Páginas

### Páginas Protegidas (exigem login):
- `index.php` - Funcionários
- `producao.php` - Produção
- `financeiro.php` - Financeiro (apenas gerente/admin)
- `manual.php` - Manual do sistema
- `usuarios.php` - Gerenciamento de usuários (apenas admin)

### Como Funciona:
```php
// No início de cada página protegida:
require_once __DIR__ . '/config/auth.php';

Auth::exigirLogin();        // Exige qualquer usuário logado
Auth::exigirNivel('gerente'); // Exige nível mínimo de gerente
```

---

## 👤 3. Menu do Usuário

Todas as páginas agora possuem um menu dropdown no canto superior direito com:
- Nome do usuário logado
- Badge com nível de acesso
- Link para "Gerenciar Usuários" (apenas para admin)
- Botão "Sair"

---

## 💰 4. Melhorias no Financeiro

### Novo Botão "Nova Movimentação":
- Botão destacado no header da página
- Scroll automático até o formulário
- Foco no campo descrição

### Modal de Confirmação:
- Antes de salvar, exibe resumo dos dados
- Permite revisar antes de confirmar

---

## 📅 5. Período de Pagamento

Alterado de **01-05** para **01-10** de cada mês.

Arquivos modificados:
- `models/FinanceiroDAO.php` - Função `isPeriodoPagamento()`
- `manual.php` - Texto informativo
- `MANUAL.md` - Documentação

---

## 📁 Estrutura de Arquivos

```
BHSAC/
├── api/
├── config/
│   ├── auth.php          ← NOVO
│   └── database.php
├── css/
│   └── style.css         ← Atualizado (estilos modais/dropdown)
├── models/
│   ├── FinanceiroDAO.php
│   ├── FuncionarioDAO.php
│   ├── ProducaoDAO.php
│   └── UsuarioDAO.php    ← NOVO
├── uploads/
├── financeiro.php        ← Atualizado
├── funcionarios.php
├── index.php             ← Atualizado
├── login.php             ← NOVO
├── manual.php            ← Atualizado
├── MANUAL.md             ← Atualizado
├── producao.php          ← Atualizado
└── usuarios.php          ← NOVO
```

---

## 🔧 Fluxo de Autenticação

```
┌─────────────────┐
│   login.php     │
│  (formulário)   │
└────────┬────────┘
         │ POST email/senha
         ▼
┌─────────────────┐
│   Auth::login() │
│  (config/auth)  │
└────────┬────────┘
         │ Valida credenciais
         ▼
┌─────────────────┐     ┌─────────────────┐
│ UsuarioDAO::    │────▶│   Banco MySQL   │
│ autenticar()    │     │ tabela usuarios │
└────────┬────────┘     └─────────────────┘
         │ Sucesso
         ▼
┌─────────────────┐
│   $_SESSION     │
│ usuario_id      │
│ usuario_nome    │
│ usuario_nivel   │
└────────┬────────┘
         │ Redireciona
         ▼
┌─────────────────┐
│   manual.php    │
│ (página inicial)│
└─────────────────┘
```

---

## 📝 Como Criar Novo Usuário

1. Faça login como **admin**
2. Clique no menu do usuário (canto superior direito)
3. Selecione "Gerenciar Usuários"
4. Preencha o formulário "Novo Usuário"
5. Escolha o nível de acesso
6. Clique em "Cadastrar Usuário"

---

## 🔒 Segurança

- Senhas armazenadas com hash bcrypt (`password_hash()`)
- Sessões PHP para controle de login
- Verificação de nível em cada página protegida
- Proteção contra SQL Injection (PDO prepared statements)

---

## 📞 Suporte

Para dúvidas ou problemas, consulte o manual integrado no sistema ou entre em contato com o desenvolvedor.

**Desenvolvido por:** Luiz Antonio  
**GitHub:** https://github.com/losmanim
