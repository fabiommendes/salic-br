<?php
/**
 * @author Caio Lucena <caioflucena@gmail.com>
 */

class FornecedorModel extends Minc_Db_Table_Abstract
{
    protected $_banco = 'agentes';
    protected $_name = 'agentes';
    protected $_schema = 'agentes';
    protected $_primary = 'idAgente';
    
    public function pesquisarFornecedorItem($item)
    {
        $select = "SELECT 'COTA&ccedil;&atilde;O' as Modalidade, b.idAgente,e.CNPJCPF,F.Descricao AS Fornecedor,c.idPlanilhaAprovacao,d.IdPRONAC
                FROM  bdcorporativo.scsac.tbCotacao a
                INNER JOIN bdcorporativo.scsac.tbCotacaoxAgentes b on (a.idCotacao = b.idCotacao)
                INNER JOIN bdcorporativo.scsac.tbCotacaoxPlanilhaAprovacao c on (b.idCotacaoxAgentes = c.idCotacaoxAgentes)
                INNER JOIN sac.DBO.tbPlanilhaAprovacao d on (c.idPlanilhaAprovacao = d.idPlanilhaAprovacao)
                INNER JOIN agentes.dbo.Agentes e on (b.idAgente  =e.idAgente)
                INNER JOIN agentes.dbo.Nomes f on (e.idAgente = f.idAgente)
                WHERE d.stAtivo = 'S' AND D.idPlanilhaAprovacao = ?
                UNION ALL
                SELECT 'DISPENSA' as Modalidade, a.idAgente,d.CNPJCPF,e.Descricao AS Fornecedor,c.idPlanilhaAprovacao,c.IdPRONAC
                FROM  bdcorporativo.scsac.tbDispensaLicitacao a
                INNER JOIN bdcorporativo.scsac.tbDispensaLicitacaoxPlanilhaAprovacao b on (a.idDispensaLicitacao = b.idDispensaLicitacao)
                INNER JOIN sac.DBO.tbPlanilhaAprovacao c on (b.idPlanilhaAprovacao = c.idPlanilhaAprovacao)
                INNER JOIN agentes.dbo.Agentes d on (a.idAgente  = d.idAgente)
                INNER JOIN agentes.dbo.Nomes e on (d.idAgente = e.idAgente)
                WHERE c.stAtivo = 'S' AND c.idPlanilhaAprovacao = ?
                UNION ALL
                SELECT 'LICITA&ccedil;AO' as Modalidade, b.idAgente,e.CNPJCPF,F.Descricao AS Fornecedor,c.idPlanilhaAprovacao,d.IdPRONAC
                FROM  bdcorporativo.scsac.tbLicitacao a
                INNER JOIN bdcorporativo.scsac.tbLicitacaoxAgentes b on (a.idLicitacao = b.idLicitacao)
                INNER JOIN bdcorporativo.scsac.tbLicitacaoxPlanilhaAprovacao c on (b.idLicitacao = c.idLicitacao)
                INNER JOIN sac.DBO.tbPlanilhaAprovacao d on (c.idPlanilhaAprovacao = d.idPlanilhaAprovacao)
                INNER JOIN agentes.dbo.Agentes e on (b.idAgente  =e.idAgente)
                INNER JOIN agentes.dbo.Nomes f on (e.idAgente = f.idAgente)
                WHERE b.stVencedor = 1 AND d.stAtivo = 'S' AND D.idPlanilhaAprovacao = ?
                UNION ALL
                SELECT 'CONTRATO' as Modalidade, b.idAgente,e.CNPJCPF,F.Descricao AS Fornecedor,c.idPlanilhaAprovacao,d.IdPRONAC
                FROM  bdcorporativo.scsac.tbContrato a
                INNER JOIN bdcorporativo.scsac.tbContratoxAgentes b on (a.idContrato = b.idContrato)
                INNER JOIN bdcorporativo.scsac.tbContratoxPlanilhaAprovacao c on (b.idContrato = c.idContrato)
                INNER JOIN sac.DBO.tbPlanilhaAprovacao d on (c.idPlanilhaAprovacao = d.idPlanilhaAprovacao)
                INNER JOIN agentes.dbo.Agentes e on (b.idAgente  =e.idAgente)
                INNER JOIN agentes.dbo.Nomes f on (e.idAgente = f.idAgente)
                WHERE d.stAtivo = 'S' AND D.idPlanilhaAprovacao = ?";
        $bind = array(
            $item, // cotacao
            $item, // dispensa
            $item, // licitacao
            $item, // contrato
        );
        $db = Zend_Db_Table::getDefaultAdapter();
        $stmt = $db->query($select, $bind);
        return $stmt->fetch();
    }

    public function pesquisarFornecedor($fornecedor)
    {
        $stmt = $this->getAdapter()->query(
            "SELECT * FROM agentes.dbo.Agentes AS a
                INNER JOIN agentes.dbo.Nomes AS b ON a.idAgente = b.idAgente
            WHERE a.idAgente = ?",
            $fornecedor
        );
        return $stmt->fetchObject();
    }
}
