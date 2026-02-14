<?php
/**
 * Data Access Object (DAO) para Módulo de Consumo por Peça (BOM)
 * Sistema de Gestão - BHSAC
 */

require_once __DIR__ . '/../config/database.php';

class ConsumoDAO
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Listar composição de materiais de uma peça
     */
    public function listarComposicao($pecaId)
    {
        try {
            $sql = "SELECT * FROM vw_consumo_peca WHERE peca_id = :peca_id ORDER BY material_nome";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':peca_id' => $pecaId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao listar composição: " . $e->getMessage());
        }
    }

    /**
     * Obter custo total de uma peça
     */
    public function getCustoPeca($pecaId)
    {
        try {
            $sql = "SELECT * FROM vw_custo_total_peca WHERE peca_id = :peca_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':peca_id' => $pecaId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Erro ao obter custo: " . $e->getMessage());
        }
    }

    /**
     * Listar todas as peças com custo calculado
     */
    public function listarPecasComCusto()
    {
        try {
            $sql = "SELECT ig.id, ig.nome, ig.unidade, ig.preco_referencia,
                    COALESCE(vct.qtd_materiais, 0) as qtd_materiais,
                    COALESCE(vct.custo_total, 0) as custo_materiais
                    FROM itens_gestao ig
                    LEFT JOIN vw_custo_total_peca vct ON ig.id = vct.peca_id
                    WHERE ig.categoria = 'Artefato' AND ig.ativo = TRUE
                    ORDER BY ig.nome";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao listar peças: " . $e->getMessage());
        }
    }

    /**
     * Adicionar material à composição de uma peça
     */
    public function adicionarMaterial($dados)
    {
        try {
            $sql = "INSERT INTO composicao_materiais (peca_id, material_id, consumo_liquido, percentual_perda, observacoes)
                    VALUES (:peca_id, :material_id, :consumo_liquido, :percentual_perda, :observacoes)
                    ON DUPLICATE KEY UPDATE 
                    consumo_liquido = VALUES(consumo_liquido),
                    percentual_perda = VALUES(percentual_perda),
                    observacoes = VALUES(observacoes),
                    ativo = TRUE";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':peca_id' => $dados['peca_id'],
                ':material_id' => $dados['material_id'],
                ':consumo_liquido' => $dados['consumo_liquido'],
                ':percentual_perda' => $dados['percentual_perda'] ?? 0,
                ':observacoes' => $dados['observacoes'] ?? null
            ]);

            return $this->db->lastInsertId() ?: true;
        } catch (PDOException $e) {
            throw new Exception("Erro ao adicionar material: " . $e->getMessage());
        }
    }

    /**
     * Remover material da composição
     */
    public function removerMaterial($id)
    {
        try {
            $sql = "UPDATE composicao_materiais SET ativo = FALSE WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erro ao remover material: " . $e->getMessage());
        }
    }

    /**
     * Calcular consumo para uma quantidade de peças
     */
    public function calcularConsumo($pecaId, $quantidade)
    {
        try {
            $composicao = $this->listarComposicao($pecaId);
            $resultado = [];
            $custoTotal = 0;

            foreach ($composicao as $mat) {
                $consumoTotal = $mat['consumo_bruto'] * $quantidade;
                $custoMaterial = $mat['custo_material'] * $quantidade;
                $custoTotal += $custoMaterial;

                $resultado[] = [
                    'material_id' => $mat['material_id'],
                    'material_nome' => $mat['material_nome'],
                    'material_unidade' => $mat['material_unidade'],
                    'consumo_unitario' => $mat['consumo_bruto'],
                    'consumo_total' => $consumoTotal,
                    'custo_unitario' => $mat['custo_material'],
                    'custo_total' => $custoMaterial
                ];
            }

            return [
                'peca_id' => $pecaId,
                'quantidade' => $quantidade,
                'materiais' => $resultado,
                'custo_total' => $custoTotal
            ];
        } catch (PDOException $e) {
            throw new Exception("Erro ao calcular consumo: " . $e->getMessage());
        }
    }

    /**
     * Listar materiais disponíveis para composição
     */
    public function listarMateriais()
    {
        try {
            $sql = "SELECT id, nome, unidade, preco_referencia 
                    FROM itens_gestao 
                    WHERE categoria = 'Material' AND ativo = TRUE 
                    ORDER BY nome";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao listar materiais: " . $e->getMessage());
        }
    }

    /**
     * Atualizar preço de referência do material
     */
    public function atualizarPrecoMaterial($materialId, $preco)
    {
        try {
            $sql = "UPDATE itens_gestao SET preco_referencia = :preco WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $materialId, ':preco' => $preco]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erro ao atualizar preço: " . $e->getMessage());
        }
    }
}
