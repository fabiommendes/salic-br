<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of tbAcesso
 *
 * @author 01129075125
 */
class tbItensPlanilhaProduto extends MinC_Db_Table_Abstract
{

    protected $_schema = 'sac';
    protected $_name = 'tbitensplanilhaproduto';


    /**
     * M�todo para consultar o Valor Real por ano
     * @access public
     * @param array $dados
     * @param integer $where
     * @return integer (quantidade de registros alterados)
     */
    public function buscaItemProduto($where = array())
    {

        try {
            $this->_db->beginTransaction();

            $select = $this->select();
            $select->setIntegrityCheck(false);

            $select->from(
                array('p' => $this->_name),
                array('p.idPlanilhaItens')
            );

            $select->joinInner(
                array('pr' => 'Produto'), 'p.idProduto = pr.Codigo',
                array('pr.Descricao as Produto')
            );

            $select->joinInner(
                array('e' => 'TbPlanilhaEtapa'), 'p.idPlanilhaEtapa = e.idPlanilhaEtapa',
                array('e.Descricao as Etapa')
            );

            $select->joinInner(
                array('i' => 'TbPlanilhaItens'), 'p.idPlanilhaItens = i.idPlanilhaItens',
                array('i.Descricao as Item')
            );

            //adiciona quantos filtros foram enviados
            foreach ($where as $coluna => $valor) {
                $select->where($coluna, $valor);
            }

            $select->order('e.Descricao');
            $select->order('i.Descricao');
            $this->_db->commit();
            return $this->fetchAll($select);
        } catch (Exception $e) {
            $this->_db->rollBack();
            return false;
        }
    }

    public function totalBuscaPaginacao($where = array())
    {

        try {
            $this->_db->beginTransaction();

            $select = $this->select();
            $select->setIntegrityCheck(false);

            $select->from(
                array('p' => $this->_name),
                array('total' => 'count(*)')
            );

            $select->joinInner(
                array('pr' => 'Produto'), 'p.idProduto = pr.Codigo',
                array('pr.Descricao as Produto')
            );

            $select->joinInner(
                array('e' => 'TbPlanilhaEtapa'), 'p.idPlanilhaEtapa = e.idPlanilhaEtapa',
                array('e.Descricao as Etapa')
            );

            $select->joinInner(
                array('i' => 'TbPlanilhaItens'), 'p.idPlanilhaItens = i.idPlanilhaItens',
                array('i.Descricao as Item')
            );

            //adiciona quantos filtros foram enviados
            foreach ($where as $coluna => $valor) {
                $select->where($coluna, $valor);
            }
            $this->_db->commit();
            return $this->fetchAll($select);
        } catch (Exception $e) {
            $this->_db->rollBack();
            return false;
        }
    }

    public function buscarEtapasDoItem($where = array(), $order = array())
    {

        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->distinct();
        $select->from(
            array('p' => $this->_name),
            array('p.idPlanilhaItens',
                'p.idPlanilhaEtapa')
        );

        $select->joinInner(
            array('e' => 'tbPlanilhaEtapa'),
            'p.idPlanilhaEtapa = e.idPlanilhaEtapa',
            array('e.Descricao as Etapa')
        );

        //adiciona quantos filtros foram enviados
        foreach ($where as $coluna => $valor) {
            $select->where($coluna, $valor);
        }

        $select->order($order);
        
        return $this->fetchAll($select);
    }

    public function itensPorItemEEtapaReadequacao($idEtapa, $idProduto)
    {

        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array('a' => $this->_name),
            array('idPlanilhaItens')
        );

        $select->joinInner(
            array('b' => 'tbPlanilhaItens'), 'a.idPlanilhaItens = b.idPlanilhaItens',
            array('Descricao as Item')
        );

        $select->where('a.idPlanilhaEtapa = ?', $idEtapa);
        $select->where('a.idProduto = ?', $idProduto);

        $select->order('2'); // Descricao

        
        return $this->fetchAll($select);
    }

    public function buscarItens($idEtapa, $idproduto = null)
    {
        /*    $sql = "select distinct
                        pp.idPlanilhaItem as idPlanilhaItens,
                        right(i.Descricao,40) as Descricao
                    from sac.dbo.tbPlanilhaProposta pp
                        inner join sac.dbo.tbPlanilhaItens as i on pp.idPlanilhaItem = i.idPlanilhaItens
                        inner join sac.dbo.tbPlanilhaEtapa as e on pp.idEtapa = e.idPlanilhaEtapa
                    where idEtapa = $idEtapa order by i.Descricao "; */
//
//        $sql = "select distinct a.idPlanilhaItens,b.Descricao
//                   FROM sac..tbItensPlanilhaProduto a
//                   INNER JOIN sac..tbPlanilhaItens b on (a.idPlanilhaItens = b.idPlanilhaItens)
//                   WHERE idPlanilhaEtapa = " . $idEtapa . " ";

        $select = $this->select()->distinct();
        $select->setIntegrityCheck(false);
        $select->from(
            array('a' => $this->_name),
            array(
                'a.idPlanilhaItens',
                'b.Descricao'
            ),
            $this->_schema
        );
        $select->joinInner(
            array('b' => 'tbplanilhaitens'), 'a.idPlanilhaItens = b.idPlanilhaItens',
            array(''), $this->_schema
        );
        $select->where('idPlanilhaEtapa = ?',$idEtapa);

        if (!empty($idproduto)) {
            $select->where('idProduto = ?',$idproduto);
        }
        $select->order('b.Descricao');

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        return $db->fetchAll($select);
    }

    public function buscarItem($where = array(), $order = array())
    {

        $select = $this->select()->distinct();
        $select->setIntegrityCheck(false);
        $select->from(
            array('i' => 'tbPlanilhaItens'),
            array(
                'idPlanilhaItens',
                'Descricao'
            ),
            $this->_schema
        );

        //adiciona quantos filtros foram enviados
        foreach ($where as $coluna => $valor) {
            $select->where($coluna, $valor);
        }

        $select->order($order);

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        return $db->fetchRow($select);
    }
}
?>
