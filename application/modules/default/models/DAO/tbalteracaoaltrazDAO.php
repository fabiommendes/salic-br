<?php

class tbalteracaoaltrazDAO extends Zend_Db_Table
{
    protected $_name = "bdcorporativo.scSAC.tbalteracaorazaosocialprojeto";

    public static function buscarDadosAltRaz($idpedidoalteracao)
    {
        $sql = "select       
                       trsp.nmrazaosocial,
                       trsp.dsjustificativa,
                       tap.idPRONAC,
                       prepr.idAgente
                       from bdcorporativo.scSAC.tbalteracaorazaosocialprojeto trsp
                       join bdcorporativo.scSAC.tbPedidoAlteracaoProjeto tap on tap.idPedidoAlteracao = trsp.idPedidoAlteracao
                       join SAC.dbo.Projetos pr on pr.IdPRONAC = tap.idPRONAC
                       join SAC.dbo.PreProjeto prepr on prepr.idPreProjeto = pr.idProjeto
                       where trsp.idpedidoalteracao= ".$idpedidoalteracao;

        $db= Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        return $db->fetchAll($sql);
    }
    
    public static function alterarRazaoSocialProjeto($dados, $idagente)
    {
        $db= Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);

        $where = "idagente = ".$idagente;
        $alterar = $db->update("agentes.dbo.Nomes", $dados, $where);

        if ($alterar)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

}

?>
