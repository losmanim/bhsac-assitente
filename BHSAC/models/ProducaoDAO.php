<?php
/**
 * Data Access Object (DAO) para Módulo de Produção e Serviços
 * Responsável pelas operações de itens e registros operacionais
 */

require_once __DIR__ . '/../config/database.php';

class ProducaoDAO
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Listar todos os itens ou filtrados por categoria
     */
    public function listarItens($categoria = null)
    {
        try {
            $sql = "SELECT * FROM itens_gestao WHERE ativo = TRUE";
            $params = [];

            if ($categoria) {
                $sql .= " AND categoria = :categoria";
                $params[':categoria'] = $categoria;
            }

            $sql .= " ORDER BY categoria, nome";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao listar itens: " . $e->getMessage());
        }
    }

    /**
     * Registrar uma nova operação (Produção, Venda, Consumo, Serviço)
     */
    public function getById($id)
    {
        try {
            $sql = "SELECT r.*, i.nome as item_nome, i.unidade, i.categoria 
                    FROM registros_operacionais r
                    INNER JOIN itens_gestao i ON r.item_id = i.id
                    WHERE r.id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Erro ao obter registro: " . $e->getMessage());
        }
    }

    public function registrarOperacao($dados)
    {
        try {
            $sql = "INSERT INTO registros_operacionais (item_id, tipo_operacao, quantidade, valor_total, data_operacao, observacoes) 
                    VALUES (:item_id, :tipo_operacao, :quantidade, :valor_total, :data_operacao, :observacoes)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':item_id' => $dados['item_id'],
                ':tipo_operacao' => $dados['tipo_operacao'],
                ':quantidade' => $dados['quantidade'],
                ':valor_total' => $dados['valor_total'] ?: 0,
                ':data_operacao' => $dados['data_operacao'],
                ':observacoes' => $dados['observacoes'] ?: null
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Erro ao registrar operação: " . $e->getMessage());
        }
    }

    public function atualizarOperacao($id, $dados)
    {
        try {
            $sql = "UPDATE registros_operacionais 
                    SET item_id = :item_id, 
                        tipo_operacao = :tipo_operacao, 
                        quantidade = :quantidade, 
                        valor_total = :valor_total, 
                        data_operacao = :data_operacao, 
                        observacoes = :observacoes
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':item_id' => $dados['item_id'],
                ':tipo_operacao' => $dados['tipo_operacao'],
                ':quantidade' => $dados['quantidade'],
                ':valor_total' => $dados['valor_total'] ?: 0,
                ':data_operacao' => $dados['data_operacao'],
                ':observacoes' => $dados['observacoes'] ?: null
            ]);

            return true;
        } catch (PDOException $e) {
            throw new Exception("Erro ao atualizar operação: " . $e->getMessage());
        }
    }

    public function excluirOperacao($id)
    {
        try {
            $sql = "DELETE FROM registros_operacionais WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erro ao excluir operação: " . $e->getMessage());
        }
    }

    /**
     * Listar registros operacionais com filtros
     */
    public function listarRegistros($filtros = [])
    {
        try {
            $sql = "SELECT r.*, i.nome as item_nome, i.categoria, i.unidade 
                    FROM registros_operacionais r
                    INNER JOIN itens_gestao i ON r.item_id = i.id
                    WHERE 1=1";

            $params = [];

            if (!empty($filtros['data_inicio'])) {
                $sql .= " AND r.data_operacao >= :data_inicio";
                $params[':data_inicio'] = $filtros['data_inicio'];
            }

            if (!empty($filtros['data_fim'])) {
                $sql .= " AND r.data_operacao <= :data_fim";
                $params[':data_fim'] = $filtros['data_fim'];
            }

            if (!empty($filtros['tipo_operacao'])) {
                $sql .= " AND r.tipo_operacao = :tipo_operacao";
                $params[':tipo_operacao'] = $filtros['tipo_operacao'];
            }

            $sql .= " ORDER BY r.data_operacao DESC, r.id DESC";

            if (!empty($filtros['limite'])) {
                $sql .= " LIMIT " . intval($filtros['limite']);
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao listar registros: " . $e->getMessage());
        }
    }

    /**
     * Obter resumo consolidado por item em um período
     */
    public function getResumoPorItem($dataInicio, $dataFim)
    {
        try {
            $sql = "SELECT i.nome, i.categoria, i.unidade, r.tipo_operacao, SUM(r.quantidade) as total_qtd, SUM(r.valor_total) as total_valor
                    FROM registros_operacionais r
                    INNER JOIN itens_gestao i ON r.item_id = i.id
                    WHERE r.data_operacao BETWEEN :inicio AND :fim
                    GROUP BY i.id, r.tipo_operacao
                    ORDER BY i.categoria, i.nome";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':inicio' => $dataInicio,
                ':fim' => $dataFim
            ]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao obter resumo: " . $e->getMessage());
        }
    }

    /**
     * Obter estatísticas agrupadas por categoria
     */
    public function getStatsPorCategoria($dataInicio, $dataFim)
    {
        try {
            $sql = "SELECT i.categoria, r.tipo_operacao, SUM(r.quantidade) as total_qtd, SUM(r.valor_total) as total_valor, COUNT(*) as total_registros
                    FROM registros_operacionais r
                    INNER JOIN itens_gestao i ON r.item_id = i.id
                    WHERE r.data_operacao BETWEEN :inicio AND :fim
                    GROUP BY i.categoria, r.tipo_operacao
                    ORDER BY i.categoria";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':inicio' => $dataInicio, ':fim' => $dataFim]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao obter estatísticas por categoria: " . $e->getMessage());
        }
    }

    /**
     * Obter estatísticas semanais (Produção e Vendas)
     */
    public function getStatsSemanais($dataInicio, $dataFim)
    {
        try {
            // YEARWEEK(data, 1) para começar na segunda-feira
            $sql = "SELECT YEARWEEK(data_operacao, 1) as semana, 
                           MIN(data_operacao) as data_inicio_semana,
                           tipo_operacao, 
                           SUM(quantidade) as total_qtd,
                           SUM(valor_total) as total_valor
                    FROM registros_operacionais
                    WHERE data_operacao BETWEEN :inicio AND :fim
                    GROUP BY semana, tipo_operacao
                    ORDER BY semana ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':inicio' => $dataInicio, ':fim' => $dataFim]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao obter estatísticas semanais: " . $e->getMessage());
        }
    }

    /**
     * Obter estatísticas mensais
     */
    public function getStatsMensais($dataInicio, $dataFim)
    {
        try {
            $sql = "SELECT DATE_FORMAT(data_operacao, '%Y-%m') as mes, 
                           tipo_operacao, 
                           SUM(quantidade) as total_qtd,
                           SUM(valor_total) as total_valor
                    FROM registros_operacionais
                    WHERE data_operacao BETWEEN :inicio AND :fim
                    GROUP BY mes, tipo_operacao
                    ORDER BY mes ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':inicio' => $dataInicio, ':fim' => $dataFim]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao obter estatísticas mensais: " . $e->getMessage());
        }
    }

    /**
     * Obter produção diária detalhada por item
     */
    public function getProducaoDiaria($data)
    {
        try {
            $sql = "SELECT item_id, quantidade
                    FROM registros_operacionais
                    WHERE data_operacao = :data
                    AND tipo_operacao = 'Produção'";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':data' => $data]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao obter produção diária: " . $e->getMessage());
        }
    }

    /**
     * Salvar produção diária em lote
     */
    public function salvarProducaoDiaria($data, $dados)
    {
        try {
            $this->db->beginTransaction();

            // 1. Limpar registros de produção existentes para esta data
            $sqlDelete = "DELETE FROM registros_operacionais 
                          WHERE data_operacao = :data 
                          AND tipo_operacao = 'Produção'";
            $stmtDelete = $this->db->prepare($sqlDelete);
            $stmtDelete->execute([':data' => $data]);

            // 2. Inserir novos registros
            $sqlInsert = "INSERT INTO registros_operacionais (item_id, tipo_operacao, quantidade, data_operacao) 
                          VALUES (:item_id, 'Produção', :quantidade, :data_operacao)";
            $stmtInsert = $this->db->prepare($sqlInsert);

            foreach ($dados as $reg) {
                if ($reg['quantidade'] > 0) {
                    $stmtInsert->execute([
                        ':item_id' => $reg['item_id'],
                        ':quantidade' => $reg['quantidade'],
                        ':data_operacao' => $data
                    ]);
                }
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new Exception("Erro ao salvar produção diária: " . $e->getMessage());
        }
    }

    /**
     * Cadastrar um novo item (Artefato, Serviço ou Material)
     */
    public function cadastrarItem($dados)
    {
        try {
            $sql = "INSERT INTO itens_gestao (nome, categoria, unidade, preco_referencia) 
                    VALUES (:nome, :categoria, :unidade, :preco_referencia)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nome' => $dados['nome'],
                ':categoria' => $dados['categoria'],
                ':unidade' => $dados['unidade'],
                ':preco_referencia' => $dados['preco_referencia'] ?: 0
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Erro ao cadastrar item: " . $e->getMessage());
        }
    }

    // ==========================================
    // CÁLCULO DE CONSUMO DE MATERIAIS NA PRODUÇÃO
    // ==========================================

    /**
     * Calcular materiais consumidos baseado na produção
     * Usa a composição de materiais (BOM) para calcular
     */
    public function calcularConsumoProducao($dataInicio, $dataFim)
    {
        try {
            $sql = "SELECT 
                        cm.material_id,
                        m.nome as material_nome,
                        m.unidade as material_unidade,
                        m.preco_referencia,
                        SUM(ro.quantidade * cm.consumo_liquido * (1 + cm.percentual_perda / 100)) as consumo_total,
                        SUM(ro.quantidade * cm.consumo_liquido * (1 + cm.percentual_perda / 100) * m.preco_referencia) as custo_total
                    FROM registros_operacionais ro
                    INNER JOIN itens_gestao p ON ro.item_id = p.id
                    INNER JOIN composicao_materiais cm ON cm.peca_id = ro.item_id AND cm.ativo = TRUE
                    INNER JOIN itens_gestao m ON cm.material_id = m.id
                    WHERE ro.tipo_operacao = 'Produção'
                    AND ro.data_operacao BETWEEN :inicio AND :fim
                    GROUP BY cm.material_id, m.nome, m.unidade, m.preco_referencia
                    ORDER BY m.nome";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':inicio' => $dataInicio, ':fim' => $dataFim]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao calcular consumo: " . $e->getMessage());
        }
    }

    /**
     * Calcular consumo do controle diário (turno)
     */
    public function calcularConsumoControleDiario($data, $turno = null)
    {
        try {
            $sql = "SELECT 
                        cm.material_id,
                        m.nome as material_nome,
                        m.unidade as material_unidade,
                        m.preco_referencia,
                        SUM((cd.quantidade_produzida - cd.quantidade_refugo) * cm.consumo_liquido * (1 + cm.percentual_perda / 100)) as consumo_total,
                        SUM((cd.quantidade_produzida - cd.quantidade_refugo) * cm.consumo_liquido * (1 + cm.percentual_perda / 100) * m.preco_referencia) as custo_total
                    FROM controle_diario cd
                    INNER JOIN composicao_materiais cm ON cm.peca_id = cd.item_id AND cm.ativo = TRUE
                    INNER JOIN itens_gestao m ON cm.material_id = m.id
                    WHERE cd.data_producao = :data";
            
            $params = [':data' => $data];
            
            if ($turno) {
                $sql .= " AND cd.turno = :turno";
                $params[':turno'] = $turno;
            }
            
            $sql .= " GROUP BY cm.material_id, m.nome, m.unidade, m.preco_referencia ORDER BY m.nome";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao calcular consumo do controle diário: " . $e->getMessage());
        }
    }

    /**
     * Resumo de consumo mensal por material
     */
    public function getResumoConsumoMensal($mes, $ano)
    {
        try {
            $sql = "SELECT 
                        m.id as material_id,
                        m.nome as material_nome,
                        m.unidade,
                        m.preco_referencia,
                        COALESCE(SUM((cd.quantidade_produzida - cd.quantidade_refugo) * cm.consumo_liquido * (1 + cm.percentual_perda / 100)), 0) as consumo_calculado,
                        COALESCE(SUM((cd.quantidade_produzida - cd.quantidade_refugo) * cm.consumo_liquido * (1 + cm.percentual_perda / 100) * m.preco_referencia), 0) as custo_calculado
                    FROM itens_gestao m
                    LEFT JOIN composicao_materiais cm ON cm.material_id = m.id AND cm.ativo = TRUE
                    LEFT JOIN controle_diario cd ON cd.item_id = cm.peca_id 
                        AND MONTH(cd.data_producao) = :mes 
                        AND YEAR(cd.data_producao) = :ano
                    WHERE m.categoria = 'Material' AND m.ativo = TRUE
                    GROUP BY m.id, m.nome, m.unidade, m.preco_referencia
                    ORDER BY m.nome";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':mes' => $mes, ':ano' => $ano]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao obter resumo de consumo: " . $e->getMessage());
        }
    }

    // ==========================================
    // CONTROLE DIÁRIO MELHORADO
    // ==========================================

    /**
     * Salvar registro de controle diário detalhado
     */
    public function salvarControleDiario($dados)
    {
        try {
            $sql = "INSERT INTO controle_diario 
                    (data_producao, turno, operador, item_id, quantidade_planejada, 
                     quantidade_produzida, quantidade_refugo, hora_inicio, hora_fim, 
                     tempo_parada, motivo_parada, observacoes, usuario_id)
                    VALUES 
                    (:data_producao, :turno, :operador, :item_id, :quantidade_planejada, 
                     :quantidade_produzida, :quantidade_refugo, :hora_inicio, :hora_fim, 
                     :tempo_parada, :motivo_parada, :observacoes, :usuario_id)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':data_producao' => $dados['data_producao'],
                ':turno' => $dados['turno'] ?? 'Manhã',
                ':operador' => $dados['operador'] ?? null,
                ':item_id' => $dados['item_id'],
                ':quantidade_planejada' => $dados['quantidade_planejada'] ?? 0,
                ':quantidade_produzida' => $dados['quantidade_produzida'],
                ':quantidade_refugo' => $dados['quantidade_refugo'] ?? 0,
                ':hora_inicio' => $dados['hora_inicio'] ?? null,
                ':hora_fim' => $dados['hora_fim'] ?? null,
                ':tempo_parada' => $dados['tempo_parada'] ?? 0,
                ':motivo_parada' => $dados['motivo_parada'] ?? null,
                ':observacoes' => $dados['observacoes'] ?? null,
                ':usuario_id' => $dados['usuario_id'] ?? null
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Erro ao salvar controle diário: " . $e->getMessage());
        }
    }

    /**
     * Atualizar registro de controle diário
     */
    public function atualizarControleDiario($id, $dados)
    {
        try {
            $sql = "UPDATE controle_diario SET 
                    turno = :turno,
                    operador = :operador,
                    quantidade_planejada = :quantidade_planejada,
                    quantidade_produzida = :quantidade_produzida,
                    quantidade_refugo = :quantidade_refugo,
                    hora_inicio = :hora_inicio,
                    hora_fim = :hora_fim,
                    tempo_parada = :tempo_parada,
                    motivo_parada = :motivo_parada,
                    observacoes = :observacoes
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':turno' => $dados['turno'] ?? 'Manhã',
                ':operador' => $dados['operador'] ?? null,
                ':quantidade_planejada' => $dados['quantidade_planejada'] ?? 0,
                ':quantidade_produzida' => $dados['quantidade_produzida'],
                ':quantidade_refugo' => $dados['quantidade_refugo'] ?? 0,
                ':hora_inicio' => $dados['hora_inicio'] ?? null,
                ':hora_fim' => $dados['hora_fim'] ?? null,
                ':tempo_parada' => $dados['tempo_parada'] ?? 0,
                ':motivo_parada' => $dados['motivo_parada'] ?? null,
                ':observacoes' => $dados['observacoes'] ?? null
            ]);

            return true;
        } catch (PDOException $e) {
            throw new Exception("Erro ao atualizar controle diário: " . $e->getMessage());
        }
    }

    /**
     * Listar controle diário por data
     */
    public function listarControleDiario($data, $turno = null)
    {
        try {
            $sql = "SELECT cd.*, ig.nome as item_nome, ig.unidade,
                    (cd.quantidade_produzida - cd.quantidade_refugo) as quantidade_liquida,
                    CASE WHEN cd.quantidade_planejada > 0 
                         THEN ROUND((cd.quantidade_produzida / cd.quantidade_planejada) * 100, 1) 
                         ELSE 0 END as eficiencia
                    FROM controle_diario cd
                    INNER JOIN itens_gestao ig ON cd.item_id = ig.id
                    WHERE cd.data_producao = :data";
            $params = [':data' => $data];

            if ($turno) {
                $sql .= " AND cd.turno = :turno";
                $params[':turno'] = $turno;
            }

            $sql .= " ORDER BY cd.turno, ig.nome";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao listar controle diário: " . $e->getMessage());
        }
    }

    /**
     * Obter resumo diário
     */
    public function getResumoDiario($data)
    {
        try {
            $sql = "SELECT 
                    turno,
                    COUNT(DISTINCT item_id) as qtd_itens,
                    SUM(quantidade_planejada) as total_planejado,
                    SUM(quantidade_produzida) as total_produzido,
                    SUM(quantidade_refugo) as total_refugo,
                    SUM(quantidade_produzida - quantidade_refugo) as total_liquido,
                    SUM(tempo_parada) as total_paradas,
                    CASE WHEN SUM(quantidade_planejada) > 0 
                         THEN ROUND((SUM(quantidade_produzida) / SUM(quantidade_planejada)) * 100, 1) 
                         ELSE 0 END as eficiencia_geral
                    FROM controle_diario
                    WHERE data_producao = :data
                    GROUP BY turno";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':data' => $data]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao obter resumo diário: " . $e->getMessage());
        }
    }

    /**
     * Excluir registro de controle diário
     */
    public function excluirControleDiario($id)
    {
        try {
            $sql = "DELETE FROM controle_diario WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erro ao excluir controle diário: " . $e->getMessage());
        }
    }

    /**
     * Salvar controle diário em lote (grade simplificada)
     */
    public function salvarControleDiarioLote($data, $turno, $registros)
    {
        try {
            $this->db->beginTransaction();

            // Limpar registros existentes para esta data e turno
            $sqlDelete = "DELETE FROM controle_diario WHERE data_producao = :data AND turno = :turno";
            $stmtDelete = $this->db->prepare($sqlDelete);
            $stmtDelete->execute([':data' => $data, ':turno' => $turno]);

            // Inserir novos registros
            $sqlInsert = "INSERT INTO controle_diario 
                          (data_producao, turno, item_id, quantidade_planejada, quantidade_produzida, quantidade_refugo)
                          VALUES (:data, :turno, :item_id, :planejada, :produzida, :refugo)";
            $stmtInsert = $this->db->prepare($sqlInsert);

            foreach ($registros as $reg) {
                if (($reg['quantidade_produzida'] ?? 0) > 0 || ($reg['quantidade_planejada'] ?? 0) > 0) {
                    $stmtInsert->execute([
                        ':data' => $data,
                        ':turno' => $turno,
                        ':item_id' => $reg['item_id'],
                        ':planejada' => $reg['quantidade_planejada'] ?? 0,
                        ':produzida' => $reg['quantidade_produzida'] ?? 0,
                        ':refugo' => $reg['quantidade_refugo'] ?? 0
                    ]);
                }
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new Exception("Erro ao salvar controle diário em lote: " . $e->getMessage());
        }
    }
}
