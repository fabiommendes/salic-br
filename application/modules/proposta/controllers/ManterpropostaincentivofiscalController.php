<?php

/** VerificarReadequacaoDeProjetoController
 * @author Equipe RUP - Politec
 * @author wouerner <wouerner@gmail.com>
 * @since 17/05/2010
 * @version 1.0
 * @package application
 * @subpackage application.controllers
 */

class Proposta_ManterpropostaincentivofiscalController extends MinC_Controller_Action_Abstract {

    /**
     * @var integer (vari�vel com o id do usu�rio logado)
     * @access private
     */
    private $idResponsavel = 0;
    private $idAgente = 0;
    private $idUsuario = 0;
    private $idPreProjeto = null;
    private $blnPossuiDiligencias = 0;
    private $idAgenteProponente = 0;
    private $cpfLogado = null;
    private $usuarioProponente = "N";

    /**
     * Reescreve o m�todo init()
     * @access public
     * @param void
     * @return void
     */
    public function init() {

        $auth = Zend_Auth::getInstance(); // pega a autentica��o
        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo');

        // verifica as permissoes
        $PermissoesGrupo = array();
        $PermissoesGrupo[] = 97;  // Gestor do SALIC
        $PermissoesGrupo[] = 93;  // Coordenador de Parecerista
        $PermissoesGrupo[] = 94;  // Parecerista
        $PermissoesGrupo[] = 121; // Tecnico
        $PermissoesGrupo[] = 122; // Coordenador de Acompanhamento

        if (isset($auth->getIdentity()->usu_codigo)) {
            parent::perfil(1, $PermissoesGrupo);
        } else {
            parent::perfil(4, $PermissoesGrupo);
        }

        $cpf = isset($auth->getIdentity()->usu_codigo) ? $auth->getIdentity()->usu_identificacao : $auth->getIdentity()->Cpf;

        // Busca na SGCAcesso
        $sgcAcesso = new Sgcacesso();
        $buscaAcesso = $sgcAcesso->buscar(array('Cpf = ?' => $cpf));

        // Busca na Usuarios
        $usuarioDAO = new Usuario();
        $buscaUsuario = $usuarioDAO->buscar(array('usu_identificacao = ?' => $cpf));

        // Busca na Agentes
        $agentesDAO = new Agente_Model_Agentes();
        $buscaAgente = $agentesDAO->BuscaAgente($cpf);


        if (count($buscaAcesso) > 0) {
            $this->idResponsavel = $buscaAcesso[0]->IdUsuario;
        }
        if (count($buscaAgente) > 0) {
            $this->idAgente = $buscaAgente[0]->idAgente;
        }
        if (count($buscaUsuario) > 0) {
            $this->idUsuario = $buscaUsuario[0]->usu_codigo;
        }

        if ($this->idAgente != 0) {
            $this->usuarioProponente = "S";
        }

        $this->cpfLogado = $cpf;
        $this->idAgenteProponente = $this->idAgente;
        $this->usuario = isset($auth->getIdentity()->usu_codigo) ? 'func' : 'prop';
        $this->view->usuarioLogado = isset($auth->getIdentity()->usu_codigo) ? 'func' : 'prop';
        $this->view->usuarioProponente = $this->usuarioProponente;

        parent::init();

        //recupera ID do pre projeto (proposta)
        if (!empty($_REQUEST['idPreProjeto'])) {
            $this->idPreProjeto = $_REQUEST['idPreProjeto'];

            //VERIFICA SE A PROPOSTA ESTA COM O MINC
            $Movimentacao = new Movimentacao();
            $rsStatusAtual = $Movimentacao->buscarStatusAtualProposta($this->idPreProjeto);
            $this->view->movimentacaoAtual = isset($rsStatusAtual->Movimentacao) ? $rsStatusAtual->Movimentacao : '';

            //VERIFICA SE A PROPOSTA FOI ENVIADA AO MINC ALGUMA VEZ
            $arrbusca = array();
            $arrbusca['idProjeto = ?'] = $this->idPreProjeto;
            $arrbusca['Movimentacao = ?'] = '96';
            $rsHistMov = $Movimentacao->buscar($arrbusca);
            $this->view->blnJaEnviadaAoMinc = $rsHistMov->count();

            //VERIFICA SE A PROPOSTA TEM DILIGENCIAS
            $PreProjeto = new Proposta_Model_PreProjeto();
            $rsDiligencias = $PreProjeto->listarDiligenciasPreProjeto(array('pre.idPreProjeto = ?' => $this->idPreProjeto));
            $this->view->blnPossuiDiligencias = $rsDiligencias->count();
        }
    }

    /**
     * verificaPermissaoAcessoProposta
     *
     * @param mixed $idPreProjeto
     * @access public
     * @return void
     */
    public function verificaPermissaoAcessoProposta($idPreProjeto) {
        $tblProposta = new Proposta_Model_Proposta();
        $rs = $tblProposta->buscar(array("idPreProjeto = ? " => $idPreProjeto, "1=1 OR idEdital IS NULL OR idEdital > 0" => "?", "idUsuario =?" => $this->idResponsavel));
        return $rs->count();
    }

    /**
     * indexAction
     *
     * @access public
     * @return void
     */
    public function indexAction() {
        $arrBusca = array();
        $arrBusca['stEstado = ?'] = 1;
        $arrBusca['idUsuario = ?'] = $this->idResponsavel;
        // Chama o SQL
        $tblPreProjeto = new Proposta_Model_PreProjeto();
        $rsPreProjeto = $tblPreProjeto->buscar($arrBusca, array("idAgente ASC"));

        //METODO QUE MONTA TELA DO USUARIO ENVIANDO TODOS OS PARAMENTROS NECESSARIO DENTRO DO ARRAY
        $this->montaTela("manterpropostaincentivofiscal/index.phtml", array("acaoAlterar" => $this->_urlPadrao . "/proposta/manterpropostaincentivofiscal/editar",
            "acaoExcluir" => $this->_urlPadrao . "/proposta/manterpropostaincentivofiscal/excluir",
            "dados" => $rsPreProjeto));
    }

    /**
     * declaracaonovapropostaAction
     *
     * @access public
     * @return void
     */
    public function declaracaonovapropostaAction() {

        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();

        $post = Zend_Registry::get('post');

        if ($post->mecanismo == 1) { //mecanismo == 1 (proposta por incentivo fiscal)
            $url = $this->_urlPadrao . "/proposta/manterpropostaincentivofiscal/buscaproponente";
        } else {
            $url = $this->_urlPadrao . "/manterpropostaedital/editalconfirmar";
        }

        //METODO QUE MONTA TELA DO USUARIO ENVIANDO TODOS OS PARAMENTROS NECESSARIO DENTRO DO ARRAY
        $this->montaTela("manterpropostaincentivofiscal/declaracaonovaproposta.phtml", array("acao" => $url,
            "agente" => $post->propronente));
    }

    /**
     * buscaproponenteAction
     *
     * @access public
     * @return void
     */
    public function buscaproponenteAction() {
        $post = Zend_Registry::get('post');

        if (empty($post->idAgente)) {
            $this->montaTela("manterpropostaincentivofiscal/declaracaonovaproposta.phtml");
            return;
        }

        //VERIFICA SE PROPONETE JA ESTA CADASTRADO
        $arrBusca = array();
        $arrBusca['a.idAgente = ?'] = $post->idAgente;
        $tblAgente = new Agente_Model_Agentes();
        $rsProponente = $tblAgente->buscarAgenteNome($arrBusca)->current();

        if (count($rsProponente) > 0) {
            //METODO QUE MONTA TELA DO USUARIO ENVIANDO TODOS OS PARAMENTROS NECESSARIO DENTRO DO ARRAY
            $this->montaTela("manterpropostaincentivofiscal/formproposta.phtml", array("proponente" => $rsProponente,
                "acao" => $this->_urlPadrao . "/proposta/manterpropostaincentivofiscal/salvar"));
        } else {
            $this->_redirect("/agente/manteragentes/agentes");
        }
    }

    /**
     * validaagenciaAction
     *
     * @access public
     * @return void
     */
    public function validaagenciaAction() {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();

        $get = Zend_Registry::get('get');
        $agencia = $get->agencia;

        if ($agencia > 0) {
            $tblProposta = new Proposta_Model_Proposta();
            $agencia = $tblProposta->buscaragencia($agencia);
            if (count($agencia) > 0) {
                echo "";
            } else {
                echo "Ag&ecirc;ncia inv&aacute;lida";
            }
        } else {
            echo "Ag&ecirc;ncia inv&aacute;lida";
        }
    }

    /**
     * Metodo responsavel por gravar a Proposta (INSERT e UPDATE)
     * @param void
     * @return objeto
     */
    public function salvarAction()
    {
        $post = Zend_Registry::get("post");
        $idPreProjeto = $post->idPreProjeto;
        $acao = $post->acao;

        if ($acao == 'atualizacao_automatica') {
            $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout
            $this->_helper->viewRenderer->setNoRender(true);
        }
        $dtInicio = null;
        $dtInicioTemp = explode("/", $post->dtInicioDeExecucao);
        $dtInicio = $dtInicioTemp[2] . "/" . $dtInicioTemp[1] . "/" . $dtInicioTemp[0] . date(" H:i:s");

        $dtFim = null;
        $dtFimTemp = explode("/", $post->dtFinalDeExecucao);
        $dtFim = $dtFimTemp[2] . "/" . $dtFimTemp[1] . "/" . $dtFimTemp[0] . date(" H:i:s");

        $dtAtoTombamento = null;
        if ($post->dtAtoTombamento) {
            $dtAtoTombamentoTemp = explode("/", $post->dtAtoTombamento);
            $dtAtoTombamento = $dtAtoTombamentoTemp[2] . "/" . $dtAtoTombamentoTemp[1] . "/" . $dtAtoTombamentoTemp[0] . date(" H:i:s");
        }

        $idAgente = $post->idAgente;
        $nomeProjeto = str_replace("'", "", $post->nomeProjeto);
        $nomeProjeto = str_replace("\"", "", $nomeProjeto);

        //***NAO TIRAR ESSA QUEBRA DE LINHA - FAZ PARTE DA PROGRAMACAO****
        $resumoDoProjeto = str_replace('
', ' ', str_replace('	', '', str_replace('&nbsp;', '', strip_tags(trim($_POST['resumoDoProjeto'])))));
        //***NAO TIRAR ESSA QUEBRA DE LINHA - FAZ PARTE DA PROGRAMACAO****

        $stDataFixa = $post->stDataFixa;
        $stPlanoAnual = $post->stPlanoAnual;
        $agenciaBancaria = $post->agenciaBancaria;
        $propostaAudioVisual = $post->propostaAudioVisual;
        $dtInicioDeExecucao = $dtInicio;
        $dtFinalDeExecucao = $dtFim;
        $nrAtoTombamento = $post->nrAtoTombamento;
        $dtAtoTombamento = $dtAtoTombamento;
        $esferaTombamento = $post->esferaTombamento;
        $objetivos = $_POST['objetivos'];
        $justificativa = $_POST['justificativa'];
        $acessibilidade = $_POST['acessibilidade'];
        $democratizacaoDeAcesso = $_POST['democratizacaoDeAcesso'];
        $etapaDeTrabalho = $_POST['etapaDeTrabalho'];
        $fichaTecnica = $_POST['fichaTecnica'];
        $sinopse = $_POST['sinopse'];
        $impactoAmbiental = $_POST['impactoAmbiental'];
        $especificacaoTecnica = $_POST['especificacaoTecnica'];
        $informacoes = $_POST['informacoes'];

        $dados = array("idAgente" => $idAgente,
            "NomeProjeto" => $nomeProjeto,
            "Mecanismo" => 1, //seguindo sistema legado
            "AgenciaBancaria" => $agenciaBancaria,
            "AreaAbrangencia" => $propostaAudioVisual,
            "DtInicioDeExecucao" => $dtInicioDeExecucao,
            "DtFinalDeExecucao" => $dtFinalDeExecucao,
            "NrAtoTombamento" => $nrAtoTombamento,
            "DtAtoTombamento" => $dtAtoTombamento,
            "EsferaTombamento" => $esferaTombamento,
            "ResumoDoProjeto" => $resumoDoProjeto,
            "Objetivos" => $objetivos,
            "Justificativa" => $justificativa,
            "Acessibilidade" => $acessibilidade,
            "DemocratizacaoDeAcesso" => $democratizacaoDeAcesso,
            "EtapaDeTrabalho" => $etapaDeTrabalho,
            "FichaTecnica" => $fichaTecnica,
            "Sinopse" => $sinopse,
            "ImpactoAmbiental" => $impactoAmbiental,
            "EspecificacaoTecnica" => $especificacaoTecnica, //No legado o que esta sendo gravado aqui e OUTRAS INFORMACOES
            "EstrategiadeExecucao" => $informacoes, //No legado o que esta sendo gravado aqui e ESPECIFICAO TECNICA
            "dtAceite" => date("Y/m/d H:i:s"),
            "stEstado" => 1,
            "stDataFixa" => $stDataFixa,
            "stPlanoAnual" => $stPlanoAnual,
            "idUsuario" => $this->idResponsavel,
            "stTipoDemanda" => "NA", //seguindo sistema legado
        );
        $dados['idPreProjeto'] = $idPreProjeto;

        if (!empty($idPreProjeto)) {
            $mesagem = "Altera&ccedil;&atilde;o realizada com sucesso!";
        } else {
            $mesagem = "Cadastro realizado com sucesso!";
        }

        //instancia classe modelo
        $tblPreProjeto = new Proposta_Model_PreProjeto();

        $db = Zend_Db_Table::getDefaultAdapter();

        try {
            //persiste os dados do Pre Projeto
            $idPreProjeto = $tblPreProjeto->salvar($dados);
            $this->view->idPreProjeto = $idPreProjeto;

            if ($acao == "incluir") {
                // Salvando os dados na TbVinculoProposta
                $tbVinculoDAO = new TbVinculo();
                $tbVinculoPropostaDAO = new tbVinculoPropostaResponsavelProjeto();

                $whereVinculo['idUsuarioResponsavel = ?'] = $this->idResponsavel;
                $whereVinculo['idAgenteProponente   = ?'] = $idAgente;
                $vinculo = $tbVinculoDAO->buscar($whereVinculo);

                if (count($vinculo) == 0) {
                    $dadosV = array('idAgenteProponente' => $idAgente,
                        'dtVinculo' => new Zend_Db_Expr("GETDATE()"),
                        'siVinculo' => 2,
                        'idUsuarioResponsavel' => $this->idResponsavel
                    );

                    $insere = $tbVinculoDAO->inserir($dadosV);
                }

                $vinculo2 = $tbVinculoDAO->buscar($whereVinculo);
                if (count($vinculo2) > 0) {
                    $novosDadosV = array('idVinculo' => $idVinculo = $vinculo2[0]->idVinculo,
                        'idPreProjeto' => $idPreProjeto,
                        'siVinculoProposta' => 2
                    );

                    $insere = $tbVinculoPropostaDAO->inserir($novosDadosV, false);
                }
                /* **************************************************************************************** */
            }
            if ($acao != 'atualizacao_automatica') {
                parent::message($mesagem, "/proposta/manterpropostaincentivofiscal/editar?idPreProjeto=" . $idPreProjeto, "CONFIRM");
            }
            return;
        } catch (Zend_Exception $ex) {
            parent::message("N�o foi poss�vel realizar a opera��o!" . $ex->getMessage(), "/proposta/manterpropostaincentivofiscal/index?idPreProjeto=" . $idPreProjeto, "ERROR");
        }
    }

    /**
     * Metodo responsavel por carregar a proposta na tela apos uma nova proposta ser inclusa
     * @param $idPreProjeto
     * @return objeto
     */
    public function carregaProposta($idPreProjeto) {
        $arrBusca = array();
        $arrBusca['idPreProjeto = ?'] = $idPreProjeto;
        $this->view->idPreProjeto = $idPreProjeto;
        // Chama o SQL
        $tblPreProjeto = new Proposta_Model_PreProjeto();
        $rsPreProjeto = $tblPreProjeto->buscar($arrBusca)->current();

        $arrBuscaProponete = array();
        $arrBuscaProponete['a.idAgente = ?'] = $rsPreProjeto->idAgente;

        $tblAgente = new Agente_Model_Agentes();
        $rsProponente = $tblAgente->buscarAgenteNome($arrBuscaProponete)->current();

        $arrDados = array("proposta" => $rsPreProjeto,
            "proponente" => $rsProponente);
        return $arrDados;
        //METODO QUE MONTA TELA DO USUARIO ENVIANDO TODOS OS PARAMENTROS NECESSARIO DENTRO DO ARRAY
        $this->montaTela("manterpropostaincentivofiscal/formproposta.phtml", array("acao" => $this->_urlPadrao . "/proposta/manterpropostaincentivofiscal/salvar",
            "proposta" => $rsPreProjeto,
            "proponente" => $rsProponente));
    }

    /**
     * Metodo responsavel por carregar os dados da proposta para alteracao
     * @param void
     * @return objeto
     */
    public function editarAction() {

        /* =============================================================================== */
        /* ==== VERIFICA PERMISSAO DE ACESSO DO PROPONENTE A PROPOSTA OU AO PROJETO ====== */
        /* =============================================================================== */
        $this->verificarPermissaoAcesso(true, false, false);

        //recupera parametros
        $get = Zend_Registry::get('get');
        $idPreProjeto = $get->idPreProjeto;
        $this->view->idPreProjeto = $idPreProjeto;
        if (!empty($idPreProjeto)) {

            $arrBusca['idPreProjeto = ?'] = $idPreProjeto;

            // Chama o SQL
            $tblPreProjeto = new Proposta_Model_PreProjeto();
            $rsPreProjeto = $tblPreProjeto->buscar($arrBusca)->current();

            $arrBuscaProponete['a.idAgente = ?'] = $rsPreProjeto->idAgente;
            $tblAgente = new Agente_Model_Agentes();
            $rsProponente = $tblAgente->buscarAgenteNome($arrBuscaProponete)->current();

            $ag = new Agente_Model_Agentes();
            $verificarvinculo = $ag->buscarAgenteVinculoProponente(array('vprp.idPreProjeto = ?' => $idPreProjeto,
                'vprp.siVinculoProposta = ?' => 2));

            $verificarvinculoCount = $ag->buscarAgenteVinculoProponente(array('vprp.idPreProjeto = ?' => $idPreProjeto))->count();

            if ($verificarvinculoCount > 0) {
                $this->view->verificarsolicitacaovinculo = true;
            } else {
                $this->view->verificarsolicitacaovinculo = false;
            }

            // I Love you @
            if (@$verificarvinculo[0]->siVinculo != 2) {
                $this->view->siVinculoProponente = true;
            } else {
                $this->view->siVinculoProponente = false;
            }

            $idAgente = $this->idResponsavel;

            $tblVinculo = new TbVinculo();

            $arrBuscaP['VP.idPreProjeto = ?'] = $idPreProjeto;
            $arrBuscaP['VI.idUsuarioResponsavel = ?'] = $this->idResponsavel;
            $rsVinculoP = $tblVinculo->buscarVinculoProponenteResponsavel($arrBuscaP);

            $arrBuscaN['VI.siVinculo IN (0,2)'] = '';
            $arrBuscaN['VI.idUsuarioResponsavel = ?'] = $this->idResponsavel;
            $rsVinculoN = $tblVinculo->buscarVinculoProponenteResponsavel($arrBuscaN);

            //METODO QUE MONTA TELA DO USUARIO ENVIANDO TODOS OS PARAMENTROS NECESSARIO DENTRO DO ARRAY
            $this->montaTela("manterpropostaincentivofiscal/formproposta.phtml", array("acao" => $this->_urlPadrao . "/proposta/manterpropostaincentivofiscal/salvar",
                "proposta" => $rsPreProjeto,
                "solicitacaovinculo" => $verificarvinculo,
                "idResponsavel" => $idAgente,
                "dadosVinculo" => $rsVinculoP,
                "listaProponentes" => $rsVinculoN,
                "idPreProjeto" => $idPreProjeto,
                "proponente" => $rsProponente));
        } else {
            //chama o metodo index
            $this->_forward("index", "manterpropostaincentivofiscal", 'proposta');
        }
    }

    /**
     * Metodo responsavel por inativar uma proposta gravada
     * @param void
     * @return objeto
     */
    public function excluirAction() {

        /* =============================================================================== */
        /* ==== VERIFICA PERMISSAO DE ACESSO DO PROPONENTE A PROPOSTA OU AO PROJETO ====== */
        /* =============================================================================== */
        $this->verificarPermissaoAcesso(true, false, false);

        $get = Zend_Registry::get("get");
        $idPreProjeto = $get->idPreProjeto;

        //BUSCANDO REGISTRO A SER ALTERADO
        $tblPreProjeto = new Proposta_Model_PreProjeto();
        $rsPreProjeto = $tblPreProjeto->find($idPreProjeto)->current();
        //altera Estado da proposta
        $rsPreProjeto->stEstado = 0;

        if ($rsPreProjeto->save()) {
            parent::message("Exclus&atilde;o realizada com sucesso!", "/proposta/manterpropostaincentivofiscal/listar-propostas", "CONFIRM");
        } else {
            parent::message("N&atilde;o foi poss�vel realizar a opera&ccedil;&atilde;o!", "/proposta/manterpropostaincentivofiscal/listar-propostas", "ERROR");
        }
    }

    /**
     * enviarPropostaAoMincAction
     *
     * @access public
     * @return void
     */
    public function enviarPropostaAoMincAction() {

        /* =============================================================================== */
        /* ==== VERIFICA PERMISSAO DE ACESSO DO PROPONENTE A PROPOSTA OU AO PROJETO ====== */
        /* =============================================================================== */
        $this->verificarPermissaoAcesso(true, false, false);

        //recupera parametros
        $get = Zend_Registry::get('get');
        $idPreProjeto = $get->idPreProjeto;

        $erro = "";
        $msg = "";

        if (!empty($idPreProjeto)) {
            $sp = new spValidarApresentacaoDeProjeto();
            $arrResultado = $sp->paChecklistDeEnvioDeProposta($idPreProjeto);

            //METODO QUE MONTA TELA DO USUARIO ENVIANDO TODOS OS PARAMENTROS NECESSARIO DENTRO DO ARRAY
            $this->montaTela("manterpropostaincentivofiscal/enviarproposta.phtml", array("acao" => $this->_urlPadrao . "/proposta/manterpropostaincentivofiscal/salvar",
                "resultado" => $arrResultado));
        } else {
            parent::message("Necess�rio informar o n�mero da proposta.", "/proposta/manterpropostaincentivofiscal/index", "ERROR");
        }
    }

    /**
     * validarEnvioPropostaAoMinc
     *
     * @param mixed $idPreProjeto
     * @access public
     * @return void
     */
    public function validarEnvioPropostaAoMinc($idPreProjeto) {
    /* }}} */
        //BUSCA DADOS DO PROJETO
        $arrBusca = array();
        $arrBusca['idPreProjeto = ?'] = $idPreProjeto;
        $tblPreProjeto = new Proposta_Model_PreProjeto();
        $rsPreProjeto = $tblPreProjeto->buscar($arrBusca)->current();
        /* ======== VERIFICA TODAS AS INFORMACOES NECESSARIAS AO ENVIO DA PROPOSTA ======= */

        $arrResultado = array();

        $arrResultado['erro'] = false;

        /*         * ******* MOVIMENTACAO ******** */
        //VERIFICA SE A PROPOSTA ESTA COM O MINC
        $Movimentacao = new Movimentacao();
        $rsMovimentacao = $Movimentacao->buscarStatusAtualProposta($idPreProjeto);

        if ($rsMovimentacao->Movimentacao != 95) {
            $arrResultado['erro'] = true;
            $arrResultado['movimentacao']['erro'] = false;
            $arrResultado['movimentacao']['msg'] = "A Proposta Cultural encontra-se no Minist&eacute;rio da Cultura";
        } else {
            /* $arrResultado['erro'] = true;
              $arrResultado['movimentacao']['erro'] = false;
              $arrResultado['movimentacao']['msg'] = "A Proposta Cultural encontra-se no Minist&eacute;rio da Cultura"; */
        }

        /*         * ******* DADOS DO PROPONENTE ******** */

        $tblProponente = new Proponente();
        //$rsProponente = $tblProponente->buscar(array("a.idAgente = ?"=>$rsPreProjeto->idAgente))->current();

        $tblAgente = new Agente_Model_Agentes();
        $rsProponente = $tblAgente->buscarAgenteNome(array("a.idAgente = ?" => $rsPreProjeto->idAgente))->current();

        $regularidade = Regularidade::buscarSalic($rsProponente->CNPJCPF);

        $dadosEndereco = Agente_Model_EnderecoNacionalDAO::buscarEnderecoNacional($rsPreProjeto->idAgente);

        $dadosEmail = Email::buscar($rsPreProjeto->idAgente);

        $dadosDirigente = Agente_Model_ManterAgentesDAO::buscarVinculados(null, null, null, null, $rsPreProjeto->idAgente);
        //$dadosDirigente = ManterAgentes::buscaDirigentes($rsProponente->CNPJCPF);

        $tblLocaisRealizacao = new Abrangencia();
        $dadosLocais = $tblLocaisRealizacao->buscar(array("a.idProjeto" => $idPreProjeto, "a.stAbrangencia" => 1));

        $tblPlanoDivulgacao = new PlanoDeDivulgacao();
        $dadosPlanoDivulgacao = $tblPlanoDivulgacao->buscar(array("idProjeto =?" => $idPreProjeto))->toArray();

        $tblPlanoDistribuicao = new PlanoDistribuicao();
        $dadosPlanoDistribuicao = $tblPlanoDistribuicao->buscar(array("a.idProjeto = ?" => $idPreProjeto, "a.stPlanoDistribuicaoProduto = ?" => 1), array("idProduto ASC"))->toArray();


        if (count($rsProponente) > 0) {

            //VERIFICA SE O PROPONENTE EST� VINCULADO
            $vinculoProponente = new tbVinculoPropostaResponsavelProjeto();
            $whereProp['VP.idPreProjeto = ?'] = $this->idPreProjeto;
            $whereProp['VP.siVinculoProposta = ?'] = 2;
            $rsVinculo = $vinculoProponente->buscarResponsaveisProponentes($whereProp);

            if ($rsVinculo->count() > 0) {

                if (isset($rsVinculo[0]->siVinculo) && $rsVinculo[0]->siVinculo == 0) {
                    $msgProponente = "Aguardando o v�nculo do Proponente";
                } elseif (isset($rsVinculo[0]->siVinculo) && $rsVinculo[0]->siVinculo == 1) {
                    $msgProponente = "O Proponente rejeitou o v�nculo";
                } elseif (isset($rsVinculo[0]->siVinculo) && $rsVinculo[0]->siVinculo == 2) {
                    $msgProponente = "Proponente Vinculado";
                } else {
                    $msgProponente = "Proponente desvinculado";
                }

                if ($rsVinculo[0]->siVinculo == 2) {
                    $arrResultado['vinculoproponente']['erro'] = false;
                    $arrResultado['vinculoproponente']['msg'] = $msgProponente;
                } else {
                    $arrResultado['erro'] = true;
                    $arrResultado['vinculoproponente']['erro'] = true;
                    $arrResultado['vinculoproponente']['msg'] = $msgProponente;
                }
            } else {
                $arrResultado['erro'] = true;
                $arrResultado['vinculoproponente']['erro'] = true;
                $arrResultado['vinculoproponente']['msg'] = "Proponente desvinculado";
            }
            //REGULARIDADE DO PROPONENTE
            if (count($regularidade) > 0) {
                if ($regularidade[0]->Habilitado == "S") {
                    $arrResultado['regularidadeproponente']['erro'] = false;
                    $arrResultado['regularidadeproponente']['msg'] = "Proponente em situa&ccedil;&atilde;o REGULAR no Minist&eacute;rio da Cultura";
                } else {
                    $arrResultado['erro'] = true;
                    $arrResultado['regularidadeproponente']['erro'] = true;
                    $arrResultado['regularidadeproponente']['msg'] = "Proponente em situa&ccedil;&atilde;o IRREGULAR no Minist&eacute;rio da Cultura";
                }
            } else {
                $arrResultado['regularidadeproponente']['erro'] = false;
                $arrResultado['regularidadeproponente']['msg'] = "Proponente em situa&ccedil;&atilde;o REGULAR no Minist&eacute;rio da Cultura";
            }

            //DADOS GERAIS DA PROPOSTA
            if (!empty($rsPreProjeto)) {
                if (trim($rsPreProjeto->Objetivos) == "" || trim($rsPreProjeto->Justificativa) == "" || trim($rsPreProjeto->Acessibilidade) == "" ||
                        trim($rsPreProjeto->DemocratizacaoDeAcesso) == "" || trim($rsPreProjeto->EtapaDeTrabalho) == "" || trim($rsPreProjeto->FichaTecnica) == "" ||
                        trim($rsPreProjeto->Sinopse) == "" || trim($rsPreProjeto->ImpactoAmbiental) == "" || trim($rsPreProjeto->EspecificacaoTecnica) == "") {
                    $arrResultado['erro'] = true;
                    $arrResultado['dadosgeraisproposta']['erro'] = true;
                    $arrResultado['dadosgeraisproposta']['msg'] = "Dados gerais da proposta pendente. Os campos Objetivos, Justificativa, Acessibilidade, Democratiza&ccedil;&atilde;o de Acesso, Etapas de Trabalho, Ficha T�cnica, Sinopse da obra, Impacto Ambiental e Especifica&ccedil;&atilde;o t�cnicas do produto, s�o de preenchimento obrigat�rio.";
                } else {
                    $arrResultado['dadosgeraisproposta']['erro'] = false;
                    $arrResultado['dadosgeraisproposta']['msg'] = "Dados gerais da proposta";
                }
            } else {
                $arrResultado['erro'] = true;
                $arrResultado['dadosgeraisproposta']['erro'] = true;
                $arrResultado['dadosgeraisproposta']['msg'] = "Dados gerais da proposta pendente. Os campos Objetivos, Justificativa, Acessibilidade, Democratiza&ccedil;&atilde;o de Acesso, Etapas de Trabalho, Ficha T�cnica, Sinopse da obra, Impacto Ambiental e Especifica&ccedil;&atilde;o t�cnicas do produto, s�o de preenchimento obrigat�rio.";
            }

            //E-MAIL
            $blnEmail = false;
            if (count($dadosEmail) > 0) {
                foreach ($dadosEmail as $email) {
                    if ($email->Status == 1) {
                        $blnEmail = true;
                    }
                }
                if ($blnEmail === false) {
                    $arrResultado['erro'] = true;
                    $arrResultado['email']['erro'] = true;
                    $arrResultado['email']['msg'] = "E-mail do proponente inexistente";
                } else {
                    $arrResultado['email']['erro'] = false;
                    $arrResultado['email']['msg'] = "E-mail do proponente";
                }
            } else {
                $arrResultado['erro'] = true;
                $arrResultado['email']['erro'] = true;
                $arrResultado['email']['msg'] = "E-mail do proponente inexistente";
            }

            //ENDERECO
            $blnEndCorrespondencia = false;
            if (count($dadosEndereco) > 0) {
                foreach ($dadosEndereco as $endereco) {
                    if ($endereco->Status == 1) {
                        $blnEndCorrespondencia = true;
                    }
                }
                if ($blnEndCorrespondencia === false) {
                    $arrResultado['erro'] = true;
                    $arrResultado['endereco']['erro'] = true;
                    $arrResultado['endereco']['msg'] = "Dados cadastrais do proponente inexistente ou n&atilde;o h&aacute; endere&ccedil;o para correspond&ecirc;ncia selecionado";
                } else {
                    $arrResultado['endereco']['erro'] = false;
                    $arrResultado['endereco']['msg'] = "Dados cadastrais do proponente";
                }
            } else {
                $arrResultado['erro'] = true;
                $arrResultado['endereco']['erro'] = true;
                $arrResultado['endereco']['msg'] = "Dados cadastrais do proponente inexistente ou n&atilde;o h&aacute; endere&ccedil;o para correspond&ecirc;ncia selecionado";
            }

            //NATUREZA
            if ($rsProponente->TipoPessoa == 1) {
                $tblNatureza = new Natureza();
                $dadosNatureza = $tblNatureza->buscar(array("idAgente = ?" => $rsPreProjeto->idAgente));

                if (count($dadosNatureza) > 0) {
                    $arrResultado['dirigente']['erro'] = false;
                    $arrResultado['dirigente']['msg'] = "Natureza do proponente";
                } else {
                    $arrResultado['erro'] = true;
                    $arrResultado['dirigente']['erro'] = true;
                    $arrResultado['dirigente']['msg'] = "Natureza do proponente";
                }
            }

            //DIRIGENTE
            if ($rsProponente->TipoPessoa == 1) {

                if (count($dadosDirigente) > 0) {
                    $arrResultado['dirigente']['erro'] = false;
                    $arrResultado['dirigente']['msg'] = "Cadastro de Dirigente";
                } else {
                    $arrResultado['erro'] = true;
                    $arrResultado['dirigente']['erro'] = true;
                    $arrResultado['dirigente']['msg'] = "Cadastro de Dirigente";
                }
            }

            //LOCAIS DE RALIZACAO
            if (count($dadosLocais) > 0) {
                $arrResultado['locaisrealizacao']['erro'] = false;
                $arrResultado['locaisrealizacao']['msg'] = "Local de realiza&ccedil;&atilde;o da proposta";
            } else {
                $arrResultado['erro'] = true;
                $arrResultado['locaisrealizacao']['erro'] = true;
                $arrResultado['locaisrealizacao']['msg'] = "O Local de realiza&ccedil;&atilde;o da proposta n&atilde;o foi preenchido";
            }

            //PLANO DE DIVULGACAO
            if (count($dadosPlanoDivulgacao) > 0) {
                $arrResultado['planodivulgacao']['erro'] = false;
                $arrResultado['planodivulgacao']['msg'] = "Plano B&aacute;sico de Divulga&ccedil;&atilde;o";
                $planinhaProposta = New PlanilhaProposta();

                $get = Zend_Registry::get('get');
                $idProjeto = $get->idPreProjeto;
                $buscaPlanilhaPropostaDivulgacao = $planinhaProposta->somarPlanilhaPropostaDivulgacao($idProjeto, 109);
                $buscaPlanilhaProposta = $planinhaProposta->somarPlanilhaProposta($idProjeto, 109);

                $porcentProposta = ($buscaPlanilhaProposta->soma * 0.20);
                $valorPropostaDivulgacao = $buscaPlanilhaPropostaDivulgacao->soma;
                if ($valorPropostaDivulgacao > $porcentProposta) {
                    $valorRetirar = $valorPropostaDivulgacao - $porcentProposta;
                    $arrResultado['erro'] = true;
                    $arrResultado['planodivulgacao']['erro'] = true;
                    //$arrResultado['planodivulgacao']['msg'] = "Custo de Divulga��o/Comercializa��o superior a 20% do valor total do projeto";
                    $arrResultado['planodivulgacao']['msg'] = "Custo de Divulga&ccedil;&atilde;o/Comercializa&ccedil;&atilde;o superior a 20% do valor total da proposta. Favor readequar os custos em <b>R$ " . number_format($valorRetirar, '2', ',', '.') . "</b> para enviar a sua proposta ao Minist�rio da Cultura.";
                }
            } else {
                $arrResultado['erro'] = true;
                $arrResultado['planodivulgacao']['erro'] = true;
                $arrResultado['planodivulgacao']['msg'] = "O Plano B&aacute;sico de Divulga&ccedil;&atilde;o n&atilde;o foi preenchido";
            }

            //PLANO DE DISTRIBUICAO
            if (count($dadosPlanoDistribuicao) > 0) {

                $arrResultado['planodistribuicao']['erro'] = false;
                $arrResultado['planodistribuicao']['msg'] = "Plano Distribui&ccedil;&atilde;o de Produto";

                //PLANILHA POR PRODUTO
                //inicializando variaveis
                $arrProdutoPlanilhaOrcamentaria = array();
                $arrProdutoPlanilhaCustoAdmin = array();
                $arrBuscaPlanilhaOrcamentaria = array(); //para planilhas orcamentarias onde idProduto <> 0
                $arrBuscaPlanilhaCustoAdmin = array(); //para planilhas orcamentarias onde idProduto = 0
                $qtdeProdutoPrincial = 0;
                $valorProjeto = 0;
                //instancia classe modelo PlanilhaProposta
                $tblPlanilhaProposta = new PlanilhaProposta();
                foreach ($dadosPlanoDistribuicao as $produto) {
                    //=========== PLANILHA ORCAMENTARIA ===============
                    $idProduto = $produto['idProduto'];
                    $arrBuscaPlanilhaOrcamentaria['idProjeto = ?'] = $idPreProjeto;
                    $arrBuscaPlanilhaOrcamentaria['idProduto = ?'] = $idProduto;
                    //$arrBuscaPlanilhaOrcamentaria['idEtapa <> ?']=4;

                    $planilhaOrcamentaria = $tblPlanilhaProposta->buscar($arrBuscaPlanilhaOrcamentaria);
                    //$planilha = PlanilhaPropostaDAO::buscarPlanilhaPorProjetoProduto($idPreProjeto, $idProduto);

                    if (count($planilhaOrcamentaria) > 0) {
                        $arrProdutoPlanilhaOrcamentaria['CONTEM'][] = $idProduto;

                        //realiza calculo para encontrar valor do projeto
                        for ($i = 0; $i < sizeof($planilhaOrcamentaria); $i++) {
                            $valorProjeto += ( $planilhaOrcamentaria[$i]->Quantidade * $planilhaOrcamentaria[$i]->Ocorrencia * $planilhaOrcamentaria[$i]->ValorUnitario);
                        }
                    } else {
                        $arrProdutoPlanilhaOrcamentaria['NAO_CONTEM'][] = $idProduto;
                    }

                    //=========== PRODUTO PRINCIPAL ==========
                    if ($produto['stPrincipal'] == 1) {
                        $qtdeProdutoPrincial++;
                    }
                }//fecha FOREACH de Plano Distribuicao

                if (!empty($arrProdutoPlanilhaOrcamentaria['NAO_CONTEM'])) {
                    $arrResultado['erro'] = true;
                    $arrResultado['planilhaproduto']['erro'] = true;
                    $arrResultado['planilhaproduto']['msg'] = "Existe produto cadastrado sem a respectiva planilha or&ccedil;ament&aacute;ria lan&ccedil;ada";
                }

                //=========== PLANILHA CUSTO ADMINISTRATIVO ==========
                $arrBuscaPlanilhaCustoAdmin['idProjeto = ?'] = $idPreProjeto;
                $arrBuscaPlanilhaCustoAdmin['idProduto = ?'] = 0; //planilha de custo admin. n&atilde;o tem produto
                $arrBuscaPlanilhaCustoAdmin['idEtapa = ?'] = 4; //etapa 4 = Custo/Adminitrativo

                $planilhaCustoAdmin = $tblPlanilhaProposta->buscar($arrBuscaPlanilhaCustoAdmin);
                $valorCustoAdmin = 0;
                if (count($planilhaCustoAdmin) > 0) {
                    $arrResultado['planilhacustoadmin']['erro'] = false;
                    $arrResultado['planilhacustoadmin']['msg'] = "Planilha de custos administrativos lan&ccedil;ada";

                    //realiza calculo para encontrar custo administrativo do projeto
                    for ($i = 0; $i < sizeof($planilhaCustoAdmin); $i++) {
                        $valorCustoAdmin += ( $planilhaCustoAdmin[$i]->Quantidade * $planilhaCustoAdmin[$i]->Ocorrencia * $planilhaCustoAdmin[$i]->ValorUnitario);
                    }
                } else {
                    $arrResultado['erro'] = true;
                    $arrResultado['planilhacustoadmin']['erro'] = true;
                    $arrResultado['planilhacustoadmin']['msg'] = "A planilha de custos administrativos da proposta n&atilde;o est&aacute; lan&ccedil;ada";
                }

                //calcula percentual do custo administrativo
                $quinzecentoprojeto = ($valorProjeto * 0.15);

                //if ($percentual > 15) {
                if ($valorCustoAdmin > $quinzecentoprojeto) {
                    $valorRetirarCustoAdm = $valorCustoAdmin - $quinzecentoprojeto;
                    $arrResultado['erro'] = true;
                    $arrResultado['percentualcustoadmin']['erro'] = true;
                    $arrResultado['percentualcustoadmin']['msg'] = "Custo administrativo  superior a 15% do valor total da proposta. Favor readequar os custos em <b>R$ " . number_format($valorRetirarCustoAdm, '2', ',', '.') . "</b> para enviar a sua proposta ao Minist�rio da Cultura.";
                }
                if ($qtdeProdutoPrincial <= 0) {
                    $arrResultado['erro'] = true;
                    $arrResultado['produtoprincipal']['erro'] = true;
                    $arrResultado['produtoprincipal']['msg'] = "N&atilde;o h&aacute; produto principal selecionado na proposta";
                } elseif ($qtdeProdutoPrincial > 1) {
                    $arrResultado['erro'] = true;
                    $arrResultado['produtoprincipal']['erro'] = true;
                    $arrResultado['produtoprincipal']['msg'] = "S&oacute; poder&aacute; haver um produto principal em cada proposta, a sua est&aacute; com mais de um produto";
                } else {
                    $arrResultado['produtoprincipal']['erro'] = false;
                    $arrResultado['produtoprincipal']['msg'] = "Produto principal";
                }
            } else {
                $arrResultado['erro'] = true;
                $arrResultado['planodistribuicao']['erro'] = true;
                $arrResultado['planodistribuicao']['msg'] = "O Plano Distribui&ccedil;&atilde;o de Produto n&atilde;o foi preenchido";
            }
        } else {
            $arrResultado['erro'] = true;
            $arrResultado['proponente']['erro'] = true;
            $arrResultado['proponente']['msg'] = "Dados cadastrais do proponente inexistente ou n&atilde;o h&aacute; endere&ccedil;o para correspond&ecirc;ncia selecionado";
        }
        //=========== PLANO ANUAL==========
        if ($rsPreProjeto->stPlanoAnual <> 0) {
            $ano_envio = date("Y");
            $ano_execucao = explode ('/',data::formatarDataMssql($rsPreProjeto->DtInicioDeExecucao));
            $ano_execucao = $ano_execucao[2];
            $data_validacao = (int) date("Y") . '0930';
            if (($data_validacao <= date('Ymd')) && ($ano_envio >= $ano_execucao)) {
                $arrResultado['erro'] = true;
                $arrResultado['planoanual']['erro'] = true;
                $arrResultado['planoanual']['msg'] = "De acordo com a s�mula 10, projetos de plano anual s� poder�o ser enviados at� 30 de setembro do ano vigente, e o per�odo de execu��o dever� ser do ano seguinte a data de envio.";
            }else  {
                $arrResultado['planoanual']['erro'] = false;
                $arrResultado['planoanual']['msg'] = "Plano Anual";
            }

        }
        return $arrResultado;
    }

    public function confirmarEnvioPropostaAoMincAction() {

        /* =============================================================================== */
        /* ==== VERIFICA PERMISSAO DE ACESSO DO PROPONENTE A PROPOSTA OU AO PROJETO ====== */
        /* =============================================================================== */
        $this->verificarPermissaoAcesso(true, false, false);

        //recupera parametros
        $get = Zend_Registry::get('get');
        $idPreProjeto = $get->idPreProjeto;
        $valida = $get->valida;
        $idTecnico = null;
        $rsTecnicos = array();

        if (isset($_REQUEST['edital'])) {
            $edital = "&edital=s";
        } else {
            $edital = "";
        }
        if (!empty($idPreProjeto) && $valida == "s") {
            $tblPreProjeto = new Proposta_Model_PreProjeto();
            $tblAvaliacao = new AnalisarPropostaDAO();

            //recupera dados do projeto
            $rsPreProjeto = $tblPreProjeto->find($idPreProjeto)->current();

            if ($rsPreProjeto->AreaAbrangencia == 0) {
                $idOrgaoSuperior = 262;
            } else {
                $idOrgaoSuperior = 171;
            }
            //verifica se a proposta ja foi recebida por um tecnico
            $avaliacao = $tblAvaliacao->verificarAvaliacao($idPreProjeto);

            //SE A PROPOSTA JA FOI AVALIADA POR UM TECNICO E O MESMO ESTIVER ATIVO, ATRIBUI A AVALIACAO A ELE
            if (count($avaliacao) > 0) {
                if ($avaliacao[0]->ConformidadeOK == 0 || $avaliacao[0]->ConformidadeOK == 1) {
                    //verifica se o tecnico esta habilitado
                    $arrBusca = array();
                    $arrBusca['sis_codigo = '] = 21;
                    $arrBusca['gru_codigo = '] = 92;
                    $arrBusca['usu_codigo = '] = $avaliacao[0]->idTecnico;
                    $analista = AdmissibilidadeDAO::buscarAnalistas($arrBusca);

                    if (count($analista) > 0) {
                        if ($analista[0]->uog_status == 1) {
                            $idTecnico = $avaliacao[0]->idTecnico;
                        } else {
                            $idTecnico = null;
                            //recupera todos os tecnicos do orgao para fazer o balanceamento
                            $rsTecnicos = $tblPreProjeto->recuperarTecnicosOrgao($idOrgaoSuperior);
                        }
                    } else {
                        $idTecnico = null;
                        //recupera todos os tecnicos do orgao para fazer o balanceamento
                        $rsTecnicos = $tblPreProjeto->recuperarTecnicosOrgao($idOrgaoSuperior);
                    }
                }
            } else {
                //recupera todos os tecnicos do orgao para fazer o balanceamento
                $rsTecnicos = $tblPreProjeto->recuperarTecnicosOrgao($idOrgaoSuperior);
            }

            //SE A PROPOSTA NUNCA FOI AVALIADA OU SE O TECNICO Q A AVALIOU ESTA DESABILITADO FAZ O BALANCEAMENTO
            if (count($rsTecnicos) > 0 && $idTecnico == null) {
                $arrTecnicosPropostas = array();

                foreach ($rsTecnicos as $tecnico) {
                    $rsAvaliacaoPorTecnico = $tblAvaliacao->recuperarQtdePropostaTecnicoOrgao($tecnico->uog_orgao, $tecnico->usu_codigo);
                    $arrTecnicosPropostas[$tecnico->usu_codigo] = $rsAvaliacaoPorTecnico[0]->qtdePropostas;
                }
                asort($arrTecnicosPropostas);

                //PEGA O ID DO TECNICO Q TEM MENOS PROPOSTAS
                $ct = 1;
                foreach ($arrTecnicosPropostas as $chave => $valor) {
                    if ($ct == 1) {
                        $idTecnico = $chave;
                        $ct++;
                    } else {
                        break;
                    }
                }
            }

            //INICIA PERSISTENCIA DOS DADOS
            if ($idTecnico) {

                try {

                    //======== PERSXISTE DADOS DA MOVIMENTACAO ==========/
                    //atualiza status da ultima movimentacao
                    $tblAvaliacao->updateEstadoMovimentacao($idPreProjeto);

                    //PERSISTE DADOS DA MOVIMENTACAO
                    $tblMovimentacao = new Movimentacao();
                    $dados = array("idProjeto" => $idPreProjeto,
                        "Movimentacao" => "96", //satus
                        "DtMovimentacao" => date("Y/m/d H:i:s"),
                        "stEstado" => "0", //esta informacao estava fixa trigger
                        "Usuario" => $this->idResponsavel);

                    $tblMovimentacao->salvar($dados);

                    //======== PERSXISTE DADOS DA AVALIACAO ==========/
                    //atualiza status da ultima avaliacao
                    //$tblAvaliacao->updateEstadoAvaliacao($idPreProjeto); //COMENTANDO CODIGO PARA DEIXAR SP (SAC..tbMovimentacao.trMovimentacao_Insert) TRABALHAR

                    $dados = array();
                    $dados['idPreProjeto'] = $idPreProjeto;
                    $dados['idTecnico'] = $idTecnico; //$this->idResponsavel;
                    $dados['dtEnvio'] = "'" . date("Y/m/d H:i:s") . "'";
                    $dados['dtAvaliacao'] = "'" . date("Y/m/d H:i:s") . "'";
                    $dados['avaliacao'] = "";
                    $dados['conformidade'] = 9;
                    $dados['estado'] = 0;

                    //PERSISTE DADOS DA AVALIACAO PROPOSTA
                    //$tblAvaliacao->inserirAvaliacao($dados); //COMENTANDO CODIGO PARA DEIXAR SP (SAC..tbMovimentacao.trMovimentacao_Insert) TRABALHAR

//                    $db->commit();

                    parent::message("A Proposta foi enviado com sucesso ao Minist&eacute;rio da Cultura!", "/proposta/manterpropostaincentivofiscal/enviar-proposta-ao-minc?idPreProjeto=" . $idPreProjeto . $edital, "CONFIRM");
                    die();
                } catch (Exception $e) {
//                    $db->rollback();
                    parent::message("A Proposta n&atilde;o foi enviado ao Minist&eacute;rio da Cultura.", "/proposta/manterpropostaincentivofiscal/enviar-proposta-ao-minc?idPreProjeto=" . $idPreProjeto . $edital, "ERROR");
                    die();
                }
            } else { //fecha IF se encontrou tecnicos para enviar a proposta
                parent::message("A Proposta n&atilde;o foi enviado ao Minist&eacute;rio da Cultura.", "/proposta/manterpropostaincentivofiscal/enviar-proposta-ao-minc?idPreProjeto=" . $idPreProjeto . $edital, "ERROR");
                die();
            }
        } else {
            parent::message("A Proposta n&atilde;o foi enviado ao Minist&eacute;rio da Cultura.", "/proposta/manterpropostaincentivofiscal/enviar-proposta-ao-minc?idPreProjeto=" . $idPreProjeto . $edital, "ERROR");
        }
    }

    /**
     * M?todo responsavel por validar as datas do formulario
     * @param void
     * @return objeto
     */
    public function validaDatasAction() {
        $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout
        //recupera parametros
        $get = Zend_Registry::get('get');
        $dtInicio = $get->dtInicio;
        $dtFim = $get->dtFim;

        $bln = "true";
        $script = "";
        $mensagem = "";

        $objData = new Data();

        //VERIFICA SE DATA INICIO E MAIOR QUE DATA FINAL
        if (!empty($get->dtInicio) && !empty($get->dtFim) && strlen($get->dtInicio) == 10 && strlen($get->dtFim) == 10) {

            $dtTemp = explode("/", $get->dtInicio);
            $dtInicio = $dtTemp[2] . $dtTemp[1] . $dtTemp[0];

            $dtTemp = null;
            $dtTemp = explode("/", $get->dtFim);
            $dtFim = $dtTemp[2] . $dtTemp[1] . $dtTemp[0];

            if ($dtInicio > $dtFim) {
                $mensagem = "<br><font color='red'>Data de in&iacute;cio n&atilde;o pode ser maior que a data final</font>";
                $bln = "false";
            }
            if (!$objData->validarData($get->dtInicio)) {
                $mensagem = "<br><font color='red'>Data de in&iacute;cio inv&aacute;lida</font>";
                $bln = "false";
            }
            if (!$objData->validarData($get->dtFim)) {
                $mensagem = "<br><font color='red'>Data final inv&aacute;lida</font>";
                $bln = "false";
            }
            if (!$objData->validarData($get->dtInicio)) {
                $mensagem = "<br><font color='red'>O per�odo de execu��o de projetos de plano anual dever ser posterior ao ano vigente</font>";
                $bln = "false";
            }

        }

        //VERIFICA SE DATA INICIO E MAIOR QUE 90 DIAS DA DATA ATUAL
        if (!empty($get->dtInicio) && strlen($get->dtInicio) == 10) {
            $dtTemp = explode("/", $get->dtInicio);
            $dtInicio = $dtTemp[2] . $dtTemp[1] . $dtTemp[0];

            $diffEmDias = $objData->CompararDatas(date("Ymd"), $dtInicio);
            if ($diffEmDias < 0 || $diffEmDias < 90) {
                $mensagem = "<br><font color='red'>A data inicial de realiza&ccedil;&atilde;o dever&aacute; ser no m&iacute;nimo 90 dias ap&oacute;s a data atual.</font>";
                $bln = "false";
            }

            if (!$objData->validarData($get->dtInicio)) {
                $mensagem = "<br><font color='red'>Data de in&iacute;cio inv&aacute;lida</font>";
                $bln = "false";
            }
            //verifica se a data inicio esta entre 01 de Fevereiro e 30 de Novembro
            //if($dtInicio >= date("Y")."0201" && $dtInicio <= date("Y")."1130"){
        }

        //VERIFICA SE DATA DO ATO E VALIDA, CASO ELA TENHA SIDO INFORMADA
        if (!empty($get->dtAto) && strlen($get->dtAto) == 10) {
            if (!$objData->validarData(trim($get->dtAto))) {
                $mensagem = "<br><font color='red'>Data tombamento inv&aacute;lida</font>";
                $bln = "false";
            }
        }



        $script = "\$('#blnDatasValidas').val(" . $bln . ");\n";
        $this->montaTela("manterpropostaincentivofiscal/mensagem.phtml", array("mensagem" => $mensagem,
            "script" => $script));
    }

    /**
     * listarPropostasAction
     *
     * @access public
     * @return void
     */
    public function listarPropostasAction() {

        // Desativei essa view para a outra abaixo
        $this->_redirect("proposta/manterpropostaincentivofiscal/listarproposta");
    }

    /**
     * listarpropostaAction
     *
     * @access public
     * @return void
     */
    public function listarpropostaAction()
    {
        $proposta = new Proposta_Model_PreProjeto();
        $dadosCombo = array();
        $cpfCnpj = '';

        $rsVinculo = $proposta->listarPropostasCombo($this->idResponsavel);
        $agente = array();

        $i = 0;
        foreach ($rsVinculo as $rs) {

            if (!in_array($rs->idAgente, $agente)) {
                if (strlen(trim($rs->CNPJCPF)) > 11) {
                    $cpfCnpj = Mascara::addMaskCNPJ($rs->CNPJCPF);
                } else {
                    $cpfCnpj = Mascara::addMaskCPF($rs->CNPJCPF);
                }

                $dadosCombo[$i]['idAgenteProponente'] = $rs->idAgente;
                $dadosCombo[$i]['CPF'] = $cpfCnpj;
                $dadosCombo[$i]['Nome'] = $rs->NomeProponente;
            }
            $i++;
        }

        $this->view->dadosCombo = $dadosCombo;
        $this->view->idResponsavel = $this->idResponsavel;
        $this->view->idUsuario = $this->idUsuario;
    }

    /**
     * localizarPropostaAction
     *
     * @access public
     * @return void
     * @todo retirar html
     */
    public function localizarPropostaAction() {

        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();

        $get = Zend_Registry::get('get');
        $idAgente = $get->idAgente;

        $tblPreProjeto = new Proposta_Model_PreProjeto();
        $rsPreProjeto = $tblPreProjeto->listarPropostasResultado($this->idAgente, $this->idResponsavel, $idAgente);

        $arrPropostas = array();
        $i = 0;
        $x = 0;
        $identificadores = array();
        foreach ($rsPreProjeto as $prop) {
            if(!in_array($prop->idAgente.$prop->idPreProjeto, $identificadores)) {
                $arrPropostas[$x]['CNPJCPF'] = $prop->CNPJCPF;
                $arrPropostas[$x]['idAgente'] = $prop->idAgente;
                $arrPropostas[$x]['NomeProponente'] = utf8_encode($prop->NomeProponente);
                $arrPropostas[$x]['idPreProjeto'] = $prop->idPreProjeto;
                $arrPropostas[$x]['NomeProjeto'] = utf8_encode($prop->NomeProjeto);
                $x++;
            }
            $identificadores[$i] = $prop->idAgente.$prop->idPreProjeto;
            $i++;
        }

        if(count($rsPreProjeto) > 0){
            $this->montaTela("manterpropostaincentivofiscal/localizarproposta.phtml", array("propostas" => $arrPropostas));
        } else {
            echo "<table class='tabela'><tr><td class='centro'>N&atilde;o foi encontrado nenhum registro.</td></tr></table>";
        }
    }

    /**
     * M�todo consultarresponsaveis()
     * UC 89 - Fluxo FA2 - Aceitar Vinculo
     * @access public
     * @param void
     * @return void
     */
    public function consultarresponsaveisAction() {
        $auth = Zend_Auth::getInstance(); // pega a autentica��o
        $idUsuario = $auth->getIdentity()->IdUsuario;
        $cpf = $auth->getIdentity()->Cpf;

        $Agentes = new Agente_Model_Agentes();
        $buscarpendentes = $Agentes->gerenciarResponsaveisListas('0', $idUsuario);
        $buscarvinculados = $Agentes->gerenciarResponsaveisListas('2', $idUsuario);

        $this->view->pendentes = $buscarpendentes;
        $this->view->vinculados = $buscarvinculados;
        $this->view->cpfLogado = $cpf;
    }

    /**
     * M�todo vincularpropostas()
     * UC 89 - Fluxo FA6 - Vincular Propostas
     * @access public
     * @param void
     * @return void
     */
    public function vincularpropostasAction() {
        $tbVinculo = new TbVinculo();
        $propostas = new Proposta_Model_PreProjeto();

//        $proposta = new Proposta_Model_Proposta();
        $agentes = new Agente_Model_Agentes();
        $dadosCombo = array();
        $rsVinculo = $agentes->listarVincularPropostaCombo($this->idResponsavel);

        $i = 0;
        foreach ($rsVinculo as $rs) {
            $dadosCombo[$i]['idResponsavel'] = $rs->idUsuarioResponsavel;
            $dadosCombo[$i]['idVinculo'] = $rs->idVinculo;
            $dadosCombo[$i]['NomeResponsavel'] = $rs->NomeResponsavel;
            $i++;
        }

        //ADICIONA AO ARRAY O IDAGENTE DO USUARIO LOGADO
        $dadosIdAgentes = array($this->idAgenteProponente);

        //VERIFICA SE O USUARIO LOGADO EH DIRIGENTE DE ALGUMA EMPRESA
        $Vinculacao = new Vinculacao();
        $rsVinculucao = $Vinculacao->verificarDirigenteIdAgentes($this->cpfLogado);

        //CASO RETORNE ALGUM RESULTADO, ADICIONA OS IDAGENTE'S DE CADA UM AO ARRAY
        if(count($rsVinculucao)>0){
            foreach ($rsVinculucao as $value) {
                $dadosIdAgentes[] = $value->idAgente;
            }
        }

        //PROCURA AS PROPOSTAS DE TODOS OS IDAGENTE'S
        $listaPropostas = $propostas->buscarVinculadosProponenteDirigentes($dadosIdAgentes);

        $wherePropostaD['pp.idAgente = ?'] = $this->idAgenteProponente;
        $wherePropostaD['pr.idProjeto IS NULL'] = '';
        $wherePropostaD['pp.idUsuario <> ?'] = $this->idResponsavel;
        $listaPropostasD = $propostas->buscarPropostaProjetos($wherePropostaD);

        $this->view->responsaveis = $dadosCombo;
        $this->view->propostas = $listaPropostas;
        $this->view->propostasD = $listaPropostasD;
    }

    /**
     * M�todo vincularpropostas()
     * UC 89 - Fluxo FA8 - Vincular Propostas
     * @access public
     * @param void
     * @return void
     */
    public function vincularprojetosAction() {
        $tbVinculo = new TbVinculo();
        $propostas = new Proposta_Model_PreProjeto();

        $whereProjetos['pp.idAgente = ?'] = $this->idAgenteProponente;
        $whereProjetos['pp.idUsuario <> ?'] = $this->idResponsavel;
        $whereProjetos['pr.idProjeto IS NOT NULL'] = '';
        $listaProjetos = $propostas->buscarPropostaProjetos($whereProjetos);

        $this->view->projetos = $listaProjetos;
    }

    /**
     * M�todo novoresponsavel()
     * UC 89 - Fluxo FA4 - Vincular Respons�vel
     * @access public
     * @param void
     * @return void
     */
    public function novoresponsavelAction() {

    }

    /**
     * M�todo resnovoresponsavel()
     * Retorno do novoresponsavel
     * UC 89 - Fluxo FA4 - Vincular Respons�vel
     * @access public
     * @param void
     * @return void
     * @todo retirar html
     */
    public function respnovoresponsavelAction() {

        $this->_helper->layout->disableLayout();

        $cnpjcpf = Mascara::delMaskCPF($this->_request->getParam("cnpjcpf"));
        $nome = $this->_request->getParam("nome");

        $tbVinculo = new TbVinculo();

        if ((empty($cnpjcpf)) && (empty($nome))) {
            echo "<table class='tabela'>
					<tr>
					    <td class='red' align='center'>Voc� deve preencher pelo menos um campo!</td>
					</tr>
				</table>";
            exit();
        } elseif (!empty($cnpjcpf)) {
            $where['SGA.Cpf = ?'] = $cnpjcpf;
        } elseif (!empty($nome)) {
            $where['SGA.Nome like (?)'] = "%" . utf8_decode($nome) . "%";
        }

        $busca = $tbVinculo->buscarResponsaveis($where, $this->idAgenteProponente);

        $this->view->dados = $busca;
        $this->view->dadoscount = count($busca);
        $this->view->idAgenteProponente = $this->idAgenteProponente;
    }
}
