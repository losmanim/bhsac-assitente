<?php
/**
 * Configuração de conexão com o banco de dados MySQL
 * Sistema de Gestão de Funcionários - BHSAC
 */

// Configurações do banco de dados
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');            // Porta padrão do MariaDB no LAMPP
define('DB_USER', 'bhsac_app');          // Novo usuário dedicado
define('DB_PASS', 'app123');             // Senha do usuário bhsac_app
define('DB_NAME', 'gestao_funcionarios');

/**
 * Classe de conexão com o banco de dados usando PDO
 */
class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Erro de conexão: " . $e->getMessage());
        }
    }

    // Padrão Singleton - garante apenas uma conexão
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    // Previne clonagem
    private function __clone()
    {
    }
}
