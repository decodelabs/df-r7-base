<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class OutputManifest implements IOutputManifest {

    protected $_primarySource;
    protected $_sources = [];
    protected $_wildcards = [];
    protected $_aggregateFields = [];
    protected $_outputFields = [];
    protected $_privateFields = [];
    protected $_fieldProcessors = null;
    protected $_combines = [];
    protected $_searchController = null;
    protected $_queryRequiresPartial = false;

    public function __construct(ISource $source, array $rows=null, $isNormalized=true) {
        $this->importSource($source, $rows, $isNormalized);
    }

    public function getPrimarySource() {
        return $this->_primarySource;
    }

    public function getSources() {
        return $this->_sources;
    }

    public function importSource(ISource $source, array $rows=null, $isNormalized=true) {
        if($source->isDerived()) {
            $source = $source->getAdapter()->getDerivationSource();
        }

        if($source === $this->_primarySource) {
            return $this;
        }

        $sourceAlias = $source->getAlias();
        $this->_sources[$sourceAlias] = $source;

        if(!$this->_primarySource) {
            $this->_primarySource = $source;
        }

        foreach($source->getOutputFields() as $alias => $field) {
            $this->addOutputField($field);
        }

        foreach($source->getPrivateFields() as $alias => $field) {
            $this->_privateFields[$alias] = $field;
        }

        if(isset($this->_wildcards[$sourceAlias], $rows[0])) {
            $muteFields = $this->_wildcards[$sourceAlias];
            unset($this->_wildcards[$sourceAlias]);

            foreach($rows[0] as $key => $value) {
                $parts = explode('.', $key);
                $alias = $fieldName = array_pop($parts);
                $s = array_shift($parts);

                if($s == $sourceAlias
                && substr($fieldName, 0, 1) != '@'
                //&& !isset($this->_outputFields[$alias])
                ) {
                    if(array_key_exists($fieldName, $muteFields)) {
                        if($muteFields[$fieldName] === null) {
                            continue;
                        } else {
                            $alias = $muteFields[$fieldName];
                        }
                    }

                    $this->addOutputField(new opal\query\field\Intrinsic($source, $fieldName, $alias));
                }
            }
        }

        return $this;
    }


    public function addOutputField(IField $field) {
        if($field instanceof IWildcardField) {
            $this->_wildcards[$field->getSourceAlias()] = $field->getMuteFields();
            return $this;
        }

        $alias = $field->getAlias();

        if(isset($this->_outputFields[$alias])
        && $field !== isset($this->_outputFields[$alias])
        && !$this->_outputFields[$alias] instanceof ILateAttachField) {
            $field->setOverrideField($this->_outputFields[$alias]);
        }

        $this->_outputFields[$alias] = $field;

        if($field instanceof IAggregateField) {
            $this->_aggregateFields[$alias] = $field;
        }

        if($field instanceof ICorrelationField) {
            if($aggregateField = $field->getAggregateOutputField()) {
                $this->_aggregateFields[$aggregateField->getAlias()] = $aggregateField;
            }
        }

        if($field instanceof ICombineField) {
            $this->_combines[$field->getName()] = $field->getCombine();
        }

        if($field instanceof ISearchController) {
            $this->_searchController = $field;
        }

        $this->_fieldProcessors = null;

        return $this;
    }

    public function getOutputFields() {
        return $this->_outputFields;
    }

    public function getPrivateFields() {
        return $this->_privateFields;
    }

    public function getAllFields() {
        return array_merge($this->_outputFields, $this->_privateFields);
    }

    public function getWildcardMap() {
        return $this->_wildcards;
    }

    public function getAggregateFields() {
        return $this->_aggregateFields;
    }

    public function getAggregateFieldAliases() {
        return array_keys($this->_aggregateFields);
    }

    public function hasAggregateFields() {
        return !empty($this->_aggregateFields);
    }

    public function getOutputFieldProcessors() {
        if($this->_fieldProcessors === null) {
            $this->_fieldProcessors = [];

            foreach($this->_sources as $source) {
                $sourceAlias = $source->getAlias();
                $adapter = $source->getAdapter();
                $fieldNames = [];

                foreach($source->getOutputFields() as $name => $field) {
                    if(isset($this->_outputFields[$name])) {
                        $field = $this->_outputFields[$name];
                    }

                    if($field->shouldBeProcessed()) {
                        $fieldNames[] = $field->getName();
                    }
                }

                if(($keyField = $source->getKeyField()) && $keyField->shouldBeProcessed()) {
                    $fieldNames[] = $keyField->getName();
                }

                foreach(opal\schema\Introspector::getFieldProcessors($adapter, $fieldNames) as $name => $field) {
                    if(!$field instanceof IFieldValueProcessor) {
                        continue;
                    }

                    $this->_fieldProcessors[$sourceAlias.'.'.$name] = $field;
                }
            }
        }

        return $this->_fieldProcessors;
    }

    public function getCombines() {
        return $this->_combines;
    }

    public function getSearchController() {
        return $this->_searchController;
    }

    public function queryRequiresPartial(bool $flag=null) {
        if($flag !== null) {
            $this->_queryRequiresPartial = $flag;
            return $this;
        }

        return $this->_queryRequiresPartial;
    }

    public function requiresPartial($forFetch=true) {
        if($this->_queryRequiresPartial) {
            return true;
        }

        if(!$forFetch) {
            return false;
        }

        foreach($this->_outputFields as $field) {
            if(!$field->shouldBeProcessed()) {
                return true;
            }
        }

        return false;
    }
}
