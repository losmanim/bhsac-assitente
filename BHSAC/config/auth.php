<?php
/**
 * Sistema de Autenticação e Controle de Sessão
 * BHSAC - Sistema de Gestão
 */

session_start();

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../models/UsuarioDAO.php';

class Auth
{
    private static $niveis = [
        'admin' => 3,
        'gerente' => 2,
        'operador' => 1
    ];

    /**
     * Realizar login
     */
    public static function login(string $email, string $senha): array
    {
        $dao = new UsuarioDAO();
        $usuario = $dao->autenticar($email, $senha);

        if ($usuario) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_nivel'] = $usuario['nivel'];
            $_SESSION['logado'] = true;

            return ['sucesso' => true, 'usuario' => $usuario];
        }

        return ['sucesso' => false, 'erro' => 'Email ou senha inválidos'];
    }

    /**
     * Realizar logout
     */
    public static function logout(): void
    {
        session_unset();
        session_destroy();
    }

    /**
     * Verificar se está logado
     */
    public static function estaLogado(): bool
    {
        return isset($_SESSION['logado']) && $_SESSION['logado'] === true;
    }

    /**
     * Obter usuário logado
     */
    public static function getUsuario(): ?array
    {
        if (!self::estaLogado()) {
            return null;
        }

        return [
            'id' => $_SESSION['usuario_id'],
            'nome' => $_SESSION['usuario_nome'],
            'email' => $_SESSION['usuario_email'],
            'nivel' => $_SESSION['usuario_nivel']
        ];
    }

    /**
     * Verificar se tem permissão para determinado nível
     */
    public static function temPermissao(string $nivelMinimo): bool
    {
        if (!self::estaLogado()) {
            return false;
        }

        $nivelUsuario = $_SESSION['usuario_nivel'];
        return self::$niveis[$nivelUsuario] >= self::$niveis[$nivelMinimo];
    }

    /**
     * Verificar se é admin
     */
    public static function ehAdmin(): bool
    {
        return self::temPermissao('admin');
    }

    /**
     * Verificar se é gerente ou superior
     */
    public static function ehGerente(): bool
    {
        return self::temPermissao('gerente');
    }

    /**
     * Exigir login - redireciona se não estiver logado
     */
    public static function exigirLogin(): void
    {
        if (!self::estaLogado()) {
            header('Location: login.php');
            exit;
        }
    }

    /**
     * Exigir nível mínimo - redireciona se não tiver permissão
     */
    public static function exigirNivel(string $nivelMinimo): void
    {
        self::exigirLogin();

        if (!self::temPermissao($nivelMinimo)) {
            header('Location: index.php?erro=sem_permissao');
            exit;
        }
    }
}
