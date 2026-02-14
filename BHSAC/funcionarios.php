<?php

class Funcionario
{
    // Atributos
    public $nome;
    public $cargo;
    private $salario;
    private $dataNascimento;
    private $Ndocumentos;
    private $rg;
    private $cnh;
    private $endereco;
    private $email;
    private $telefone;
    private $dataContratacao;
    private $dataRescisao;
    private $observacoes;
    private $anexoCpf;
    private $anexoRg;
    private $anexoCnh;
    private $anexoNrs;
    private $anexoCertificados;

    // Métodos
    public function mostrarFuncionario()
    {
        return [
            'nome' => $this->getNome(),
            'cargo' => $this->getCargo(),
            'salario' => $this->getSalario(),
            'dataNascimento' => $this->getDataNascimento(),
            'cpf' => $this->getNdocumentos(),
            'rg' => $this->getRg(),
            'cnh' => $this->getCnh(),
            'endereco' => $this->getEndereco(),
            'email' => $this->getEmail(),
            'telefone' => $this->getTelefone(),
            'dataContratacao' => $this->getDataContratacao(),
            'dataRescisao' => $this->getDataRescisao(),
            'observacoes' => $this->getObservacoes(),
            'anexoCpf' => $this->getAnexoCpf(),
            'anexoRg' => $this->getAnexoRg(),
            'anexoCnh' => $this->getAnexoCnh(),
            'anexoNrs' => $this->getAnexoNrs(),
            'anexoCertificados' => $this->getAnexoCertificados()
        ];
    }

    public function cadastrarFuncionario()
    {
        $this->nome = $_POST['nome'] ?? '';
        $this->cargo = $_POST['cargo'] ?? '';
        $this->salario = $_POST['salario'] ?? 0;
        $this->dataNascimento = $_POST['data-nascimento'] ?? null;
        $this->Ndocumentos = $_POST['num-cpf'] ?? '';
        $this->rg = $_POST['rg'] ?? '';
        $this->cnh = $_POST['cnh'] ?? '';
        $this->endereco = $_POST['endereco'] ?? '';
        $this->email = $_POST['email'] ?? '';
        $this->telefone = $_POST['telefone'] ?? '';
        $this->dataContratacao = $_POST['data-contratacao'] ?? null;
        $this->dataRescisao = $_POST['data-rescisao'] ?? null;
        $this->observacoes = $_POST['observacoes'] ?? '';
        $this->anexoCpf = '';
        $this->anexoRg = '';
        $this->anexoCnh = '';
        $this->anexoNrs = '';
        $this->anexoCertificados = '';
    }

    // Método Construtor
    public function __construct()
    {
        if (isset($_POST['nome'])) {
            $this->cadastrarFuncionario();
        }
    }

    // Calcular tempo de empresa
    public function calcularTempoEmpresa()
    {
        if (!$this->dataContratacao) {
            return null;
        }
        $inicio = new DateTime($this->dataContratacao);
        $fim = $this->dataRescisao ? new DateTime($this->dataRescisao) : new DateTime();
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

    // Verificar se está ativo (não tem data de rescisão)
    public function estaAtivo()
    {
        return empty($this->dataRescisao);
    }

    // Getters
    public function getNome()
    {
        return $this->nome;
    }

    public function getCargo()
    {
        return $this->cargo;
    }

    public function getSalario()
    {
        return $this->salario;
    }

    public function getDataNascimento()
    {
        return $this->dataNascimento;
    }

    public function getNdocumentos()
    {
        return $this->Ndocumentos;
    }

    public function getEndereco()
    {
        return $this->endereco;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getTelefone()
    {
        return $this->telefone;
    }

    public function getDataContratacao()
    {
        return $this->dataContratacao;
    }

    public function getDataRescisao()
    {
        return $this->dataRescisao;
    }

    public function getObservacoes()
    {
        return $this->observacoes;
    }

    // Setters
    public function setNome($nome)
    {
        $this->nome = $nome;
    }

    public function setCargo($cargo)
    {
        $this->cargo = $cargo;
    }

    public function setSalario($salario)
    {
        $this->salario = $salario;
    }

    public function setDataNascimento($dataNascimento)
    {
        $this->dataNascimento = $dataNascimento;
    }

    public function setNdocumentos($Ndocumentos)
    {
        $this->Ndocumentos = $Ndocumentos;
    }

    public function setEndereco($endereco)
    {
        $this->endereco = $endereco;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setTelefone($telefone)
    {
        $this->telefone = $telefone;
    }

    public function setDataContratacao($dataContratacao)
    {
        $this->dataContratacao = $dataContratacao;
    }

    public function setDataRescisao($dataRescisao)
    {
        $this->dataRescisao = $dataRescisao;
    }

    public function setObservacoes($observacoes)
    {
        $this->observacoes = $observacoes;
    }

    // Getters para novos campos
    public function getRg()
    {
        return $this->rg;
    }

    public function getCnh()
    {
        return $this->cnh;
    }

    public function getAnexoCpf()
    {
        return $this->anexoCpf;
    }

    public function getAnexoRg()
    {
        return $this->anexoRg;
    }

    public function getAnexoCnh()
    {
        return $this->anexoCnh;
    }

    // Setters para novos campos
    public function setRg($rg)
    {
        $this->rg = $rg;
    }

    public function setCnh($cnh)
    {
        $this->cnh = $cnh;
    }

    public function setAnexoCpf($anexoCpf)
    {
        $this->anexoCpf = $anexoCpf;
    }

    public function setAnexoRg($anexoRg)
    {
        $this->anexoRg = $anexoRg;
    }

    public function setAnexoCnh($anexoCnh)
    {
        $this->anexoCnh = $anexoCnh;
    }

    public function getAnexoNrs()
    {
        return $this->anexoNrs;
    }

    public function getAnexoCertificados()
    {
        return $this->anexoCertificados;
    }

    public function setAnexoNrs($anexoNrs)
    {
        $this->anexoNrs = $anexoNrs;
    }

    public function setAnexoCertificados($anexoCertificados)
    {
        $this->anexoCertificados = $anexoCertificados;
    }
}
