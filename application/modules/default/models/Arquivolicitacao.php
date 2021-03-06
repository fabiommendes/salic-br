<?php

/**
 * Description of Arquivolicitacao
 *
 * @author 01610881125
 */
class Arquivolicitacao extends MinC_Db_Table_Abstract{

    protected $_banco   = 'bdcorporativo';
    protected $_name    = 'tbArquivoLicitacao';
    protected $_schema  = 'bdcorporativo.scSAC';

    public function buscarArquivos($idlicitacao){
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
                        array('al'=>$this->_name),
                        array(
                                'al.idArquivo'
                              )
                      );

        $select->joinInner(
                            array('arq'=>'tbArquivo'),
                            'arq.idArquivo = al.idArquivo',
                            array('arq.nmArquivo','arq.sgExtensao','arq.nrTamanho'),
                            'bdcorporativo.scCorp'
                           );
        $select->where('al.idlicitacao = ?', $idlicitacao);

        return $this->fetchAll($select);
    }

}
