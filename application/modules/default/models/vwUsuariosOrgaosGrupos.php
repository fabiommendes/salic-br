<?php
class vwUsuariosOrgaosGrupos extends MinC_Db_Table_Abstract {

    protected $_banco  = 'TABELAS';
    protected $_schema = 'TABELAS';
    protected $_name   = 'vwUsuariosOrgaosGrupos';
    protected $_primary = 'usu_codigo';

    public function buscarUnidadesAutorizadasDoUsuario($gru_nome = 'Admissibilidade')
    {
        $sql = $this->select();
        $sql->setIntegrityCheck(false);
        $sql->from(
            'vwusuariosorgaosgrupos',
            array
            (
                'usu_codigo',
                'usu_nome',
                'usu_orgao'
            , 'usu_orgaolotacao'
            , 'uog_orgao'
            , 'org_siglaautorizado'
            , 'org_nomeautorizado'
            , 'gru_codigo'
            , 'gru_nome'
            , 'org_superior'
            , 'uog_status'
            , 'id_unico'
            ),
            $this->_schema
        );
        $sql->where("gru_nome LIKE '%{$gru_nome}%'");
        $sql->order('usu_nome ASC');
        return $this->fetchAll($sql);
    }

    /**
     * Metodo para buscar as unidades autorizadas do usuario do sistema
     * @access public
     * @param @usu_codigo (codigo do usuario)
     * @param @sis_codigo (codigo sistema)
     * @param @gru_codigo (codigo do grupo)
     * @param @uog_orgao  (codigo do orgao)
     * @return object
     */
    public function carregarPorPareceristaGrupo($intIdUnidade)
    {
        $sql = $this->select();
        $sql->setIntegrityCheck(false);
        $sql->from(
            'vwusuariosorgaosgrupos',
            array
            (
                'usu_codigo',
                'usu_nome',
                'usu_orgao'
            , 'usu_orgaolotacao'
            , 'uog_orgao'
            , 'org_siglaautorizado'
            , 'org_nomeautorizado'
            , 'gru_codigo'
            , 'gru_nome'
            , 'org_superior'
            , 'uog_status'
            , 'id_unico'
            ),
            $this->_schema
        );
        $sql->where("gru_nome LIKE '%Parecerista%'");
        $sql->where("org_superior = ?", $intIdUnidade);
        $sql->order('org_siglaautorizado ASC');
        $sql->order('gru_nome ASC');
        $sql->order('usu_nome ASC');
        $arrResult = $this->fetchAll($sql);
        $arrNew = array();
        foreach ($arrResult as $arrValue) {
            $arrNew[$arrValue['gru_nome']][] = $arrValue->toArray();
        }
        return $arrNew;
    }

    public function carregarPorPareceristaGrupoFetchPairs($intIdUnidade)
    {
        $arrResult = self::carregarPorPareceristaGrupo($intIdUnidade);
        $arrNew = array();
        foreach ($arrResult as $strKey => $arrGrupo) {
            foreach($arrGrupo as $arrValue) {
                $arrNew[$strKey][$arrValue['usu_codigo']] = utf8_encode($arrValue['usu_nome']);
            }
        }
        return $arrNew;
    }


    /**
     * Metodo para buscar as unidades autorizadas do usuario do sistema
     * @access public
     * @param @usu_codigo (codigo do usuario)
     * @param @sis_codigo (codigo sistema)
     * @param @gru_codigo (codigo do grupo)
     * @param @uog_orgao  (codigo do orgao)
     * @return object
     */
    public function carregarUnidade()
    {
        $sql = $this->select();
        $sql->setIntegrityCheck(false);
        $sql->from(
            'Orgaos',
            array
            (
                'Codigo',
                'Sigla',
            ),
            $this->getSchema('sac')
        );
        $auth = Zend_Auth::getInstance(); // pega a autenticacao
        $arrAuth = array_change_key_case((array) $auth->getIdentity());
        $intUsuOrgao = $arrAuth['usu_orgao'];
        if ($intUsuOrgao == 91) {
            $sql->where('idSecretaria = 91');
            $sql->where('Codigo <> 91');
        } else {
            $sql->where('vinculo = 1');
        }
        $sql->where('status = 0');
        $sql->where('stVinculada = 1');
        $sql->order('Sigla ASC');
        $arrResult = $this->fetchAll($sql);
        return $arrResult;
    }

    public function carregarUsuariosPorUnidade($intIdUnidade)
    {

        $sql = $this->select();
        $sql->setIntegrityCheck(false);
        $sql->from(
            'vwusuariosorgaosgrupos',
            array
            (
                'usu_codigo',
                'usu_nome',
                'usu_orgao',
                'usu_orgaolotacao',
            ),
            $this->_schema
        );
        $sql->where("sis_codigo = ?", 21);
        $sql->where("gru_codigo = ?", 94);
        $sql->where("uog_status = ?", 1);
        $sql->where("uog_orgao = ?", $intIdUnidade);
        $sql->order('usu_orgao ASC');
        $sql->order('usu_nome ASC');
        $arrResult = $this->fetchAll($sql);
        $arrNew = array();
        foreach ($arrResult->toArray() as $arrValue) {
            $arrNew[utf8_encode($arrValue['usu_orgaolotacao'])][$arrValue['usu_codigo']] = utf8_encode($arrValue['usu_nome']);
        }
        return $arrNew;
    }

    public function carregarUsuariosPorUnidadeIphan($intIdUnidade)
    {

        $sql = $this->select();
        $sql->setIntegrityCheck(false);
        $sql->from(
            'vwusuariosorgaosgrupos',
            array
            (
                'usu_codigo',
                'usu_nome',
                'usu_orgao',
                'usu_orgaolotacao',
            ),
            $this->_schema
        );
        $sql->where("sis_codigo = ?", 21);
        $sql->where("gru_codigo = ?", 94);
        $sql->where("uog_status = ?", 1);
        $sql->where("uog_orgao = ?", $intIdUnidade);
        $sql->order('usu_orgao ASC');
        $sql->order('usu_nome ASC');
        $arrResult = $this->fetchAll($sql);
        $arrNew = array();
        foreach ($arrResult->toArray() as $arrValue) {
            $arrNew[utf8_encode($arrValue['usu_orgaolotacao'])][$arrValue['usu_codigo']] = utf8_encode($arrValue['usu_nome']);
        }
        return $arrNew;
    }

    public function buscarUsuarios($codPerfil, $codOrgao){
        $sql = "exec sac.dbo.paUsuariosDoPerfil $codPerfil, $codOrgao ";
        $db= Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        return $db->fetchAll($sql);
    }

    public function carregarTecnicosPorUnidadeEGrupo($intIdUnidade, $intIdGrupo)
    {
        $sql = $this->select();
        $sql->setIntegrityCheck(false);
        $sql->from(
            'vwusuariosorgaosgrupos',
            array
            (
                'usu_codigo',
                'usu_nome',
                'usu_orgao',
                'usu_orgaolotacao',
                'org_superior',
            ),
            $this->_schema
        );
        $sql->where("uog_status = ?", 1);
        $sql->where("sis_codigo = ?", 21);
        $sql->where("uog_orgao = ?", $intIdUnidade);
        $sql->where("gru_codigo = ?", $intIdGrupo);
        $sql->order('usu_orgao ASC');
        $sql->order('usu_nome ASC');
        $arrResult = $this->fetchAll($sql);
        return $arrResult;
    }
}

