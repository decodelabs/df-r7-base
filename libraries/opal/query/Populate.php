<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;
    
class Populate implements IPopulateQuery, core\IDumpable {

    use TQuery;
    use TQuery_LocalSource;
    use TQuery_Populate;
    use TQuery_Populatable;
    use TQuery_WhereClauseFactory;
    use TQuery_Orderable;
    use TQuery_Limitable;
    use TQuery_Offsettable;


    public static function typeIdToName($id) {
        switch($id) {
            case IPopulateQuery::TYPE_SOME:
                return 'SOME';

            default:
            case IPopulateQuery::TYPE_ALL:
                return 'ALL';
        }
    }

    public function __construct(IPopulatableQuery $parent, $fieldName, $type, array $selectFields=null) {
        $adapter = $parent->getSource()->getAdapter();

        if(!$adapter instanceof opal\query\IIntegralAdapter) {
            throw new LogicException(
                'Cannot populate field '.$fieldName.' - adapter is not integral'
            );
        }

        $this->_parent = $parent;
        $this->_type = $type;

        $parentSourceManager = $parent->getSourceManager();

        $this->_field = $parentSourceManager->extrapolateIntrinsicField($parent->getSource(), $fieldName);
        
        $this->_sourceManager = new SourceManager(
            $parentSourceManager->getApplication(), 
            $parentSourceManager->getTransaction()
        );

        $this->_sourceManager->setParentSourceManager($parentSourceManager);

        $schema = $adapter->getQueryAdapterSchema();
        $field = $schema->getField($fieldName);

        if(!$field instanceof opal\schema\IRelationField) {
            throw new RuntimeException(
                'Cannot populate '.$fieldName.' - field is not a relation'
            );
        }

        $adapter = $field->getTargetQueryAdapter($this->_sourceManager->getApplication());
        $alias = uniqid('ppl_'.$fieldName);

        if(empty($selectFields)) {
            $selectFields = ['*'];
        }

        $this->_source = $this->_sourceManager->newSource($adapter, $alias, $selectFields);
    }

    public function getParentQuery() {
        return $this->_parent;
    }


// Dump
    public function getDumpProperties() {
        $output = [
            'parent' => $this->_parent->getSource()->getId(),
            'field' => $this->_field,
            'type' => self::typeIdToName($this->_type)
        ];

        if(!empty($this->_populates)) {
            $output['populates'] = $this->_populates;
        }

        if($this->_whereClauseList && !$this->_whereClauseList->isEmpty()) {
            $output['where'] = $this->_whereClauseList;
        }

        if(!empty($this->_order)) {
            $order = array();
            
            foreach($this->_order as $directive) {
                $order[] = $directive->toString();
            }
            
            $output['order'] = implode(', ', $order);
        }
        
        if($this->_limit) {
            $output['limit'] = $this->_limit;
        }
        
        if($this->_offset) {
            $output['offset'] = $this->_offset;
        }

        return $output;
    }
}