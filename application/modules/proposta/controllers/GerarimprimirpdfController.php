<?php

/**
 * AnexarDocumentosController
 * @author Equipe RUP - Politec
 * @author wouerner <wouerner@gmail.com>
 * @since 28/04/2010
 * @link http://www.cultura.gov.br
 */
//require_once "GenericControllerNew.php";

class Proposta_GerarimprimirpdfController extends MinC_Controller_Action_Abstract
{
    public function init()
    {

        $this->view->title = "Salic - Sistema de Apoio &agrave;s Leis de Incentivo &agrave; Cultura"; // titulo da pagina
        $auth = Zend_Auth::getInstance(); // pega a autenticacao
        $Usuario = new UsuarioDAO(); // objeto usuario
        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo'); // cria a sess�o com o grupo ativo

        if ($auth->hasIdentity()) // caso o usuario esteja autenticado
        {
            // verifica as permissoes
            $PermissoesGrupo = array();
            $PermissoesGrupo[] = 93;  // Coordenador de Parecerista
            $PermissoesGrupo[] = 94;  // Parecerista
            $PermissoesGrupo[] = 103; // Coordenador de Analise
            $PermissoesGrupo[] = 118; // Componente da Comissao
            $PermissoesGrupo[] = 119; // Presidente da Mesa
            $PermissoesGrupo[] = 120; // Coordenador Administrativo CNIC
            //if (!in_array($GrupoAtivo->codGrupo, $PermissoesGrupo)) // verifica se o grupo ativo est� no array de permiss�es
            //{
            //    parent::message("Voc� n�o tem permiss�o para acessar essa �rea do sistema!", "principal/index", "ALERT");
            //}

            // pega as unidades autorizadas, org�os e grupos do usu�rio (pega todos os grupos)
//            $grupos = $Usuario->buscarUnidades($auth->getIdentity()->usu_codigo, 21);
//
//            // manda os dados para a visao
//            $this->view->usuario = $auth->getIdentity(); // manda os dados do usuario para a visao
//            $this->view->arrayGrupos = $grupos; // manda todos os grupos do usuario para a visao
//            $this->view->grupoAtivo = $GrupoAtivo->codGrupo; // manda o grupo ativo do usuario para a visao
//            $this->view->orgaoAtivo = $GrupoAtivo->codOrgao; // manda o orgao ativo do usuario para a visao

        } // fecha if
        else // caso o usuario nao esteja autenticado
        {
            return $this->_helper->redirector->goToRoute(array('controller' => 'index', 'action' => 'logout'), null, true);
        }
        parent::init(); // chama o init() do pai GenericControllerNew
    }
    public function indexAction(){
       $this->_helper->layout->disableLayout();

       function verifica($var){
           if($var or $var == 1){
               return "Sim";
           }else{
               return "N&atilde;o";
           }
       }
       function data($data){
           if(!empty ($data)){
                $dataF = new data($data);
                return $dataF->dataBrasileira($dataF->tratarDataZend($data, "americano"));
           }
       }
       function tratatexto($valor){

           $valor = str_replace('<br>', '|br|', $valor);
           $valor = str_replace('</p>', '|br|', $valor);
           $valor = strip_tags($valor);
           $valor = str_replace('|br|', '<br>', $valor);
           return $valor;

       }

        $id_projeto = $_REQUEST['idPreProjeto'];
        $this->view->id_projeto = $id_projeto;
        $tblPreProjeto = new Proposta_Model_DbTable_PreProjeto();

        $rsDadosProjeto = array_change_key_case( $tblPreProjeto->buscaCompleta(array("idPreProjeto = ?"=>$id_projeto))->current()->toArray() );
        $this->view->rsDadosProjeto = $rsDadosProjeto;

        $tbAbrangencia = new Proposta_Model_DbTable_Abrangencia();
        $this->view->rsAbrangencias = $tbAbrangencia->buscar( array("idProjeto"=>$id_projeto) );

        $tblPlanoDivulgacao = new Proposta_Model_DbTable_PlanoDeDivulgacao();
        $this->view->rsPlanoDivulgacao = $tblPlanoDivulgacao->buscar(array("pd.idprojeto = ?" => $id_projeto)); //busca dados de divulgacao do preprojeto

        $tblPlanoDistribuicao = new PlanoDistribuicao();
        $this->view->rsPlanoDistribuicao = $tblPlanoDistribuicao->buscar( array("a.idprojeto = ?" => $id_projeto), array("idplanodistribuicao DESC"));

        $tblPlanilhaProposta = new Proposta_Model_DbTable_PlanilhaProposta;
        $this->view->rsOrcamento = $tblPlanilhaProposta->Orcamento($id_projeto);

        if (($rsDadosProjeto['mecanismo'] == "1") AND ( $rsDadosProjeto['idedital'] == NULL OR ($rsDadosProjeto['idedital'] == "0" ))) {
            $this->view->mecanismo = 'Incentivo Fiscal';
        } else {
            $this->view->mecanismo = 'FNC';
        }

        $this->_helper->layout->disableLayout();

        $html = $this->view->render('/gerarimprimirpdf/index.phtml');

        $pdf = new PDF($html, 'pdf');

        $pdf->gerarRelatorio();

        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
}
