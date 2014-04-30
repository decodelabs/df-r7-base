<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\native;

use df;
use df\core;
use df\opal;
use df\user;

class QuerySourceAdapter implements opal\query\IAdapter {
    
    use user\TAccessLock;

    protected $_dataSourceId;
    protected $_rows;
    
    public function __construct($dataSourceId, array $rows) {
        $this->_dataSourceId = $dataSourceId;
        $this->_rows = $rows;
    }
    
    public function getQuerySourceId() {
        return $this->_dataSourceId;
    }
    
    public function getQuerySourceAdapterHash() {
        return md5($this->_dataSourceId);
    }
    
    public function getQuerySourceDisplayName() {
        return $this->_dataSourceId;
    }

    public function getDelegateQueryAdapter() {
        return null;
    }

    public function newRecord(array $values=null) {
        return new opal\record\Base($this, $values);
    }

    public function newPartial(array $values=null) {
        return new opal\record\Partial($this, $values);
    }


    public function supportsQueryType($type) {
        switch($type) {
            case opal\query\IQueryTypes::SELECT:
            case opal\query\IQueryTypes::FETCH:
                
            case opal\query\IQueryTypes::CORRELATION:

            case opal\query\IQueryTypes::JOIN:
            case opal\query\IQueryTypes::JOIN_CONSTRAINT:
            case opal\query\IQueryTypes::REMOTE_JOIN:
                
            case opal\query\IQueryTypes::SELECT_ATTACH:
            case opal\query\IQueryTypes::FETCH_ATTACH:
                return true;
                
            default:
                return false;
        }
    }

    public function supportsQueryFeature($feature) {
        switch($feature) {
            case opal\query\IQueryFeatures::AGGREGATE:
            case opal\query\IQueryFeatures::WHERE_CLAUSE:
            case opal\query\IQueryFeatures::GROUP_DIRECTIVE:
            case opal\query\IQueryFeatures::HAVING_CLAUSE:
            case opal\query\IQueryFeatures::ORDER_DIRECTIVE:
            case opal\query\IQueryFeatures::LIMIT:
            case opal\query\IQueryFeatures::OFFSET:
                return true;
                
            default:
                return false;
        }
    }

    public function handleQueryException(opal\query\IQuery $query, \Exception $e) {
        return false;
    }

    public function ensureStorageConsistency() {}

    
    public function executeSelectQuery(opal\query\ISelectQuery $query) {
        $manipulator = new opal\query\result\ArrayManipulator($query->getSource(), $this->_fetchData($query), true);
        return $manipulator->applyReadQuery($query);
    }
    
    public function countSelectQuery(opal\query\ISelectQuery $query) {
        $manipulator = new opal\query\result\ArrayManipulator($query->getSource(), $this->_fetchData($query), true);
        return $manipulator->applyReadQuery($query, true);
    }
    
    public function executeUnionQuery(opal\query\IUnionQuery $query) {
        throw new opal\query\LogicException(
            'Native data adapter does not support union queries'
        );
    }
    
    public function countUnionQuery(opal\query\IUnionQuery $query) {
        throw new opal\query\LogicException(
            'Native data adapter does not support union queries'
        );
    }


    public function executeFetchQuery(opal\query\IFetchQuery $query) {
        $manipulator = new opal\query\result\ArrayManipulator($query->getSource(), $this->_fetchData($query), true);
        return $manipulator->applyReadQuery($query);
    }
    
    public function countFetchQuery(opal\query\IFetchQuery $query) {
        $manipulator = new opal\query\result\ArrayManipulator($query->getSource(), $this->_fetchData($query), true);
        return $manipulator->applyReadQuery($query, true);
    }
    
    public function executeInsertQuery(opal\query\IInsertQuery $query) {}
    public function executeBatchInsertQuery(opal\query\IBatchInsertQuery $query) {}
    public function executeReplaceQuery(opal\query\IReplaceQuery $query) {}
    public function executeBatchReplaceQuery(opal\query\IBatchReplaceQuery $query) {}
    public function executeUpdateQuery(opal\query\IUpdateQuery $query) {}
    public function executeDeleteQuery(opal\query\IDeleteQuery $query) {}

    public function fetchRemoteJoinData(opal\query\IJoinQuery $join, array $rows) {
        return $this->_fetchData($join);
    }
    
    public function fetchAttachmentData(opal\query\IAttachQuery $attachment, array $rows) {
        $manipulator = new opal\query\result\ArrayManipulator($attachment->getSource(), $this->_fetchData($attachment), true);
        return $manipulator->applyAttachmentDataQuery($attachment);
    }
    
    protected function _fetchData(opal\query\IQuery $query) {
        $data = [];
        $sourceAlias = $query->getSource()->getAlias();
        
        foreach($this->_rows as $origRow) {
            if($origRow instanceof opal\query\IDataRowProvider) {
                $temp = $origRow->toDataRowArray();
            } else if($origRow instanceof core\IArrayProvider) {
                $temp = $origRow->toArray();
            } else {
                $temp = $origRow;
            }
            
            if(!is_array($temp)) {
                throw new opal\query\UnexpectedValueException(
                    'Data source rows must be convertible to an array'
                );
            }
            
            $row = [];
            
            foreach($temp as $key => $value) {
                $row[$sourceAlias.'.'.$key] = $value;
            }
            
            if(is_object($origRow)) {
                $row[$sourceAlias.'.@object'] = $origRow;
            }
            
            $data[] = $row;
        }
        
        // delete me
        $data = array_reverse($data);
        
        return $data;
    }


    public function beginQueryTransaction() {}
    public function commitQueryTransaction() {}
    public function rollbackQueryTransaction() {}


    public function getAccessLockDomain() {
        return 'nativeData';
    }

    public function lookupAccessKey(array $keys, $action=null) {
        $id = $this->getUnitId();

        $parts = explode(IUnit::ID_SEPARATOR, $id);
        $test = $parts[0].IUnit::ID_SEPARATOR;

        if($action !== null) {
            if(isset($keys[$id.'#'.$action])) {
                return $keys[$id.'#'.$action];
            }

            if(isset($keys[$test.'*#'.$action])) {
                return $keys[$test.'*#'.$action];
            }

            if(isset($keys[$test.'%#'.$action])) {
                return $keys[$test.'%#'.$action];
            }

            if(isset($keys['*#'.$action])) {
                return $keys['*#'.$action];
            }
        }


        if(isset($keys[$id])) {
            return $keys[$id];
        }

        if(isset($keys[$test.'*'])) {
            return $keys[$test.'*'];
        }

        if(isset($keys[$test.'%'])) {
            return $keys[$test.'%'];
        }

        return null;
    }

    public function getDefaultAccess($action=null) {
        return user\IState::ALL;
    }

    public function getAccessLockId() {
        return $this->_dataSourceId;
    }
}
