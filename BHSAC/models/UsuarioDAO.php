<?php
require_once __DIR__ . '/../config/database.php';

class UsuarioDAO
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Autenticar usuário
     */
    public function autenticar(string $email, string $senha): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE email = ? AND ativo = TRUE");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // Atualizar último acesso
            $this->atualizarUltimoAcesso($usuario['id']);
            unset($usuario['senha']); // Remover senha do retorno
            return $usuario;
        }

        return null;
    }

    /**
     * Atualizar último acesso
     */
    private function atualizarUltimoAcesso(int $id): void
    {
        $stmt = $this->conn->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    }

    /**
     * Listar todos os usuários
     */
    public function listarTodos(): array
    {
        $stmt = $this->conn->query("SELECT id, nome, email, nivel, ativo, ultimo_acesso, criado_em FROM usuarios ORDER BY nome");
        return $stmt->fetchAll();
    }

    /**
     * Buscar usuário por ID
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT id, nome, email, nivel, ativo, ultimo_acesso, criado_em FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Criar novo usuário
     */
    public function criar(string $nome, string $email, string $senha, string $nivel = 'operador'): int
    {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO usuarios (nome, email, senha, nivel) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $email, $senhaHash, $nivel]);
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Atualizar usuário
     */
    public function atualizar(int $id, string $nome, string $email, string $nivel, bool $ativo): bool
    {
        $stmt = $this->conn->prepare("UPDATE usuarios SET nome = ?, email = ?, nivel = ?, ativo = ? WHERE id = ?");
        return $stmt->execute([$nome, $email, $nivel, $ativo, $id]);
    }

    /**
     * Alterar senha
     */
    public function alterarSenha(int $id, string $novaSenha): bool
    {
        $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        return $stmt->execute([$senhaHash, $id]);
    }

    /**
     * Excluir usuário
     */
    public function excluir(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM usuarios WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Verificar se email já existe
     */
    public function emailExiste(string $email, ?int $excluirId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE email = ?";
        $params = [$email];

        if ($excluirId) {
            $sql .= " AND id != ?";
            $params[] = $excluirId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
}
