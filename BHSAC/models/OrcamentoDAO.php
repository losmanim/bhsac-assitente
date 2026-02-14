<?php
/**
 * Data Access Object (DAO) para Módulo de Orçamentos
 * Sistema de Gestão - BHSAC
 */

require_once __DIR__ . '/../config/database.php';

class OrcamentoDAO
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Gerar próximo número de orçamento
     */
    public function gerarNumero()
    {
        $ano = date('Y');
        $mes = date('m');
        
        $sql = "SELECT COUNT(*) + 1 as proximo FROM orcamentos 
                WHERE YEAR(data_emissao) = :ano AND MONTH(data_emissao) = :mes";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ano' => $ano, ':mes' => $mes]);
        $result = $stmt->fetch();
        
        return sprintf('%s%s-%04d', $ano, $mes, $result['proximo']);
    }

    /**
     * Criar novo orçamento
     */
    public function criar($dados)
    {
        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO orcamentos (numero, data_emissao, data_validade, cliente_nome, 
                    cliente_documento, cliente_contato, cliente_endereco, subtotal, desconto, 
                    valor_total, condicoes_pagamento, observacoes, usuario_id) 
                    VALUES (:numero, :data_emissao, :data_validade, :cliente_nome, 
                    :cliente_documento, :cliente_contato, :cliente_endereco, :subtotal, :desconto, 
                    :valor_total, :condicoes_pagamento, :observacoes, :usuario_id)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':numero' => $dados['numero'],
                ':data_emissao' => $dados['data_emissao'],
                ':data_validade' => $dados['data_validade'],
                ':cliente_nome' => $dados['cliente_nome'],
                ':cliente_documento' => $dados['cliente_documento'] ?? null,
                ':cliente_contato' => $dados['cliente_contato'] ?? null,
                ':cliente_endereco' => $dados['cliente_endereco'] ?? null,
                ':subtotal' => $dados['subtotal'],
                ':desconto' => $dados['desconto'] ?? 0,
                ':valor_total' => $dados['valor_total'],
                ':condicoes_pagamento' => $dados['condicoes_pagamento'] ?? null,
                ':observacoes' => $dados['observacoes'] ?? null,
                ':usuario_id' => $dados['usuario_id'] ?? null
            ]);

            $orcamentoId = $this->db->lastInsertId();

            // Inserir itens
            if (!empty($dados['itens'])) {
                $this->inserirItens($orcamentoId, $dados['itens']);
            }

            $this->db->commit();
            return $orcamentoId;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new Exception("Erro ao criar orçamento: " . $e->getMessage());
        }
    }

    /**
     * Inserir itens do orçamento
     */
    private function inserirItens($orcamentoId, $itens)
    {
        $sql = "INSERT INTO orcamento_itens (orcamento_id, item_id, descricao, quantidade, 
                unidade, valor_unitario, valor_total) 
                VALUES (:orcamento_id, :item_id, :descricao, :quantidade, 
                :unidade, :valor_unitario, :valor_total)";
        $stmt = $this->db->prepare($sql);

        foreach ($itens as $item) {
            $stmt->execute([
                ':orcamento_id' => $orcamentoId,
                ':item_id' => $item['item_id'] ?? null,
                ':descricao' => $item['descricao'],
                ':quantidade' => $item['quantidade'],
                ':unidade' => $item['unidade'],
                ':valor_unitario' => $item['valor_unitario'],
                ':valor_total' => $item['quantidade'] * $item['valor_unitario']
            ]);
        }
    }

    /**
     * Atualizar orçamento
     */
    public function atualizar($id, $dados)
    {
        try {
            $this->db->beginTransaction();

            $sql = "UPDATE orcamentos SET 
                    data_validade = :data_validade,
                    cliente_nome = :cliente_nome,
                    cliente_documento = :cliente_documento,
                    cliente_contato = :cliente_contato,
                    cliente_endereco = :cliente_endereco,
                    subtotal = :subtotal,
                    desconto = :desconto,
                    valor_total = :valor_total,
                    condicoes_pagamento = :condicoes_pagamento,
                    observacoes = :observacoes,
                    status = :status
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':data_validade' => $dados['data_validade'],
                ':cliente_nome' => $dados['cliente_nome'],
                ':cliente_documento' => $dados['cliente_documento'] ?? null,
                ':cliente_contato' => $dados['cliente_contato'] ?? null,
                ':cliente_endereco' => $dados['cliente_endereco'] ?? null,
                ':subtotal' => $dados['subtotal'],
                ':desconto' => $dados['desconto'] ?? 0,
                ':valor_total' => $dados['valor_total'],
                ':condicoes_pagamento' => $dados['condicoes_pagamento'] ?? null,
                ':observacoes' => $dados['observacoes'] ?? null,
                ':status' => $dados['status'] ?? 'Pendente'
            ]);

            // Atualizar itens
            if (isset($dados['itens'])) {
                $this->db->exec("DELETE FROM orcamento_itens WHERE orcamento_id = $id");
                $this->inserirItens($id, $dados['itens']);
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new Exception("Erro ao atualizar orçamento: " . $e->getMessage());
        }
    }

    /**
     * Obter orçamento por ID
     */
    public function getById($id)
    {
        try {
            $sql = "SELECT * FROM orcamentos WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $orcamento = $stmt->fetch();

            if ($orcamento) {
                $orcamento['itens'] = $this->getItens($id);
            }

            return $orcamento;
        } catch (PDOException $e) {
            throw new Exception("Erro ao obter orçamento: " . $e->getMessage());
        }
    }

    /**
     * Obter itens do orçamento
     */
    public function getItens($orcamentoId)
    {
        $sql = "SELECT oi.*, ig.nome as item_nome 
                FROM orcamento_itens oi 
                LEFT JOIN itens_gestao ig ON oi.item_id = ig.id 
                WHERE oi.orcamento_id = :orcamento_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':orcamento_id' => $orcamentoId]);
        return $stmt->fetchAll();
    }

    /**
     * Listar orçamentos com filtros
     */
    public function listar($filtros = [])
    {
        try {
            $sql = "SELECT o.*, 
                    (SELECT COUNT(*) FROM orcamento_itens WHERE orcamento_id = o.id) as qtd_itens
                    FROM orcamentos o WHERE 1=1";
            $params = [];

            if (!empty($filtros['status'])) {
                $sql .= " AND o.status = :status";
                $params[':status'] = $filtros['status'];
            }

            if (!empty($filtros['data_inicio'])) {
                $sql .= " AND o.data_emissao >= :data_inicio";
                $params[':data_inicio'] = $filtros['data_inicio'];
            }

            if (!empty($filtros['data_fim'])) {
                $sql .= " AND o.data_emissao <= :data_fim";
                $params[':data_fim'] = $filtros['data_fim'];
            }

            if (!empty($filtros['cliente'])) {
                $sql .= " AND o.cliente_nome LIKE :cliente";
                $params[':cliente'] = '%' . $filtros['cliente'] . '%';
            }

            $sql .= " ORDER BY o.data_emissao DESC, o.id DESC";

            if (!empty($filtros['limite'])) {
                $sql .= " LIMIT " . intval($filtros['limite']);
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao listar orçamentos: " . $e->getMessage());
        }
    }

    /**
     * Atualizar status do orçamento
     */
    public function atualizarStatus($id, $status)
    {
        try {
            $sql = "UPDATE orcamentos SET status = :status WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id, ':status' => $status]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erro ao atualizar status: " . $e->getMessage());
        }
    }

    /**
     * Excluir orçamento
     */
    public function excluir($id)
    {
        try {
            $sql = "DELETE FROM orcamentos WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erro ao excluir orçamento: " . $e->getMessage());
        }
    }

    /**
     * Estatísticas de orçamentos
     */
    public function getEstatisticas($dataInicio = null, $dataFim = null)
    {
        try {
            $dataInicio = $dataInicio ?: date('Y-m-01');
            $dataFim = $dataFim ?: date('Y-m-t');

            $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Pendente' THEN 1 ELSE 0 END) as pendentes,
                    SUM(CASE WHEN status = 'Aprovado' THEN 1 ELSE 0 END) as aprovados,
                    SUM(CASE WHEN status = 'Recusado' THEN 1 ELSE 0 END) as recusados,
                    SUM(CASE WHEN status = 'Aprovado' THEN valor_total ELSE 0 END) as valor_aprovado,
                    SUM(valor_total) as valor_total
                    FROM orcamentos 
                    WHERE data_emissao BETWEEN :inicio AND :fim";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':inicio' => $dataInicio, ':fim' => $dataFim]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Erro ao obter estatísticas: " . $e->getMessage());
        }
    }
}
