<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class Initiator implements IInitiator {
    
    use core\TApplicationAware;
    use TQuery_TransactionAware;
    
    protected $_mode = null;
    protected $_fieldMap = array();
    protected $_data = null;
    protected $_joinType = null;
    protected $_parentQuery = null;
    protected $_distinct = false;
    protected $_applicator;
    
    public static function modeIdToName($id) {
        switch($id) {
            case IQueryTypes::SELECT: return 'SELECT';
            case IQueryTypes::FETCH: return 'FETCH';
            case IQueryTypes::INSERT: return 'INSERT';
            case IQueryTypes::BATCH_INSERT: return 'BATCH_INSERT';
            case IQueryTypes::REPLACE: return 'REPLACE';
            case IQueryTypes::BATCH_REPLACE: return 'BATCH_REPLACE';
            case IQueryTypes::UPDATE: return 'UPDATE';
            case IQueryTypes::DELETE: return 'DELETE';

            case IQueryTypes::CORRELATION: return 'CORRELATION';
            case IQueryTypes::POPULATE: return 'POPULATE';
            
            case IQueryTypes::JOIN: return 'JOIN';
            case IQueryTypes::JOIN_CONSTRAINT: return 'JOIN_CONSTRAINT';
            case IQueryTypes::REMOTE_JOIN: return 'REMOTE_JOIN';
            
            case IQueryTypes::SELECT_ATTACH: return 'SELECT_ATTACH';
            case IQueryTypes::FETCH_ATTACH: return 'FETCH_ATTACH';
            case IQueryTypes::REMOTE_ATTACH: return 'REMOVE_ATTACH';

            default: return '*uninitialized*';
        }
    }
    
    public static function factory(core\IApplication $application=null) {
        if($application === null) {
            $application = df\Launchpad::$application;
        }
        
        return new self($application);
    }
    
    public function __construct(core\IApplication $application) {
        $this->_application = $application;
    }
    

    public function setApplicator(Callable $applicator=null) {
        $this->_applicator = $applicator;
        return $this;
    }

    public function getApplicator() {
        return $this->_applicator;
    }

    
// Select
    public function beginSelect(array $fields=array(), $distinct=false) {
        $this->_setMode(IQueryTypes::SELECT);
        $fields = core\collection\Util::flattenArray($fields);
        
        if(empty($fields)) {
            $fields = array('*');
        } else if(is_array($fields[0])) {
            $fields = $fields[0];
        }
        
        foreach($fields as $field) {
            $this->_fieldMap[$field] = null;
        }

        $this->_distinct = (bool)$distinct;
        
        return $this;
    }
    
    
// Fetch
    public function beginFetch() {
        $this->_setMode(IQueryTypes::FETCH);
        $this->_fieldMap = array('*' => null);
        
        return $this;
    }
    
    
// Insert
    public function beginInsert($row) {
        $this->_setMode(IQueryTypes::INSERT);
        $this->_fieldMap = array('*' => null);
        $this->_data = $row;
        
        return $this;
    }
    
    
// Batch insert
    public function beginBatchInsert($rows=array()) {
        $this->_setMode(IQueryTypes::BATCH_INSERT);
        $this->_fieldMap = array('*' => null);
        $this->_data = $rows;
        
        return $this;
    }
    
    
// Replace
    public function beginReplace($row) {
        $this->_setMode(IQueryTypes::REPLACE);
        $this->_fieldMap = array('*' => null);
        $this->_data = $row;
        
        return $this;
    }
    
// Batch replace
    public function beginBatchReplace($rows=array()) {
        $this->_setMode(IQueryTypes::BATCH_REPLACE);
        $this->_fieldMap = array('*' => null);
        $this->_data = $rows;
        
        return $this;
    }
    
// Update
    public function beginUpdate(array $valueMap=null) {
        $this->_setMode(IQueryTypes::UPDATE);
        $this->_data = $valueMap;
        
        if(is_array($valueMap)) {
            $this->_fieldMap = $valueMap;
        }
        
        return $this;
    }
    
// Delete
    public function beginDelete() {
        $this->_setMode(IQueryTypes::DELETE);
        $this->_fieldMap = array('*' => null);
        
        return $this;
    }
    



// Correlation
    public function beginCorrelation(ISourceProvider $parent, $field, $alias=null) {
        $this->_setMode(IQueryTypes::CORRELATION);
        $this->_parentQuery = $parent;
        $this->_fieldMap = [$field => $alias];

        return $this;
    }


// Populate
    public function beginPopulate(IQuery $parent, array $fields, $type=IPopulateQuery::TYPE_ALL, array $selectFields=null) {
        $this->_setMode(IQueryTypes::POPULATE);
        $this->_parentQuery = $parent;
        $fields = core\collection\Util::flattenArray($fields);
        $isAll = false;

        switch($type) {
            case IPopulateQuery::TYPE_ALL:
                $isAll = true;

            case IPopulateQuery::TYPE_SOME:
                $this->_joinType = $type;
                break;

            default:
                throw new InvalidArgumentException(
                    $type.' is not a valid populate type'
                );
        }

        if($this->_joinType == IPopulateQuery::TYPE_SOME
        && count($fields) != 1) {
            throw new InvalidArgumentException(
                'populateSome() can only handle one field at a time'
            );
        }

        foreach($fields as $field) {
            $children = array();

            if(false !== strpos($field, '.')) {
                $children = explode('.', $field);
                $field = array_shift($children);
            }

            if(!$populate = $parent->getPopulate($field)) {
                $populate = new Populate($parent, $field, $type, $selectFields);
            }

            if(!empty($children)) {
                foreach($children as $child) {
                    $populate->endPopulate();

                    $childPopulate = $populate->populateSome($child);
                    $populate = $childPopulate;
                }
            }
        }

        $populate->setNestedParent($parent);
        return $populate;
    }



// Combine
    public function beginCombine(ICombinableQuery $parent, array $fields) {
        $this->_setMode(IQueryTypes::COMBINE);
        $this->_parentQuery = $parent;
        $fields = core\collection\Util::flattenArray($fields);

        return new Combine($parent, $fields);
    }

    
    
// Join
    public function beginJoin(IQuery $parent, array $fields=array(), $type=IJoinQuery::INNER) {
        $this->_setMode(IQueryTypes::JOIN);
        $this->_parentQuery = $parent;
        $fields = core\collection\Util::flattenArray($fields);

        if(empty($fields)) {
            $fields = ['*'];
        }
        
        switch($type) {
            case IJoinQuery::INNER:
            case IJoinQuery::LEFT:
            case IJoinQuery::RIGHT:
                $this->_joinType = $type;
                break;
                
            default:
                throw new InvalidArgumentException(
                    $type.' is not a valid join type'
                );
        }
        
        if(isset($fields[0]) && is_array($fields[0])) {
            $fields = $fields[0];
        }
        
        foreach($fields as $field) {
            $this->_fieldMap[$field] = null;
        }
        
        return $this;
    }
    
    public function beginJoinConstraint(IQuery $parent, $type=IJoinQuery::INNER) {
        $this->_setMode(IQueryTypes::JOIN_CONSTRAINT);
        $this->_parentQuery = $parent;
        
        switch($type) {
            case IJoinQuery::INNER:
            case IJoinQuery::LEFT:
            case IJoinQuery::RIGHT:
                $this->_joinType = $type;
                break;
                
            default:
                throw new InvalidArgumentException(
                    $type.' is not a valid join type'
                );
        }
        
        return $this;
    }


// Attach
    public function beginAttach(IReadQuery $parent, array $fields=array(), $isSelect=false) {
        $this->_parentQuery = $parent;
        $fields = core\collection\Util::flattenArray($fields);
        
        if(isset($fields[0]) && is_array($fields[0])) {
            $fields = $fields[0];
        }
        
        if(!$isSelect) {
            $this->_setMode(IQueryTypes::FETCH_ATTACH);
            $this->_fieldMap = ['*' => null];
        } else {
            $this->_setMode(IQueryTypes::SELECT_ATTACH);

            if(empty($fields)) {
                $this->_fieldMap = ['*' => null];
            } else {
                foreach($fields as $field) {
                    $this->_fieldMap[$field] = null;
                }
            }
        }
        
        return $this;
    }

    public static function beginAttachFromPopulate(IPopulateQuery $populate) {
        return $populate->isSelect() ?
            SelectAttach::fromPopulate($populate) :
            FetchAttach::fromPopulate($populate);
    }
    
    
// Query data
    public function getFields() {
        return array_keys($this->_fieldMap);
    }
    
    public function getFieldMap() {
        return $this->_fieldMap;
    }
    
    public function getData() {
        return $this->_data;
    }
    
    public function getParentQuery() {
        return $this->_parentQuery;
    }
    
    public function getJoinType() {
        return $this->_joinType;
    }

    protected function _setMode($mode) {
        if($this->_mode !== null) {
            throw new LogicException(
                'Query initiator mode has already been set'
            );
        }

        $this->_mode = $mode;
    }
    
    
// Transmutation
    public function from($sourceAdapter, $alias=null) {
        switch($this->_mode) {
            case IQueryTypes::SELECT:
                $sourceManager = new opal\query\SourceManager($this->_application, $this->_transaction);
                $source = $sourceManager->newSource($sourceAdapter, $alias, $this->getFields());
                
                if(!$source->getAdapter()->supportsQueryType($this->_mode)) {
                    throw new LogicException(
                        'Query adapter '.$source->getAdapter()->getQuerySourceDisplayName().' '.
                        'does not support SELECT queries'
                    );
                }
                
                return (new Select($sourceManager, $source))->isDistinct((bool)$this->_distinct);
                
            case IQueryTypes::FETCH:
                $sourceManager = new opal\query\SourceManager($this->_application, $this->_transaction);
                $source = $sourceManager->newSource($sourceAdapter, $alias, array('*'));
                
                if(!$source->getAdapter()->supportsQueryType($this->_mode)) {
                    throw new LogicException(
                        'Query adapter '.$source->getAdapter()->getQuerySourceDisplayName().' '.
                        'does not support FETCH queries'
                    );
                }
                
                return new Fetch($sourceManager, $source);
                
            case IQueryTypes::DELETE:
                $sourceManager = new opal\query\SourceManager($this->_application, $this->_transaction);
                $source = $sourceManager->newSource($sourceAdapter, $alias, null, true);
                
                if(!$source->getAdapter()->supportsQueryType($this->_mode)) {
                    throw new LogicException(
                        'Query adapter '.$source->getAdapter()->getQuerySourceDisplayName().' '.
                        'does not support DELETE queries'
                    );
                }
                
                return new Delete($sourceManager, $source);    
                
            case IQueryTypes::CORRELATION:
                $sourceManager = $this->_parentQuery->getSourceManager();
                foreach($this->_fieldMap as $fieldName => $fieldAlias) { break; }

                if($fieldAlias !== null) {
                    $fieldName = explode(' as ', $fieldName);
                    $fieldName = array_shift($fieldName).' as '.$fieldAlias;
                }
                
                $source = $sourceManager->newSource($sourceAdapter, $alias, [$fieldName]);

                if(!$source->getAdapter()->supportsQueryType($this->_mode)) {
                    throw new LogicException(
                        'Query adapter '.$source->getAdapter()->getQuerySourceDisplayName().' '.
                        'does not support CORRELATION queries'
                    );
                }

                $output = new Correlation($this->_parentQuery, $source, $fieldAlias);

                if($this->_applicator) {
                    $output->setApplicator($this->_applicator);
                }

                return $output;

            case IQueryTypes::JOIN:
            case IQueryTypes::JOIN_CONSTRAINT:
                $sourceManager = $this->_parentQuery->getSourceManager();
                $fields = null;
                
                if($this->_mode === IQueryTypes::JOIN) {
                    $fields = $this->getFields();
                }
                
                $source = $sourceManager->newSource($sourceAdapter, $alias, $fields);
                
                if($source->getAdapterHash() == $this->_parentQuery->getSource()->getAdapterHash()) {
                    if(!$source->getAdapter()->supportsQueryType($this->_mode)) {
                        throw new LogicException(
                            'Query adapter '.$source->getAdapter()->getQuerySourceDisplayName().
                            ' does not support joins'
                        );
                    }
                } else {
                    if(!$source->getAdapter()->supportsQueryType(IQueryTypes::REMOTE_JOIN)) {
                        throw new LogicException(
                            'Query adapter '.$source->getAdapter()->getQuerySourceDisplayName().
                            ' does not support remote joins'
                        );
                    }
                }
                
                if($this->_mode === IQueryTypes::JOIN_CONSTRAINT) {
                    return new JoinConstraint($this->_parentQuery, $source, $this->_joinType);
                } else {
                    return new Join($this->_parentQuery, $source, $this->_joinType);
                }
                
            case IQueryTypes::SELECT_ATTACH:
            case IQueryTypes::FETCH_ATTACH:
                if($alias === null) {
                    throw new InvalidArgumentException(
                        'Attachment sources must be aliased'
                    );
                }
                
                $fields = $this->getFields();

                $sourceManager = new opal\query\SourceManager($this->_application, $this->_transaction);
                $sourceManager->setParentSourceManager($this->_parentQuery->getSourceManager());

                $source = $sourceManager->newSource($sourceAdapter, $alias, $fields);
                
                if($source->getAdapterHash() == $this->_parentQuery->getSource()->getAdapterHash()) {
                    if(!$source->getAdapter()->supportsQueryType($this->_mode)) {
                        throw new LogicException(
                            'Query adapter '.$source->getAdapter()->getQuerySourceDisplayName().
                            ' does not support attachments'
                        );
                    }
                } else {
                    if(!$source->getAdapter()->supportsQueryType(IQueryTypes::REMOTE_ATTACH)) {
                        throw new LogicException(
                            'Query adapter '.$source->getAdapter()->getQuerySourceDisplayName().
                            ' does not support remote attachments'
                        );
                    }
                }
                
                if($this->_mode == IQueryTypes::FETCH_ATTACH) {
                    return new FetchAttach($this->_parentQuery, $sourceManager, $source);
                } else {
                    return (new SelectAttach($this->_parentQuery, $sourceManager, $source))
                        ->isDistinct((bool)$this->_distinct);
                }
                
                
            case null;
                throw new LogicException(
                    'Query initiator mode has not been set'
                );
                
            default:
                throw new LogicException(
                    'Query initiator mode '.self::modeIdToName($this->_mode).' is not compatible with \'from\' syntax'
                );
        }
    }

    public function into($sourceAdapter, $alias=null) {
        $sourceManager = new opal\query\SourceManager($this->_application, $this->_transaction);
        
        switch($this->_mode) {
            case IQueryTypes::INSERT:
                $source = $sourceManager->newSource($sourceAdapter, $alias, null, true);
                
                if(!$source->getAdapter()->supportsQueryType($this->_mode)) {
                    throw new LogicException(
                        'Query adapter '.$source->getAdapter()->getQuerySourceDisplayName().' '.
                        'does not support INSERT'
                    );
                }
                
                return new Insert($sourceManager, $source, $this->_data); 
                
            case IQueryTypes::BATCH_INSERT:
                $source = $sourceManager->newSource($sourceAdapter, $alias, null, true);
                
                if(!$source->getAdapter()->supportsQueryType($this->_mode)) {
                    throw new LogicException(
                        'Query adapter '.$source->getAdapter()->getQuerySourceDisplayName().' '.
                        'does not support batch INSERT queries'
                    );
                }
                
                return new BatchInsert($sourceManager, $source, $this->_data);
                
            case null;
                throw new LogicException(
                    'Query initiator mode has not been set'
                );
                
            default:
                throw new LogicException(
                    'Query initiator mode '.self::modeIdToName($this->_mode).' is not compatible with \'into\' syntax'
                );
        }
    }

    public function in($sourceAdapter, $alias=null) {
        $sourceManager = new opal\query\SourceManager($this->_application, $this->_transaction);
        
        switch($this->_mode) {
            case IQueryTypes::REPLACE:
                $source = $sourceManager->newSource($sourceAdapter, $alias, null, true);
                
                if(!$source->getAdapter()->supportsQueryType($this->_mode)) {
                    throw new LogicException(
                        'Query adapter '.$source->getAdapter()->getQuerySourceDisplayName().' '.
                        'does not support REPLACE queries'
                    );
                }
                
                return new Replace($sourceManager, $source, $this->_data);
                
            case IQueryTypes::BATCH_REPLACE:
                $source = $sourceManager->newSource($sourceAdapter, $alias, null, true);
                
                if(!$source->getAdapter()->supportsQueryType($this->_mode)) {
                    throw new LogicException(
                        'Query adapter '.$source->getAdapter()->getQuerySourceDisplayName().' '.
                        'does not support batch REPLACE queries'
                    );
                }
                
                return new BatchReplace($sourceManager, $source, $this->_data);
                
            case IQueryTypes::UPDATE:
                $source = $sourceManager->newSource($sourceAdapter, $alias, null, true);
                
                if(!$source->getAdapter()->supportsQueryType($this->_mode)) {
                    throw new LogicException(
                        'Query adapter '.$source->getAdapter()->getQuerySourceDisplayName().' '.
                        'does not support UPDATE queries'
                    );
                }
                
                return new Update($sourceManager, $source, $this->_data);
            
            case null;
                throw new LogicException(
                    'Query initiator mode has not been set'
                );
                
            default:
                throw new LogicException(
                    'Query initiator mode '.self::modeIdToName($this->_mode).' is not compatible with \'in\' syntax'
                );
        }
    }
}
