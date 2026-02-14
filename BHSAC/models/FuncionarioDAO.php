<?php
/**
 * Data Access Object (DAO) para Funcionários
 * Responsável por todas as operações no banco de dados
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../funcionarios.php';

class FuncionarioDAO
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Cadastrar novo funcionário
     */
    public function cadastrar(Funcionario $funcionario, $anexos = [])
    {
        try {
            $sql = "INSERT INTO funcionarios (nome, cargo, salario, data_nascimento, cpf, rg, cnh, endereco, email, telefone, data_contratacao, data_rescisao, observacoes, anexo_cpf, anexo_rg, anexo_cnh, anexo_nrs, anexo_certificados) 
                    VALUES (:nome, :cargo, :salario, :data_nascimento, :cpf, :rg, :cnh, :endereco, :email, :telefone, :data_contratacao, :data_rescisao, :observacoes, :anexo_cpf, :anexo_rg, :anexo_cnh, :anexo_nrs, :anexo_certificados)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nome' => $funcionario->getNome(),
                ':cargo' => $funcionario->getCargo(),
                ':salario' => $funcionario->getSalario(),
                ':data_nascimento' => $funcionario->getDataNascimento(),
                ':cpf' => $funcionario->getNdocumentos(),
                ':rg' => $funcionario->getRg() ?: null,
                ':cnh' => $funcionario->getCnh() ?: null,
                ':endereco' => $funcionario->getEndereco(),
                ':email' => $funcionario->getEmail(),
                ':telefone' => $funcionario->getTelefone(),
                ':data_contratacao' => $funcionario->getDataContratacao() ?: null,
                ':data_rescisao' => $funcionario->getDataRescisao() ?: null,
                ':observacoes' => $funcionario->getObservacoes() ?: null,
                ':anexo_cpf' => $anexos['cpf'] ?? null,
                ':anexo_rg' => $anexos['rg'] ?? null,
                ':anexo_cnh' => $anexos['cnh'] ?? null,
                ':anexo_nrs' => $anexos['nrs'] ?? null,
                ':anexo_certificados' => $anexos['certificados'] ?? null
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Erro ao cadastrar funcionário: " . $e->getMessage());
        }
    }

    /**
     * Listar todos os funcionários ativos
     */
    public function listarTodos()
    {
        try {
            $sql = "SELECT * FROM funcionarios WHERE ativo = TRUE ORDER BY nome";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao listar funcionários: " . $e->getMessage());
        }
    }

    /**
     * Buscar funcionário por ID
     */
    public function buscarPorId($id)
    {
        try {
            $sql = "SELECT * FROM funcionarios WHERE id = :id AND ativo = TRUE";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Erro ao buscar funcionário: " . $e->getMessage());
        }
    }

    /**
     * Buscar funcionários por termo (nome, cargo, cpf ou email)
     */
    public function buscar($termo)
    {
        try {
            $sql = "SELECT * FROM funcionarios 
                    WHERE ativo = TRUE 
                    AND (nome LIKE :termo 
                         OR cargo LIKE :termo 
                         OR cpf LIKE :termo 
                         OR email LIKE :termo)
                    ORDER BY nome";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':termo' => '%' . $termo . '%']);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao buscar funcionários: " . $e->getMessage());
        }
    }

    /**
     * Atualizar funcionário
     */
    public function atualizar($id, Funcionario $funcionario, $anexos = [])
    {
        try {
            // Construir SQL dinamicamente para atualizar apenas anexos que foram enviados
            $campos = [
                'nome = :nome',
                'cargo = :cargo',
                'salario = :salario',
                'data_nascimento = :data_nascimento',
                'cpf = :cpf',
                'rg = :rg',
                'cnh = :cnh',
                'endereco = :endereco',
                'email = :email',
                'telefone = :telefone',
                'data_contratacao = :data_contratacao',
                'data_rescisao = :data_rescisao',
                'observacoes = :observacoes'
            ];

            $params = [
                ':id' => $id,
                ':nome' => $funcionario->getNome(),
                ':cargo' => $funcionario->getCargo(),
                ':salario' => $funcionario->getSalario(),
                ':data_nascimento' => $funcionario->getDataNascimento(),
                ':cpf' => $funcionario->getNdocumentos(),
                ':rg' => $funcionario->getRg() ?: null,
                ':cnh' => $funcionario->getCnh() ?: null,
                ':endereco' => $funcionario->getEndereco(),
                ':email' => $funcionario->getEmail(),
                ':telefone' => $funcionario->getTelefone(),
                ':data_contratacao' => $funcionario->getDataContratacao() ?: null,
                ':data_rescisao' => $funcionario->getDataRescisao() ?: null,
                ':observacoes' => $funcionario->getObservacoes() ?: null
            ];

            // Adicionar anexos apenas se foram enviados
            if (!empty($anexos['cpf'])) {
                $campos[] = 'anexo_cpf = :anexo_cpf';
                $params[':anexo_cpf'] = $anexos['cpf'];
            }
            if (!empty($anexos['rg'])) {
                $campos[] = 'anexo_rg = :anexo_rg';
                $params[':anexo_rg'] = $anexos['rg'];
            }
            if (!empty($anexos['cnh'])) {
                $campos[] = 'anexo_cnh = :anexo_cnh';
                $params[':anexo_cnh'] = $anexos['cnh'];
            }
            if (!empty($anexos['nrs'])) {
                $campos[] = 'anexo_nrs = :anexo_nrs';
                $params[':anexo_nrs'] = $anexos['nrs'];
            }
            if (!empty($anexos['certificados'])) {
                $campos[] = 'anexo_certificados = :anexo_certificados';
                $params[':anexo_certificados'] = $anexos['certificados'];
            }

            $sql = "UPDATE funcionarios SET " . implode(', ', $campos) . " WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            throw new Exception("Erro ao atualizar funcionário: " . $e->getMessage());
        }
    }

    /**
     * Registrar rescisão do funcionário
     */
    public function registrarRescisao($id, $dataRescisao, $observacoes = null)
    {
        try {
            $sql = "UPDATE funcionarios SET 
                    data_rescisao = :data_rescisao,
                    observacoes = CONCAT(IFNULL(observacoes, ''), :observacoes)
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':data_rescisao' => $dataRescisao,
                ':observacoes' => $observacoes ? "\n[Rescisão] " . $observacoes : ''
            ]);
        } catch (PDOException $e) {
            throw new Exception("Erro ao registrar rescisão: " . $e->getMessage());
        }
    }

    /**
     * Excluir funcionário (soft delete - apenas marca como inativo)
     */
    public function excluir($id)
    {
        try {
            $sql = "UPDATE funcionarios SET ativo = FALSE WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            throw new Exception("Erro ao excluir funcionário: " . $e->getMessage());
        }
    }

    /**
     * Buscar funcionário por CPF
     */
    public function buscarPorCpf($cpf)
    {
        try {
            $sql = "SELECT * FROM funcionarios WHERE cpf = :cpf AND ativo = TRUE";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':cpf' => $cpf]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Erro ao buscar funcionário: " . $e->getMessage());
        }
    }

    /**
     * Contar total de funcionários ativos
     */
    public function contarFuncionarios()
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM funcionarios WHERE ativo = TRUE";
            $stmt = $this->db->query($sql);
            $resultado = $stmt->fetch();
            return $resultado['total'];
        } catch (PDOException $e) {
            throw new Exception("Erro ao contar funcionários: " . $e->getMessage());
        }
    }

    /**
     * Listar funcionários ativos (com contrato ativo - sem data de rescisão)
     */
    public function listarAtivos()
    {
        try {
            $sql = "SELECT * FROM funcionarios 
                    WHERE ativo = TRUE AND data_rescisao IS NULL 
                    ORDER BY nome";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao listar funcionários ativos: " . $e->getMessage());
        }
    }

    /**
     * Listar funcionários com contrato encerrado
     */
    public function listarDesligados()
    {
        try {
            $sql = "SELECT * FROM funcionarios 
                    WHERE ativo = TRUE AND data_rescisao IS NOT NULL 
                    ORDER BY data_rescisao DESC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao listar funcionários desligados: " . $e->getMessage());
        }
    }

    /**
     * Calcular tempo de empresa de um funcionário
     */
    public function calcularTempoEmpresa($funcionario)
    {
        $dataContratacao = $funcionario['data_contratacao'] ?? null;
        $dataRescisao = $funcionario['data_rescisao'] ?? null;

        if (!$dataContratacao) {
            return 'Não informado';
        }

        $inicio = new DateTime($dataContratacao);
        $fim = $dataRescisao ? new DateTime($dataRescisao) : new DateTime();
        $intervalo = $inicio->diff($fim);

        $partes = [];
        if ($intervalo->y > 0) {
            $partes[] = $intervalo->y . ' ano' . ($intervalo->y > 1 ? 's' : '');
        }
        if ($intervalo->m > 0) {
            $partes[] = $intervalo->m . ' mês' . ($intervalo->m > 1 ? 'es' : '');
        }
        if ($intervalo->d > 0 && count($partes) < 2) {
            $partes[] = $intervalo->d . ' dia' . ($intervalo->d > 1 ? 's' : '');
        }

        return !empty($partes) ? implode(' e ', $partes) : 'Menos de 1 dia';
    }
}
