<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\ldap;

use df;
use df\core;
use df\opal;
use df\user;

abstract class Adapter implements IAdapter {
    
    use opal\query\TQuery_ImplicitSourceEntryPoint;
    use user\TAccessLock;

    const BIND_REQUIRES_DN = true;
    const UID_ATTRIBUTE = 'uid';
    const ENTRY_DN_ATTRIBUTE = 'entryDN';
    const GLOBAL_ID_ATTRIBUTE = 'entryUUID';

    protected static $_arrayAttrs = [
        'objectClass', 'memberOf', 'dSCorePropagationData', 'namingContexts',
        'supportedControl', 'supportedLDAPVersion', 'supportedLDAPPolicies',
        'supportedSASLMechanisms', 'supportedCapabilities'
    ];
    
    protected static $_dateAttrs = [
        'whenCreated', 'whenChanged', 'badPasswordTime', 'lastLogoff', 'lastLogon',
        'pwdLastSet', 'accountExpires', 'lastLogonTimestamp', 'currentTime'
    ];
    
    protected static $_booleanAttrs = [
        'isSynchronized', 'isGlobalCatalogReady'
    ];
    
    protected static $_binaryAttrs = [
        'objectGUID', 'objectSid'
    ];

    protected static $_metaFields = [
        'structuralObjectClass', 'entryUUID', 'creatorsName', 'createTimestamp',
        'entryCSN', 'modifiersName', 'modifyTimestamp', 'entryDN', 
        'subschemaSubentry', 'hasSubordinates'
    ];
    
    protected $_connection;
    protected $_context;
    protected $_privilegedIdentity;
    protected $_boundIdentity;

    protected $_querySourceId;

    public static function factory($connection, $context, IIdentity $privilegedIdentity=null) {
        $connection = Connection::factory($connection);
        $context = Context::factory($context);

        $type = $connection->getType();

        if(!$type) {
            $type = 'Generic';
        }

        $class = 'df\\opal\\ldap\\adapter\\'.$type;

        if(!class_exists($class)) {
            throw new RuntimeException(
                'No adapter available for '.$type.' connection type'
            );
        }

        return new $class($connection, $context, $privilegedIdentity);
    }

    public static function getArrayAttributes() {
        return static::$_arrayAttrs;
    }
    
    public static function getDateAttributes() {
        return static::$_dateAttrs;
    }

    public static function getBooleanAttributes() {
        return static::$_booleanAttrs;
    }
    
    public static function getBinaryAttributes() {
        return static::$_binaryAttrs;
    }

    protected function __construct(IConnection $connection, IContext $context, IIdentity $privilegedIdentity=null) {
        $this->_connection = $connection;
        $this->setContext($context);
        $this->setPrivilegedIdentity($privilegedIdentity);
    }

// Connection
    public function getConnection() {
        return $this->_connection;
    }
    
    public function setContext($context) {
        $this->_context = Context::factory($context);
        return $this;
    }
    
    public function getContext() {
        return $this->_context;
    }
    

// Identity
    public function setPrivilegedIdentity(IIdentity $identity=null) {
        $this->_privilegedIdentity = $identity;
        return $this;
    }
    
    public function getPrivilegedIdentity() {
        return $this->_privilegedIdentity;
    }
    
    public function isBound() {
        return $this->_boundIdentity !== null;
    }
    
    public function getBoundIdentity() {
        return $this->_boundIdentity;
    }
    
    public function bind(IIdentity $identity) {
        $this->_connection->bindIdentity($identity, $this->_context);
        $this->_boundIdentity = $identity;
        return $this;
    }
    
    public function ensureBind() {
        if(!$this->isBound()) {
            if($this->_privilegedIdentity) {
                $this->bind($this->_privilegedIdentity);
            } else {
                throw new BindException(
                    'No bind identity has been given'
                );
            }
        }

        return $this;
    }


// Query source
    public function getQuerySourceId() {
        if(!$this->_querySourceId) {
            $connectionString = $this->_connection->getConnectionString();
            $parts = explode('://', $connectionString);

            $this->_querySourceId = 'opal://'.array_shift($parts).'/'.array_shift($parts);
        }

        return $this->_querySourceId;
    }

    public function getQuerySourceAdapterHash() {
        return $this->_connection->getHash();
    }

    public function getQuerySourceDisplayName() {
        return $this->getQuerySourceId();
    }

    public function getDelegateQueryAdapter() {
        return $this;
    }

    public function getClusterId() {
        return null;
    }

    public function supportsQueryType($type) {
        switch($type) {
            case opal\query\IQueryTypes::SELECT:
            case opal\query\IQueryTypes::FETCH:

            case opal\query\IQueryTypes::INSERT:
            case opal\query\IQueryTypes::UPDATE:
            case opal\query\IQueryTypes::DELETE:

            /*
            case opal\query\IQueryTypes::BATCH_INSERT:
            case opal\query\IQueryTypes::REPLACE:
            case opal\query\IQueryTypes::BATCH_REPLACE:
                
            case opal\query\IQueryTypes::CORRELATION:

            case opal\query\IQueryTypes::JOIN:
            case opal\query\IQueryTypes::JOIN_CONSTRAINT:
            case opal\query\IQueryTypes::REMOTE_JOIN:
                
            case opal\query\IQueryTypes::SELECT_ATTACH:
            case opal\query\IQueryTypes::FETCH_ATTACH:
            */
                return true;
                
            default:
                return false;
        }
    }

    public function supportsQueryFeature($feature) {
        switch($feature) {
            //case opal\query\IQueryFeatures::AGGREGATE:
            case opal\query\IQueryFeatures::WHERE_CLAUSE:
            //case opal\query\IQueryFeatures::GROUP_DIRECTIVE:
            //case opal\query\IQueryFeatures::HAVING_CLAUSE:
            case opal\query\IQueryFeatures::ORDER_DIRECTIVE:
            case opal\query\IQueryFeatures::LIMIT:
            case opal\query\IQueryFeatures::OFFSET:
            //case opal\query\IQueryFeatures::TRANSACTION:
            case opal\query\IQueryFeatures::LOCATION:
                return true;
                
            default:
                return false;
        }
    }

    public function handleQueryException(opal\query\IQuery $query, \Exception $e) {
        return false;
    }

    public function ensureStorageConsistency() {}


    public function fetchRootDse() {
        $data = $this->select('*', '+')
            ->inside('')
            ->toRow();

        unset($data['*'], $data['+']);

        return opal\ldap\rootDse\Base::factory($this, $data);
    }


// Queries
    public function executeSelectQuery(opal\query\ISelectQuery $query) {
        $result = $this->_executeReadQueryRequest($query);
        return $this->_prepareReadQueryResponse($query, $result);
    }
    
    public function countSelectQuery(opal\query\ISelectQuery $query) {
        $result = $this->_executeReadQueryRequest($query);
        return ldap_count_entries($this->_connection->getResource(), $result);
    }
    
    public function executeUnionQuery(opal\query\IUnionQuery $query) {
        throw new QueryException(
            'LDAP does not support union queries'
        );
    }

    public function countUnionQuery(opal\query\IUnionQuery $query) {
        throw new QueryException(
            'LDAP does not support union queries'
        );
    }

    public function executeFetchQuery(opal\query\IFetchQuery $query) {
        $result = $this->_executeReadQueryRequest($query);
        return $this->_prepareReadQueryResponse($query, $result);
    }
    
    public function countFetchQuery(opal\query\IFetchQuery $query) {
        $result = $this->_executeReadQueryRequest($query);
        return ldap_count_entries($this->_connection->getResource(), $result);
    }
    
    

// Insert query
    public function executeInsertQuery(opal\query\IInsertQuery $query) {
        $this->ensureBind();
        $row = $query->getRow();
        $connection = $this->_connection->getResource();
        $location = $query->getLocation();

        if($location === null) {
            throw new QueryException(
                'Base DN has not been set for insert query'
            );
        }

        $baseDn = $this->_prepareDn(Dn::factory($location));

        try {
            ldap_add($connection, $baseDn, $row);
        } catch(\Exception $e) {
            throw new QueryException(
                $e->getMessage(), $e->getCode()
            );
        }

        return true;
    }
    
// Batch insert query
    public function executeBatchInsertQuery(opal\query\IBatchInsertQuery $query) {
        core\stub($query);
    }
    
// Replace query
    public function executeReplaceQuery(opal\query\IReplaceQuery $query) {
        core\stub($query);
    }
    
// Batch replace query
    public function executeBatchReplaceQuery(opal\query\IBatchReplaceQuery $query) {
        core\stub($query);
    }
    
// Update query
    public function executeUpdateQuery(opal\query\IUpdateQuery $query) {
        $this->ensureBind();
        $row = $query->getValueMap();
        $connection = $this->_connection->getResource();
        $location = $query->getLocation();

        if($location === null) {
            $dnList = $this->_fetchDnsForWriteQuery($query);
        } else {
            $dnList = [$this->_prepareDn(Dn::factory($location))];
        }

        $count = 0;

        foreach($dnList as $dn) {
            ldap_modify($connection, $dn, $row);
            $count++;
        }

        return $count;
    }
    
// Delete query
    public function executeDeleteQuery(opal\query\IDeleteQuery $query) {
        $this->ensureBind();
        $connection = $this->_connection->getResource();
        $location = $query->getLocation();

        if($location === null) {
            $dnList = $this->_fetchDnsForWriteQuery($query);
        } else {
            $dnList = [$this->_prepareDn(Dn::factory($location))];
        }

        $count = 0;

        foreach($dnList as $dn) {
            ldap_delete($connection, $dn);
        }
    }
    
// Remote data
    public function fetchRemoteJoinData(opal\query\IJoinQuery $join, array $rows) {
        core\stub($query);
    }
    
    public function fetchAttachmentData(opal\query\IAttachQuery $attachment, array $rows) {
        core\stub($query);
    }



// Query processors
    protected function _executeReadQueryRequest(opal\query\IReadQuery $query) {
        $this->ensureBind();
        $filter = $this->_buildFilter($query);

        $baseDn = $query->getLocation();
        $subNodeSearch = $query->shouldSearchChildLocations();
        $readEntry = $query->getLimit() == 1;

        if($baseDn === null) {
            $baseDn = $this->_context->getBaseDn();
        }

        $baseDn = $this->_prepareDn(Dn::factory($baseDn));
        $attributes = [];

        foreach($query->getSource()->getOutputFields() as $field) {
            $name = $field->getName();

            if(in_array(strtolower($name), ['uid', 'samaccountname'])) {
                $name = static::UID_ATTRIBUTE;
            }

            $attributes[] = $name;
        }

        if($query instanceof opal\query\IFetchQuery) {
            $attributes[] = '+';
        }

        $connection = $this->_connection->getResource();

        if($subNodeSearch) {
            $result = @ldap_search($connection, $baseDn, $filter, $attributes);
        } else if($readEntry) {
            $result = @ldap_read($connection, $baseDn, $filter, $attributes);  
        } else {
            $result = @ldap_list($connection, $baseDn, $filter, $attributes);
        }

        if(!$result) {
            throw new QueryException(
                'Search failed: '.@ldap_error($connection)
            );
        }
        
        return $result;
    }

    protected function _prepareReadQueryResponse(opal\query\IReadQuery $query, $result) {
        if(!$result) {
            return [];
        }

        $reverse = false;
        $connection = $this->_connection->getResource();
        $total = ldap_count_entries($connection, $result);

        $orderDirectives = $query->getOrderDirectives();

        if(!empty($orderDirectives)) {
            for($i = count($orderDirectives) - 1; $i >= 0; $i--) {
                $directive = $orderDirectives[$i];
                $field = $directive->getField()->getName();
                @ldap_sort($connection, $result, $field);

                if($i == 0 && $directive->isDescending()) {
                    $reverse = true;
                }
            }
        }
        
        if($query->hasLimit()) {
            $limit = $query->getLimit();
        } else {
            $limit = $total;
        }

        $start = (int)$query->getOffset();
        $end = $start + $limit - 1;

        $fields = [];

        foreach($query->getSource()->getOutputFields() as $field) {
            $name = $field->getName();

            if($name == '*' || $name == '+') {
                continue;
            }

            $fields[] = $name;
        }

        $isFetch = $query instanceof opal\query\IFetchQuery;
        $metaFields = static::$_metaFields;
        $output = [];

        for(
            $current = 0, $rowEntry = ldap_first_entry($connection, $result);
            $current <= $end && is_resource($rowEntry);
            $current++, $rowEntry = ldap_next_entry($connection, $rowEntry)
        ) {
            if($current < $start) {
                continue;
            }

            $row = [];
            $meta = [];

            foreach(ldap_get_attributes($connection, $rowEntry) as $key => $value) {
                if(!is_array($value)) {
                    continue;
                }

                unset($value['count']);

                if(in_array(strtolower($key), ['uid', 'samaccountname'])) {
                    $key = static::UID_ATTRIBUTE;
                }

                if(!in_array($key, static::$_arrayAttrs) && count($value) == 1) {
                    $value = array_shift($value);
                }

                if(in_array($key, static::$_dateAttrs)) {
                    try {
                        $value = $this->_inflateDate($key, $value);
                    } catch(\Exception $e) {
                        $value = null;
                    }
                } else if(in_array($key, static::$_binaryAttrs)) {
                    $value = $this->_inflateBinaryAttribute($key, $value); 
                } else if(in_array($key, static::$_booleanAttrs)) {
                    $value = $this->_inflateBooleanAttribute($key, $value);
                }

                if($isFetch && in_array($key, $metaFields)) {
                    $meta[$key] = $value;
                } else {
                    $row[$key] = $value;
                }
            }

            foreach($fields as $field) {
                if(!isset($row[$field])) {
                    $row[$field] = null;
                }
            }

            if($isFetch) {
                $row[':meta'] = $meta;
            }

            $output[] = $row;
        }

        if($reverse) {
            $output = array_reverse($output);
        }

        return $output;
    }

    protected function _buildFilter(opal\query\IQuery $query) {
        $clauses = $query->getWhereClauseList();

        if($clauses->isEmpty()) {
            return '(objectClass=*)';
        }

        return $this->_clauseListToString($clauses->toArray());
    }

    protected function _clauseListToString(array $clauses) {
        $set = [];
        $stack = [];

        foreach($clauses as $clause) {
            if($clause instanceof opal\query\IClause) {
                $clauseString = $this->_clauseToString($clause);
            } else if($clause instanceof opal\query\IClauseList) {
                $clauseString = $this->_clauseListToString($clause->toArray());
            }

            if(!strlen($clauseString)) {
                continue;
            }

            if($clause->isOr()) {
                if(!empty($set)) {
                    $stack[] = $set;
                }

                $set = [$clauseString];
            } else {
                $set[] = $clauseString;
            }
        }

        if(!empty($set)) {
            $stack[] = $set;
        }

        foreach($stack as $i => $set) {
            if(count($set) == 1) {
                $set = array_shift($set);
            } else {
                $set = '(&'.implode('', $set).')';
            }

            $stack[$i] = $set;
        }

        $count = count($stack);

        if(!$count) {
            return null;
        } else if($count == 1) {
            return array_shift($stack);
        } else {
            return '(|'.implode('', $stack).')';
        }
    }

    protected function _clauseToString(opal\query\IClause $clause) {
        $output = null;
        $field = $clause->getField()->getName();
        $value = $clause->getPreparedValue();
        $operator = $clause->getOperator();
        $negate = false;

        if(strtolower($field) == 'uid' || strtolower($field) == 'samaccountname') {
            $field = static::UID_ATTRIBUTE;
        }

        switch($operator) {
            // = | !=
            case opal\query\clause\Clause::OP_NEQ:
                $negate = true;
            case opal\query\clause\Clause::OP_EQ:
                $output = '('.$field.'='.$this->_normalizeScalarClauseValue($field, $value).')';
                break;
                
            // > | >= | < | <=
            case opal\query\clause\Clause::OP_GT:
            case opal\query\clause\Clause::OP_GTE:
            case opal\query\clause\Clause::OP_LT: 
            case opal\query\clause\Clause::OP_LTE:
                $output = '('.$field.$operator.$this->_normalizeScalarClauseValue($field, $value).')';
                break;
                
            // <NOT> IN()
            case opal\query\clause\Clause::OP_NOT_IN:
                $negate = true;
            case opal\query\clause\Clause::OP_IN:
                $tempList = [];

                foreach($value as $innerVal) {
                    $tempList[] = new opal\query\clause\Clause(
                        $clause->getField(),
                        '=',
                        $innerVal,
                        true
                    );
                }

                $output = $this->_clauseListToString($tempList);
                break;
                
            // <NOT> BETWEEN()
            case opal\query\clause\Clause::OP_NOT_BETWEEN:
                $negate = true;
            case opal\query\clause\Clause::OP_BETWEEN:
                $tempList = [
                    new opal\query\clause\Clause(
                        $clause->getField(),
                        '<=',
                        array_shift($value)
                    ),

                    new opal\query\clause\Clause(
                        $clause->getField(),
                        '>=',
                        array_shift($value)
                    )
                ];

                $output = $this->_clauseListToString($tempList);
                break;
                
            // <NOT> LIKE
            case opal\query\clause\Clause::OP_NOT_LIKE:
                $negate = true;
            case opal\query\clause\Clause::OP_LIKE:
                $output = '('.$field.'~='.$this->_normalizeScalarClauseValue($field, $value).')';
                break;
                
            // <NOT> CONTAINS
            case opal\query\clause\Clause::OP_NOT_CONTAINS:
            case opal\query\clause\Clause::OP_NOT_MATCHES:
                $negate = true;
            case opal\query\clause\Clause::OP_CONTAINS:
            case opal\query\clause\Clause::OP_MATCHES:
                $output = '('.$field.'=*'.$this->_normalizeScalarClauseValue($field, $value).'*)';
                break;
            
            // <NOT> BEGINS
            case opal\query\clause\Clause::OP_NOT_BEGINS:
                $negate = true;
            case opal\query\clause\Clause::OP_BEGINS:
                $output = '('.$field.'='.$this->_normalizeScalarClauseValue($field, $value).'*)';
                break;
                
            // <NOT> ENDS
            case opal\query\clause\Clause::OP_NOT_ENDS:
                $negate = true;
            case opal\query\clause\Clause::OP_ENDS:
                $output = '('.$field.'=*'.$this->_normalizeScalarClauseValue($field, $value).')';
                break;
            
            default:
                throw new opal\query\OperatorException(
                    'Operator '.$operator.' is not recognized'
                );
        }

        if($output === null) {
            return null;
        }

        if($negate) {
            $output = '(!'.$output.')';
        }

        return $output;
    }

    protected function _normalizeScalarClauseValue($field, $value) {
        if(in_array($field, self::$_dateAttrs)) {
            $value = $this->_deflateDate($field, $value);
        } else if(in_array($field, self::$_binaryAttrs)) {
            $value = $this->_deflateBinaryAttribute($field, $value);
        } else if(in_array($field, self::$_booleanAttrs)) {
            $value = $this->_deflateBooleanAttribute($field, $value);
        }

        return $this->_escapeValue($value);
    }

    protected function _fetchDnsForWriteQuery(opal\query\IWriteQuery $query) {
        $whereList = $query->getWhereClauseList();

        if($whereList->isEmpty()) {
            throw new QueryException(
                'Cannot lookup record DNs, no clauses have been passed'
            );
        }

        $output = $this->select('*', '+')
            ->inside(null, true)
            ->addWhereClause($whereList)
            ->toList(static::ENTRY_DN_ATTRIBUTE);

        return $output;
    }


// IO
    protected function _inflateDate($name, $date) {
        if($date === null || $date == 0) {
            return null;
        }
        
        return core\time\Date::factory($date);
    }
    
    protected function _deflateDate($name, $date) {
        $date = core\time\Date::factory($date);
        return $date->toTimestamp();
    }
    
    protected function _inflateBinaryAttribute($name, $value) {
        if(!strlen($value)) {
            return null;
        }
        
        return bin2hex($value);
    }
    
    protected function _deflateBinaryAttribute($name, $value) {
        if(!strlen($value)) {
            return null;
        }
        
        return pack("H*", $value);
    }

    protected function _inflateBooleanAttribute($name, $value) {
        return core\string\Manipulator::stringToBoolean($value);
    }

    protected function _deflateBooleanAttribute($name, $value) {
        return (bool)$value ? 'true' : 'false';
    }



// Transaction
    public function beginQueryTransaction() {
        return $this;
    }
    
    public function commitQueryTransaction() {
        return $this;
    }
    
    public function rollbackQueryTransaction() {
        return $this;
    }


// Record
    public function newRecord(array $values=null) {
        return new Record($this, $values);
    }

    public function newPartial(array $values=null) {
        return new opal\record\Partial($this, $values);
    }


// Access
    public function getAccessLockDomain() {
        return 'ldap';
    }

    public function lookupAccessKey(array $keys, $action=null) {
        core\stub($keys, $action);
    }

    public function getDefaultAccess($action=null) {
        return true;
    }

    public function getAccessLockId() {
        return $this->_querySourceId;
    }



// Helpers
    protected function _escapeValues(array $values) {
        foreach($values as $key => $value) {
            $values[$key] = $this->_escapeValue($value);
        }
        
        return $values;
    }
    
    protected function _escapeValue($value) {
        $value = str_replace(
            ['\\', '*', '(', ')'],
            ['\5c', '\2a', '\28', '\29'],
            $value
        );
        
        $value = core\string\Util::ascii32ToHex32($value);
        
        if($value === null) {
            $value = '\0';
        }
        
        return $value;
    }

    protected function _prepareDn(IDn $dn) {
        $baseDn = $this->_context->getBaseDn();

        if(!$dn->isChildOf($baseDn)) {
            foreach($baseDn->toArray() as $rdn) {
                $dn->push($rdn);
            }
        }        

        return $this->_flattenDn($dn);
    }

    protected function _flattenDn(IDn $dn) {
        return (string)$dn;
    }
}