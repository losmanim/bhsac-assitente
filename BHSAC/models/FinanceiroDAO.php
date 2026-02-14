<?php
/**
 * Data Access Object (DAO) para Módulo Financeiro
 * Responsável por todas as operações de movimentações financeiras
 */

require_once __DIR__ . '/../config/database.php';

class FinanceiroDAO
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Cadastrar nova movimentação
     */
    public function cadastrarMovimentacao($dados)
    {
        try {
            $sql = "INSERT INTO movimentacoes (tipo, categoria_id, funcionario_id, descricao, valor, data_movimentacao, observacoes) 
                    VALUES (:tipo, :categoria_id, :funcionario_id, :descricao, :valor, :data_movimentacao, :observacoes)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tipo' => $dados['tipo'],
                ':categoria_id' => $dados['categoria_id'],
                ':funcionario_id' => $dados['funcionario_id'] ?: null,
                ':descricao' => $dados['descricao'],
                ':valor' => $dados['valor'],
                ':data_movimentacao' => $dados['data_movimentacao'],
                ':observacoes' => $dados['observacoes'] ?: null
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Erro ao cadastrar movimentação: " . $e->getMessage());
        }
    }

    /**
     * Listar movimentações com filtros
     */
    public function listarMovimentacoes($filtros = [])
    {
        try {
            $sql = "SELECT m.*, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone,
                           f.nome as funcionario_nome
                    FROM movimentacoes m
                    INNER JOIN categorias_financeiras c ON m.categoria_id = c.id
                    LEFT JOIN funcionarios f ON m.funcionario_id = f.id
                    WHERE m.ativo = TRUE";

            $params = [];

            // Filtro por tipo
            if (!empty($filtros['tipo'])) {
                $sql .= " AND m.tipo = :tipo";
                $params[':tipo'] = $filtros['tipo'];
            }

            // Filtro por categoria
            if (!empty($filtros['categoria_id'])) {
                $sql .= " AND m.categoria_id = :categoria_id";
                $params[':categoria_id'] = $filtros['categoria_id'];
            }

            // Filtro por período
            if (!empty($filtros['data_inicio'])) {
                $sql .= " AND m.data_movimentacao >= :data_inicio";
                $params[':data_inicio'] = $filtros['data_inicio'];
            }

            if (!empty($filtros['data_fim'])) {
                $sql .= " AND m.data_movimentacao <= :data_fim";
                $params[':data_fim'] = $filtros['data_fim'];
            }

            // Filtro por funcionário
            if (!empty($filtros['funcionario_id'])) {
                $sql .= " AND m.funcionario_id = :funcionario_id";
                $params[':funcionario_id'] = $filtros['funcionario_id'];
            }

            // Busca por termo
            if (!empty($filtros['busca'])) {
                $sql .= " AND (m.descricao LIKE :busca OR c.nome LIKE :busca)";
                $params[':busca'] = '%' . $filtros['busca'] . '%';
            }

            $sql .= " ORDER BY m.data_movimentacao DESC, m.id DESC";

            // Limite
            if (!empty($filtros['limite'])) {
                $sql .= " LIMIT " . intval($filtros['limite']);
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao listar movimentações: " . $e->getMessage());
        }
    }

    /**
     * Buscar movimentação por ID
     */
    public function buscarPorId($id)
    {
        try {
            $sql = "SELECT m.*, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone,
                           f.nome as funcionario_nome
                    FROM movimentacoes m
                    INNER JOIN categorias_financeiras c ON m.categoria_id = c.id
                    LEFT JOIN funcionarios f ON m.funcionario_id = f.id
                    WHERE m.id = :id AND m.ativo = TRUE";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Erro ao buscar movimentação: " . $e->getMessage());
        }
    }

    /**
     * Atualizar movimentação
     */
    public function atualizar($id, $dados)
    {
        try {
            $sql = "UPDATE movimentacoes SET 
                    tipo = :tipo,
                    categoria_id = :categoria_id,
                    funcionario_id = :funcionario_id,
                    descricao = :descricao,
                    valor = :valor,
                    data_movimentacao = :data_movimentacao,
                    observacoes = :observacoes
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':tipo' => $dados['tipo'],
                ':categoria_id' => $dados['categoria_id'],
                ':funcionario_id' => $dados['funcionario_id'] ?: null,
                ':descricao' => $dados['descricao'],
                ':valor' => $dados['valor'],
                ':data_movimentacao' => $dados['data_movimentacao'],
                ':observacoes' => $dados['observacoes'] ?: null
            ]);
        } catch (PDOException $e) {
            throw new Exception("Erro ao atualizar movimentação: " . $e->getMessage());
        }
    }

    /**
     * Excluir movimentação (soft delete)
     */
    public function excluir($id)
    {
        try {
            $sql = "UPDATE movimentacoes SET ativo = FALSE WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            throw new Exception("Erro ao excluir movimentação: " . $e->getMessage());
        }
    }

    /**
     * Listar categorias por tipo
     */
    public function listarCategorias($tipo = null)
    {
        try {
            $sql = "SELECT * FROM categorias_financeiras WHERE ativo = TRUE";
            $params = [];

            if ($tipo) {
                $sql .= " AND tipo = :tipo";
                $params[':tipo'] = $tipo;
            }

            $sql .= " ORDER BY nome";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao listar categorias: " . $e->getMessage());
        }
    }

    /**
     * Calcular total de entradas no período
     */
    public function calcularTotalEntradas($dataInicio = null, $dataFim = null)
    {
        try {
            $sql = "SELECT COALESCE(SUM(valor), 0) as total FROM movimentacoes 
                    WHERE tipo = 'entrada' AND ativo = TRUE";
            $params = [];

            if ($dataInicio) {
                $sql .= " AND data_movimentacao >= :data_inicio";
                $params[':data_inicio'] = $dataInicio;
            }

            if ($dataFim) {
                $sql .= " AND data_movimentacao <= :data_fim";
                $params[':data_fim'] = $dataFim;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $resultado = $stmt->fetch();
            return $resultado['total'];
        } catch (PDOException $e) {
            throw new Exception("Erro ao calcular entradas: " . $e->getMessage());
        }
    }

    /**
     * Calcular total de saídas no período
     */
    public function calcularTotalSaidas($dataInicio = null, $dataFim = null)
    {
        try {
            $sql = "SELECT COALESCE(SUM(valor), 0) as total FROM movimentacoes 
                    WHERE tipo = 'saida' AND ativo = TRUE";
            $params = [];

            if ($dataInicio) {
                $sql .= " AND data_movimentacao >= :data_inicio";
                $params[':data_inicio'] = $dataInicio;
            }

            if ($dataFim) {
                $sql .= " AND data_movimentacao <= :data_fim";
                $params[':data_fim'] = $dataFim;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $resultado = $stmt->fetch();
            return $resultado['total'];
        } catch (PDOException $e) {
            throw new Exception("Erro ao calcular saídas: " . $e->getMessage());
        }
    }

    /**
     * Calcular saldo no período
     */
    public function calcularSaldo($dataInicio = null, $dataFim = null)
    {
        $entradas = $this->calcularTotalEntradas($dataInicio, $dataFim);
        $saidas = $this->calcularTotalSaidas($dataInicio, $dataFim);
        return $entradas - $saidas;
    }

    /**
     * Contar movimentações no período
     */
    public function contarMovimentacoes($dataInicio = null, $dataFim = null)
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM movimentacoes WHERE ativo = TRUE";
            $params = [];

            if ($dataInicio) {
                $sql .= " AND data_movimentacao >= :data_inicio";
                $params[':data_inicio'] = $dataInicio;
            }

            if ($dataFim) {
                $sql .= " AND data_movimentacao <= :data_fim";
                $params[':data_fim'] = $dataFim;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $resultado = $stmt->fetch();
            return $resultado['total'];
        } catch (PDOException $e) {
            throw new Exception("Erro ao contar movimentações: " . $e->getMessage());
        }
    }

    /**
     * Obter resumo por categoria
     * @return array
     */
    public function getResumoPorCategoria($tipo, $dataInicio = null, $dataFim = null)
    {
        try {
            $sql = "SELECT c.nome, c.cor, c.icone, COALESCE(SUM(m.valor), 0) as total
                    FROM categorias_financeiras c
                    LEFT JOIN movimentacoes m ON c.id = m.categoria_id 
                        AND m.ativo = TRUE";

            $params = [':tipo' => $tipo];

            if ($dataInicio) {
                $sql .= " AND m.data_movimentacao >= :data_inicio";
                $params[':data_inicio'] = $dataInicio;
            }

            if ($dataFim) {
                $sql .= " AND m.data_movimentacao <= :data_fim";
                $params[':data_fim'] = $dataFim;
            }

            $sql .= " WHERE c.tipo = :tipo AND c.ativo = TRUE
                      GROUP BY c.id, c.nome, c.cor, c.icone
                      ORDER BY total DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao obter resumo: " . $e->getMessage());
        }
    }

    /**
     * Obter resumo mensal dos últimos 12 meses
     */
    public function getResumoMensal()
    {
        try {
            $sql = "SELECT 
                        DATE_FORMAT(data_movimentacao, '%Y-%m') as mes,
                        tipo,
                        SUM(valor) as total
                    FROM movimentacoes 
                    WHERE ativo = TRUE 
                        AND data_movimentacao >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                    GROUP BY mes, tipo
                    ORDER BY mes ASC";

            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao obter resumo mensal: " . $e->getMessage());
        }
    }

    /**
     * Listar funcionários ativos para select
     */
    public function listarFuncionariosAtivos()
    {
        try {
            $sql = "SELECT id, nome FROM funcionarios WHERE ativo = TRUE AND data_rescisao IS NULL ORDER BY nome";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao listar funcionários: " . $e->getMessage());
        }
    }

    /**
     * Listar funcionários ativos com salário para pagamento
     */
    public function listarFuncionariosComSalario()
    {
        try {
            $sql = "SELECT id, nome, salario FROM funcionarios WHERE ativo = TRUE AND data_rescisao IS NULL ORDER BY nome";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao listar funcionários: " . $e->getMessage());
        }
    }

    /**
     * Verificar se estamos no período de pagamento (dias 01-05 do mês)
     */
    public function isPeriodoPagamento()
    {
        $diaAtual = (int) date('d');
        return $diaAtual >= 1 && $diaAtual <= 10;
    }

    /**
     * Obter funcionários com pagamento pendente no mês atual
     * Retorna funcionários que não têm pagamento de salário registrado no mês atual
     */
    public function getFuncionariosPagamentoPendente()
    {
        try {
            $mesAtual = date('Y-m');

            // Buscar ID da categoria de Salários
            $sqlCategoria = "SELECT id FROM categorias_financeiras WHERE nome = 'Salários' AND tipo = 'saida' LIMIT 1";
            $stmtCategoria = $this->db->query($sqlCategoria);
            $categoria = $stmtCategoria->fetch();

            if (!$categoria) {
                return [];
            }

            $categoriaSalariosId = $categoria['id'];

            // Buscar funcionários ativos que NÃO têm pagamento de salário neste mês
            $sql = "SELECT f.id, f.nome, f.salario 
                    FROM funcionarios f 
                    WHERE f.ativo = TRUE 
                        AND f.data_rescisao IS NULL
                        AND f.id NOT IN (
                            SELECT DISTINCT m.funcionario_id 
                            FROM movimentacoes m 
                            WHERE m.categoria_id = :categoria_id 
                                AND m.ativo = TRUE
                                AND DATE_FORMAT(m.data_movimentacao, '%Y-%m') = :mes_atual
                                AND m.funcionario_id IS NOT NULL
                        )
                    ORDER BY f.nome";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':categoria_id' => $categoriaSalariosId,
                ':mes_atual' => $mesAtual
            ]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao verificar pagamentos pendentes: " . $e->getMessage());
        }
    }

    /**
     * Obter ID da categoria de Salários
     */
    public function getCategoriaSalariosId()
    {
        try {
            $sql = "SELECT id FROM categorias_financeiras WHERE nome = 'Salários' AND tipo = 'saida' LIMIT 1";
            $stmt = $this->db->query($sql);
            $categoria = $stmt->fetch();
            return $categoria ? $categoria['id'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Calcular total de salários pendentes
     */
    public function calcularTotalSalariosPendentes()
    {
        $pendentes = $this->getFuncionariosPagamentoPendente();
        $total = 0;
        foreach ($pendentes as $f) {
            $total += $f['salario'];
        }
        return $total;
    }

    /**
     * Cadastrar uma nova categoria financeira
     */
    public function cadastrarCategoria($dados)
    {
        try {
            $sql = "INSERT INTO categorias_financeiras (nome, tipo, cor, icone) 
                    VALUES (:nome, :tipo, :cor, :icone)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nome' => $dados['nome'],
                ':tipo' => $dados['tipo'],
                ':cor' => $dados['cor'] ?: '#6b7280',
                ':icone' => $dados['icone'] ?: 'bi-tag'
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Erro ao cadastrar categoria: " . $e->getMessage());
        }
    }
}
