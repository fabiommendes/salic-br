<?php
/**
 * Description of tbPedidoAlteracaoProjeto
 *
 * @author 01610881125
 */
class tbPedidoAlteracaoProjeto extends MinC_Db_Table_Abstract {
    /* dados da tabela */
    protected $_banco   = "bdcorporativo";
    protected $_schema  = "bdcorporativo.scSAC";
    protected $_name    = "tbPedidoAlteracaoProjeto";


    public function buscarAtoresReadequacao($idPronac) {
        $select = $this->select();
        $select->setIntegrityCheck(false);

        $select->from(
                array('pap'=>$this->_name),
                array('Perfil3'=>new Zend_Db_Expr("'Coordenador de Acompanhamento'"),'cdPerfil3'=>new Zend_Db_Expr("'122'"))
        );

        $select->joinInner(
                array('paxta'=>'tbPedidoAlteracaoXTipoAlteracao'),
                'paxta.idPedidoAlteracao = pap.idPedidoAlteracao',
                array(),
                'bdcorporativo.scSAC'
        );
        $select->joinInner(
                array('aipa'=>'tbAvaliacaoItemPedidoAlteracao'),
                'aipa.idPedidoAlteracao = pap.idPedidoAlteracao',
                array('idAgente3'=>'aipa.idAgenteAvaliador'),
                'bdcorporativo.scSAC'
        );
        $select->joinInner(
                array('aaipa'=>'tbAcaoAvaliacaoItemPedidoAlteracao'),
                'aaipa.idAvaliacaoItemPedidoAlteracao = aipa.idAvaliacaoItemPedidoAlteracao',
                array('idAgente'=>'aaipa.idAgenteRemetente','idAgente2'=>'aaipa.idAgenteAcionado'),
                'bdcorporativo.scSAC'
        );
        $select->joinInner(
                array('ta'=>'tbTipoAgente'),
                'ta.idTipoAgente = aaipa.idTipoAgente',
                array(),
                'bdcorporativo.scSAC'
        );
        $select->joinInner(
                array('gru2'=>'Grupos'),
                'gru2.gru_nome = ta.dsTipoAgente',
                array('Perfil2'=>'gru.gru_nome','cdPerfil2'=>'gru.gru_codigo'),
                'TABELAS'
        );
        $select->joinInner(
                array('gru'=>'Grupos'),
                'gru.gru_codigo = aaipa.idPerfilRemetente',
                array('Perfil'=>'gru.gru_nome','cdPerfil'=>'gru.gru_codigo'),
                'TABELAS'
        );
        $select->joinInner(
                array('nm'=>'Nomes'),
                'nm.idAgente = aaipa.idAgenteRemetente',
                array('Nome'=>'nm.Descricao'),
                'agentes'
        );
        $select->joinLeft(
                array('nm2'=>'Nomes'),
                'nm2.idAgente = aaipa.idAgenteAcionado',
                array('Nome2'=>'nm2.Descricao'),
                'agentes'
        );
        $select->joinInner(
                array('nm3'=>'Nomes'),
                'nm3.idAgente = aipa.idAgenteAvaliador',
                array('Nome3'=>'nm3.Descricao'),
                'agentes'
        );
        $select->joinInner(
                array('org'=>'Orgaos'),
                'org.Codigo = aaipa.idOrgao',
                array('Orgao'=>'org.Sigla'),
                'SAC'
        );

        $select->where('pap.IdPRONAC = ?', $idPronac);

        return $this->fetchAll($select);
    }

    public function buscarPedidoAlteracaoPorTipoAlteracao($where=array(), $order=array()) {
        $select = $this->select();
        $select->setIntegrityCheck(false);

        $select->from(array('pa'=>$this->_name),
                array('*'));

        $select->joinInner(array('paxta'=>'tbPedidoAlteracaoXTipoAlteracao'),
                'paxta.idPedidoAlteracao = pa.idPedidoAlteracao',
                array('*'),
                'bdcorporativo.scSAC');

        // adicionando clausulas where
        foreach ($where as $coluna => $valor) {
            $select->where($coluna, $valor);
        }

        //adicionando linha order ao select
        $select->order($order);


        return $this->fetchAll($select);
    }



    /**
     * Model com os Projetos enviados para o checklist
     */
    public function buscarProjetosCheckList($where=array(), $order=array(), $tamanho=-1, $inicio=-1) {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(array('re' => $this->_name), array('re.idPedidoAlteracao'));
        $slct->joinInner(array('rex' => 'tbPedidoAlteracaoXTipoAlteracao'),
                're.idPedidoAlteracao = rex.idPedidoAlteracao',
                array('tpAlteracaoProjeto' => new Zend_Db_Expr("CASE WHEN rex.tpAlteracaoProjeto = 1 THEN 'Nome do Proponente'
													WHEN rex.tpAlteracaoProjeto = 2 THEN 'Troca de Agente'
													WHEN rex.tpAlteracaoProjeto = 3 THEN 'Ficha T&eacute;cnica'
													WHEN rex.tpAlteracaoProjeto = 4 THEN 'Local de Realiza&ccedil;&atilde;o'
													WHEN rex.tpAlteracaoProjeto = 5 THEN 'Nome do Projeto'
													WHEN rex.tpAlteracaoProjeto = 6 THEN 'Proposta Pedag&oacute;gica'
													WHEN rex.tpAlteracaoProjeto = 7 THEN 'Plano de Distribui&ccedil;&atilde;o'
													WHEN rex.tpAlteracaoProjeto = 8 THEN 'Prorroga&ccedil;&atilde;o de Prazo de Capta&ccedil;&atilde;o'
													WHEN rex.tpAlteracaoProjeto = 9 THEN 'Prorroga&ccedil;&atilde;o de Prazo de Execu&ccedil;&atilde;o'
													ELSE 'Itens de Custo' END"),
                ),'bdcorporativo.scSAC'
        );
        $slct->joinInner(array('pr' => 'Projetos'),
                'pr.IdPRONAC = re.IdPRONAC',
                array('pronac' => New Zend_Db_Expr('pr.AnoProjeto + pr.Sequencial'),
                'pr.NomeProjeto',
                'pr.IdPRONAC',
                'pr.CgcCpf',
                'pr.idpronac',
                'pr.Area as cdarea',
                'pr.Segmento as cdsegmento',
                'pr.ResumoProjeto',
                'pr.UfProjeto',
                'pr.DtInicioExecucao',
                'pr.DtFimExecucao',
                'DtInicioCaptacao' => New Zend_Db_Expr("CASE WHEN DtInicioCaptacao IS NOT NULL
														THEN ap.DtInicioCaptacao 
														ELSE dateadd(day,1,{$this->getDate()}) END"),
                'DtFimCaptacao' => New Zend_Db_Expr("CASE WHEN DtFimCaptacao IS NOT NULL THEN ap.DtFimCaptacao
													WHEN CONVERT(char(10),pr.DtFimExecucao,111) <= CONVERT(char(4),year({$this->getDate()})) + '/12/31' THEN pr.DtFimExecucao 
													ELSE CONVERT(char(4),year({$this->getDate()})) + '/12/31' END"),
                ),'SAC'
        );
        $slct->joinInner(array('ap' => 'Aprovacao'),
                'ap.idPronac = pr.idPronac AND ap.DtAprovacao in (SELECT TOP 1 MAX(DtAprovacao) FROM sac..Aprovacao WHERE IdPRONAC = pr.IdPRONAC)',
                array(
                'ap.idAprovacao',
                'ap.DtPublicacaoAprovacao',
                'ap.PortariaAprovacao',
                'ap.DtInicioCaptacao as DtInicioCaptacaoGravada',
                'ap.DtFimCaptacao as DtFimCaptacaoGravada',
                'AprovadoReal' => new Zend_Db_Expr('sac.dbo.fnTotalAprovadoProjeto(pr.AnoProjeto,pr.Sequencial)')
                ),'SAC'
        );
        $slct->joinInner(array('ar' => 'Area'),
                'ar.Codigo = pr.Area',
                array('ar.Descricao AS area'),
                'SAC'
        );
        $slct->joinInner(
                array('seg' => 'Segmento'),
                'seg.Codigo = pr.Segmento',
                array('seg.Descricao as segmento'),
                'SAC'
        );
        $slct->joinInner(array('en'  => 'Enquadramento'),
                'en.IdPRONAC = pr.IdPRONAC',
                array('en.Enquadramento as nrenq',
                'en.Observacao',
                'enquadramento' => new Zend_Db_Expr("case when en.Enquadramento = 1 then '26' when en.Enquadramento = 2 then '18' end ")
                ),'SAC'
        );
        $slct->joinLeft(array('tp' => 'tbPauta'),
                'tp.IdPRONAC = pr.IdPRONAC AND tp.dtEnvioPauta in (SELECT TOP 1 Max(dtEnvioPauta) FROM bdcorporativo.scsac.tbPauta WHERE  IdPRONAC = pr.IdPRONAC)',
                array(),
                'bdcorporativo.scSAC'
        );
        $slct->joinLeft(array('tr' => 'tbReuniao'),
                'tr.idNrReuniao = tp.idNrReuniao',
                array('tr.NrReuniao'),
                'SAC'
        );
        $slct->joinInner(array('ag' => 'Agentes'),
                'ag.CNPJCPF = pr.CgcCpf',
                array(),
                'agentes'
        );
        $slct->joinInner(array('nm' => 'Nomes'),
                'nm.idAgente = ag.idAgente',
                array('nm.Descricao as nome'),
                'agentes'
        );
        $slct->joinLeft(array('vp' => 'tbVerificaProjeto'),
                'vp.IdPRONAC = pr.IdPRONAC',
                array('vp.idUsuario',
                'NomeTecnico' => new Zend_Db_Expr('(SELECT top 1 usu_nome FROM TABELAS.dbo.Usuarios tecnico WHERE tecnico.usu_codigo = vp.idUsuario)'),
                'vp.stAnaliseProjeto',
                'status' => new Zend_Db_Expr("CASE WHEN vp.stAnaliseProjeto IS NULL THEN 'Aguardando An&aacute;lise'
												WHEN vp.stAnaliseProjeto = '1' THEN 'Aguardando An&aacute;lise'
												WHEN vp.stAnaliseProjeto = '2' THEN 'Em An&aacute;lise'
												WHEN vp.stAnaliseProjeto = '3' THEN 'An&aacute;lise Finalizada'
												WHEN vp.stAnaliseProjeto = '4' THEN 'Encaminhado para portaria'
												END "),
                'DATEDIFF(day, vp.DtRecebido, '.$this->getDate().') AS tempoAnalise',
                'vp.dtRecebido'
                ),
                'SAC'
        );

        // adiciona quantos filtros foram enviados
        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        // adicionando linha order ao select
        $slct->order($order);

        // paginacao
        if ($tamanho > -1) {
            $tmpInicio = 0;
            if ($inicio > -1) {
                $tmpInicio = $inicio;
            }
            $slct->limit($tamanho, $tmpInicio);
        }


        return $this->fetchAll($slct);
    } // fecha m�todo buscarProjetosCheckList()


    public function verificarProdutoSemItem($idPedidoAlteracao) {
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
                array('a'=>$this->_name),
                array('')
        );
        $select->joinInner(
                array('b'=>'tbPlanoDistribuicao'), 'a.idPedidoAlteracao = b.idPedidoAlteracao',
                array('idProduto'), 'SAC'
        );
        $select->joinInner(
                array('c'=>'tbPedidoAlteracaoXTipoAlteracao'), 'a.idPedidoAlteracao = c.idPedidoAlteracao',
                array(''), 'bdcorporativo.scSAC'
        );
        $select->where('a.idPedidoAlteracao = ?', $idPedidoAlteracao);
        $select->where('c.tpAlteracaoProjeto = ?', 7);
        $select->where('b.tpAcao = ?', 'I');
        $select->where(new Zend_Db_Expr("NOT EXISTS(SELECT TOP 1 * FROM sac.DBO.tbPlanilhaAprovacao d WHERE d.tpPlanilha = 'SR' AND d.stAtivo = 'N' AND b.idProduto = d.idProduto AND a.idPronac = d.idPronac)"));

        return $this->fetchAll($select);
    }

    public function painelCoordAcomp($where=array(), $order=array(), $tamanho=-1, $inicio=-1, $qtdeTotal=false) {
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->distinct();
        $select->from(
            array('a' => $this->_name),
            array('IdPRONAC', 'dtSolicitacao', 'stPedidoAlteracao'), 'bdcorporativo.scSAC'
        );

        $select->joinInner(
            array('b' => 'Projetos'), 'a.IdPRONAC = b.IdPRONAC',
            array(new Zend_Db_Expr('AnoProjeto+Sequencial AS PRONAC'), 'NomeProjeto', 'Orgao'), 'SAC'
        );

        $select->joinInner(
            array('c' => 'Area'), 'b.Area = c.Codigo',
            array('Descricao AS Area'), 'SAC'
        );

        $select->joinLeft(
            array('d' => 'Segmento'), 'b.Segmento = d.Codigo',
            array('Descricao AS Segmento'), 'SAC'
        );

        $select->joinInner(
            array('e' => 'tbPedidoAlteracaoXTipoAlteracao'), 'a.idPedidoAlteracao = e.idPedidoAlteracao',
            array('idPedidoAlteracao', 'tpAlteracaoProjeto'), 'bdcorporativo.scSAC'
        );

       //adiciona quantos filtros foram enviados
        foreach ($where as $coluna => $valor) {
            $select->where($coluna, $valor);
        }

        if ($qtdeTotal){

            return $this->fetchAll($select)->count();
        }

        //adicionando linha order ao select
        $select->order($order);

        // paginacao
        if ($tamanho > -1) {
            $tmpInicio = 0;
            if ($inicio > -1) {
                $tmpInicio = $inicio;
            }
            $select->limit($tamanho, $tmpInicio);
        }


        return $this->fetchAll($select);
    }

    public function painelCoordAcompDev($where=array(), $order=array(), $tamanho=-1, $inicio=-1, $qtdeTotal=false) {
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->distinct();
        $select->from(
            array('a' => $this->_name),
            array('IdPRONAC', 'stPedidoAlteracao', 'siVerificacao'), 'bdcorporativo.scSAC'
        );
        $select->joinInner(
            array('b' => 'Projetos'), 'a.IdPRONAC = b.IdPRONAC',
            array(new Zend_Db_Expr('AnoProjeto+Sequencial AS PRONAC'), 'NomeProjeto'), 'SAC'
        );
        $select->joinInner(
            array('c' => 'Area'), 'b.Area = c.Codigo',
            array('Descricao AS Area'), 'SAC'
        );
        $select->joinLeft(
            array('d' => 'Segmento'), 'b.Segmento = d.Codigo',
            array('Descricao AS Segmento'), 'SAC'
        );
        $select->joinInner(
            array('e' => 'tbPedidoAlteracaoXTipoAlteracao'), 'a.idPedidoAlteracao = e.idPedidoAlteracao',
            array('idPedidoAlteracao', 'tpAlteracaoProjeto', 'stVerificacao AS stItem'), 'bdcorporativo.scSAC'
        );
        $select->joinInner(
            array('f' => 'tbAvaliacaoItemPedidoAlteracao'), 'e.idPedidoAlteracao = f.idPedidoAlteracao and e.tpAlteracaoProjeto = f.tpAlteracaoProjeto',
            array('dtInicioAvaliacao', 'dtFimAvaliacao', 'idAvaliacaoItemPedidoAlteracao'), 'bdcorporativo.scSAC'
        );
        $select->joinInner(
            array('g' => 'tbAcaoAvaliacaoItemPedidoAlteracao'), 'f.idAvaliacaoItemPedidoAlteracao = g.idAvaliacaoItemPedidoAlteracao',
            array('idOrgao', 'idAcaoAvaliacaoItemPedidoAlteracao AS idAcao', 'stVerificacao AS stAcao'), 'bdcorporativo.scSAC'
        );
       //adiciona quantos filtros foram enviados
        foreach ($where as $coluna => $valor) {
            $select->where($coluna, $valor);
        }
        if ($qtdeTotal){

            return $this->fetchAll($select)->count();
        }
        //adicionando linha order ao select
        $select->order($order);
        // paginacao
        if ($tamanho > -1) {
            $tmpInicio = 0;
            if ($inicio > -1) {
                $tmpInicio = $inicio;
            }
            $select->limit($tamanho, $tmpInicio);
        }

        return $this->fetchAll($select);
    }

} // fecha class
