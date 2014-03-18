<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class Union implements IUnionQuery {
    
    use TQuery;
    use TQuery_Attachable;
    use TQuery_Combinable;
    use TQuery_Orderable;
    use TQuery_Limitable;
    use TQuery_Offsettable;
    use TQuery_Pageable;
    use TQuery_Read;
    use TQuery_SelectSourceDataFetcher;

    protected $_sourceManager;
    protected $_primaryQuery;
    protected $_queries = [];

    public function __construct(ISourceManager $sourceManager) {
        $this->_sourceManager = $sourceManager;
    }

    public function getQueryType() {
        return IQueryType::UNION;
    }

    public function getSourceManager() {
        return $this->_sourceManager;
    }

    public function getSource() {
        if(empty($this->_queries)) {
            throw new LogicException(
                'Union has no child queries yet!'
            );
        }

        return $this->_queries[0]->getSource();
    }

    public function getSourceAlias() {
        return $this->getSource()->getSourceAlias();
    }



    public function with($field1=null) {
        return Initiator::factory($this->_sourceManager->getApplication())
            ->beginUnionSelect($this, func_get_args(), true);
    }

    public function withAll($field1=null) {
        return Initiator::factory($this->_sourceManager->getApplication())
            ->beginUnionSelect($this, func_get_args(), false);
    }

    public function addQuery(IUnionSelectQuery $query) {
        if(!in_array($query, $this->_queries, true)) {
            if(empty($this->_queries)) {
                $this->_primaryQuery = $query;
            } else {
                $primarySource = $this->_primaryQuery->getSource();
                $newSource = $query->getSource();
                $newFields = array_values($newSource->getOutputFields());
                $i = 0;

                foreach($primarySource->getOutputFields() as $name => $field) {
                    if($field instanceof IExpressionField && $field->isNull() && isset($newFields[$i])) {
                        $newField = $newFields[$i];

                        if(!$newField instanceof IExpressionField || !$field->isNull()) {
                            $field->setAlias($newField->getAlias())
                                ->setAltSourceAlias($newField->getSourceAlias());
                        }
                    }

                    $i++;
                }
            }

            $this->_queries[] = $query;
        }

        return $this;
    }

    public function getQueries() {
        return $this->_queries;
    }

    public function count() {
        return $this->_sourceManager->executeQuery($this, function($adapter) {
            return (int)$adapter->countUnionQuery($this);
        });
    }
}