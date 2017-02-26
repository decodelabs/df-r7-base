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

class QuerySourceAdapter implements opal\query\INaiveIntegralAdapter, opal\query\IEntryPoint {

    use user\TAccessLock;
    use opal\query\TQuery_EntryPoint;

    protected $_dataSourceId;
    protected $_rows;
    protected $_primaryField;

    public function __construct($dataSourceId, array $rows, $primaryField=null) {
        $this->_dataSourceId = $dataSourceId;
        $this->_rows = $rows;
        $this->_primaryField = $primaryField;
    }

    public function getQuerySourceId() {
        return $this->_dataSourceId;
    }

    public function getQuerySourceAdapterHash() {
        return md5($this->_dataSourceId);
    }

    public function getQuerySourceAdapterServerHash() {
        return $this->getQuerySourceAdapterHash();
    }

    public function getQuerySourceDisplayName() {
        return $this->_dataSourceId;
    }

    public function getDelegateQueryAdapter() {
        return null;
    }



    public function getPrimaryIndex() {
        if(!$this->_primaryField) {
            return null;
        }

        return (new opal\schema\GenericIndex('primary', [
                new opal\schema\GenericField($this->_primaryField)
            ]))
            ->isUnique(true);
    }



    public function newRecord(array $values=null) {
        return new opal\record\Base($this, $values);
    }

    public function newPartial(array $values=null) {
        return new opal\record\Partial($this, $values);
    }

    public function shouldRecordsBroadcastHookEvents() {
        return false;
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

    public function handleQueryException(opal\query\IQuery $query, \Throwable $e) {
        return false;
    }

    public function ensureStorageConsistency() {}


    public function executeSelectQuery(opal\query\ISelectQuery $query) {
        $manipulator = new ArrayManipulator($query->getSource(), $this->_fetchData($query), true);
        return $manipulator->applyReadQuery($query);
    }

    public function countSelectQuery(opal\query\ISelectQuery $query) {
        $manipulator = new ArrayManipulator($query->getSource(), $this->_fetchData($query), true);
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
        $manipulator = new ArrayManipulator($query->getSource(), $this->_fetchData($query), true);
        return $manipulator->applyReadQuery($query);
    }

    public function countFetchQuery(opal\query\IFetchQuery $query) {
        $manipulator = new ArrayManipulator($query->getSource(), $this->_fetchData($query), true);
        return $manipulator->applyReadQuery($query, true);
    }

    public function executeInsertQuery(opal\query\IInsertQuery $query) {}
    public function executeBatchInsertQuery(opal\query\IBatchInsertQuery $query) {}
    public function executeUpdateQuery(opal\query\IUpdateQuery $query) {}
    public function executeDeleteQuery(opal\query\IDeleteQuery $query) {}

    public function fetchRemoteJoinData(opal\query\IJoinQuery $join, array $rows) {
        return $this->_fetchData($join);
    }

    public function fetchAttachmentData(opal\query\IAttachQuery $attachment, array $rows) {
        $manipulator = new ArrayManipulator($attachment->getSource(), $this->_fetchData($attachment), true);
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

        return $data;
    }

    public function getTransactionId() {
        return $this->getQuerySourceAdapterHash();
    }

    public function getJobAdapterId() {
        return $this->getQuerySourceId();
    }

    public function begin() {}
    public function commit() {}
    public function rollback() {}


    public function getAccessLockDomain() {
        return 'nativeData';
    }

    public function lookupAccessKey(array $keys, $action=null) {
        return null;
    }

    public function getDefaultAccess($action=null) {
        return user\IState::ALL;
    }

    public function getAccessLockId() {
        return $this->_dataSourceId;
    }
}
