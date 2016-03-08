<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class Select implements ISelectQuery, core\IDumpable {

    use TQuery;
    use TQuery_LocalSource;
    use TQuery_Derivable;
    use TQuery_Locational;
    use TQuery_Distinct;
    use TQuery_Correlatable;
    use TQuery_Joinable;
    use TQuery_Attachable;
    use TQuery_Populatable;
    use TQuery_Combinable;
    use TQuery_PrerequisiteClauseFactory;
    use TQuery_WhereClauseFactory;
    use TQuery_Searchable;
    use TQuery_Groupable;
    use TQuery_HavingClauseFactory;
    use TQuery_Orderable;
    use TQuery_Nestable;
    use TQuery_Limitable;
    use TQuery_Offsettable;
    use TQuery_Pageable;
    use TQuery_Read;
    use TQuery_SelectSourceDataFetcher;

    public function __construct(ISourceManager $sourceManager, ISource $source) {
        $this->_sourceManager = $sourceManager;
        $this->_source = $source;
    }

    public function getQueryType() {
        return IQueryTypes::SELECT;
    }


// Sources
    public function addOutputFields(string ...$fields) {
        foreach($fields as $field) {
            $this->_sourceManager->extrapolateOutputField($this->_source, $field);
        }

        return $this;
    }




// Output
    public function count() {
        return $this->_sourceManager->executeQuery($this, function($adapter) {
            return (int)$adapter->countSelectQuery($this);
        });
    }

    public function toList($valField1, $valField2=null) {
        if($valField2 !== null) {
            $keyField = $valField1;
            $valField = $valField2;
        } else {
            $keyField = null;
            $valField = $valField1;
        }

        $data = $this->_fetchSourceData($keyField, $valField);

        if($data instanceof core\IArrayProvider) {
            $data = $data->toArray();
        }

        if(!is_array($data)) {
            throw new UnexpectedValueException(
                'Source did not return a result that could be converted to an array'
            );
        }

        return $data;
    }

    public function toValue($valField=null) {
        if($valField !== null) {
            $valField = $this->_sourceManager->extrapolateDataField($this->_source, $valField);
            $data = $this->toRow();

            $key = $valField->getAlias();

            if(isset($data[$key])) {
                return $data[$key];
            } else {
                return null;
            }
        } else {
            if(null !== ($data = $this->toRow())) {
                return array_shift($data);
            }

            return null;
        }
    }



// Dump
    public function getDumpProperties() {
        $output = [
            'sources' => $this->_sourceManager,
            'fields' => $this->_source
        ];

        if(!empty($this->_populates)) {
            $output['populates'] = $this->_populates;
        }

        if(!empty($this->_combines)) {
            $output['combines'] = $this->_combines;
        }

        if(!empty($this->_joins)) {
            $output['join'] = $this->_joins;
        }

        if(!empty($this->_attachments)) {
            $output['attach'] = $this->_attachments;
        }

        if($this->hasWhereClauses()) {
            $output['where'] = $this->getWhereClauseList();
        }

        if($this->_searchController) {
            $output['search'] = $this->_searchController;
        }

        if(!empty($this->_group)) {
            $output['group'] = $this->_groups;
        }

        if($this->hasHavingClauses()) {
            $output['having'] = $this->_havingClauseList;
        }

        if(!empty($this->_order)) {
            $order = [];

            foreach($this->_order as $directive) {
                $order[] = $directive->toString();
            }

            $output['order'] = implode(', ', $order);
        }

        if(!empty($this->_nest)) {
            $output['nest'] = $this->_nest;
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


## ATTACH
class Select_Attach extends Select implements ISelectAttachQuery {

    use TQuery_Attachment;
    use TQuery_AttachmentListExtension;
    use TQuery_AttachmentValueExtension;
    use TQuery_ParentAwareJoinClauseFactory;

    public function __construct(IQuery $parent, ISourceManager $sourceManager, ISource $source) {
        $this->_parent = $parent;
        parent::__construct($sourceManager, $source);
    }

    public function getQueryType() {
        return IQueryTypes::SELECT_ATTACH;
    }

// Dump
    public function getDumpProperties() {
        return array_merge([
            'sources' => $this->_sourceManager,
            'type' => self::typeIdToName($this->_type),
            'fields' => $this->_source,
            'on' => $this->_joinClauseList,
        ], parent::getDumpProperties());
    }
}


## UNION
class Select_Union extends Select implements IUnionSelectQuery {

    protected $_union;
    protected $_isUnionDistinct = true;

    public function __construct(IUnionQuery $union, ISource $source) {
        $this->_union = $union;
        parent::__construct($union->getSourceManager(), $source);
    }

    public function isUnionDistinct(bool $flag=null) {
        if($flag !== null) {
            $this->_isUnionDistinct = $flag;
            return $this;
        }

        return $this->_isUnionDistinct;
    }

    public function endSelect() {
        $this->_union->addQuery($this);
        return $this->_union;
    }

    public function with(...$fields) {
        $this->endSelect();

        return Initiator::factory()
            ->beginUnionSelect($this->_union, $fields, true);
    }

    public function withAll(...$fields) {
        $this->endSelect();

        return Initiator::factory()
            ->beginUnionSelect($this->_union, $fields, false);
    }
}