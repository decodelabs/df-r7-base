<?php

namespace df\axis\unit\table;

use df\core;
use df\axis;

class Bridge extends Base implements axis\IVirtualUnit {

    const IS_SHARED = false;
    const DOMINANT_UNIT = null;
    const DOMINANT_FIELD = null;

    const BROADCAST_HOOK_EVENTS = false;

    private $_dominantUnitName;
    private $_dominantFieldName;
    private $_isVirtual = false;

    public static function getBridgeClass($modelName, $id) {
        $class = 'df\\apex\\models\\'.$modelName.'\\'.$id.'\\Unit';

        if(!class_exists($class)) {
            throw new axis\RuntimeException(
                'Could not find bridge unit '.$id
            );
        }

        if(!is_subclass_of($class, 'df\\axis\\unit\\table\\Bridge')) {
            throw new axis\RuntimeException(
                'Unit '.$id.' is not a Bridge'
            );
        }

        return $class;
    }

    public static function loadVirtual(axis\IModel $model, array $args) {
        $fieldId = array_shift($args);
        $parts = explode('.', $fieldId);
        $unitName = array_shift($parts);
        $fieldName = array_shift($parts);
        $class = __CLASS__;

        if($unitId = array_shift($args)) {
            $parts = explode('/', $unitId, 2);
            $modelName = array_shift($parts);
            $id = array_shift($parts);

            if(!$id) {
                $id = $modelName;
                $modelName = $model->getModelName();
            }

            $class = self::getBridgeClass($modelName, $id);
        }

        $output = new $class($model);
        $output->_dominantUnitName = $unitName;
        $output->_dominantFieldName = $fieldName;
        $output->_isVirtual = true;

        return $output;
    }

    public function __construct(axis\IModel $model, $unitName=null) {
        if(!static::IS_SHARED && get_class($this) !== __CLASS__) {
            if(!static::DOMINANT_UNIT || !static::DOMINANT_FIELD) {
                throw new axis\schema\LogicException(
                    'Dominant field info has not been defined in class '.get_class($this)
                );
            }

            $this->_dominantUnitName = static::DOMINANT_UNIT;
            $this->_dominantFieldName = static::DOMINANT_FIELD;
        }

        parent::__construct($model, $unitName);
    }

    public function getUnitName() {
        if($this->_isVirtual) {
            $class = get_class($this);
            $args = [$this->_dominantUnitName.'.'.$this->_dominantFieldName];

            if($class != __CLASS__) {
                $parts = explode('\\', $class);
                array_pop($parts);
                $unitId = array_pop($parts);
                $modelName = array_pop($parts);
                $args[] = $modelName.'/'.$unitId;
            }

            return 'table.Bridge('.implode(',', $args).')';
        } else {
            return parent::getUnitName();
        }
    }

    public function getDirectUnitName() {
        if($this->_isVirtual) {
            $class = get_class($this);

            if($class != __CLASS__) {
                $parts = explode('\\', $class);
                array_pop($parts);
                $unitId = array_pop($parts);
                return $unitId;
            }
        } else {
            return parent::getUnitName();
        }
    }

    public function getCanonicalUnitName() {
        if($this->_isVirtual) {
            return $this->_dominantUnitName.'_'.$this->_dominantFieldName;
        } else {
            return parent::getCanonicalUnitName();
        }
    }


    public function buildInitialSchema() {
        if(!$this->_dominantUnitName) {
            throw new axis\schema\LogicException(
                'Bridge "'.$this->getUnitName().'" does not have a dominant unit defined - are you sure Bridge is the unit type you want to use?'
            );
        }

        $dominantUnit = $this->_model->getUnit($this->_dominantUnitName);
        $dominantSchema = $dominantUnit->getTransientUnitSchema();
        $dominantField = $dominantSchema->getField($this->_dominantFieldName);

        if(!$dominantField) {
            throw new axis\schema\FieldTypeNotFoundException(
                'Target Many relation field '.$this->_dominantFieldName.' could not be found on unit '.$dominantUnit->getUnitId()
            );
        }

        $submissiveUnit = axis\Model::loadUnitFromId($dominantField->getTargetUnitId());
        $submissiveSchema = $submissiveUnit->getTransientUnitSchema();

        $schema = new axis\schema\Base($this, $this->getUnitName());

        $bridgePrimaryFields = [
            $dominantName = $dominantSchema->getName(),
            $submissiveName = $dominantField->getBridgeTargetFieldName()
        ];

        $schema->addField($dominantName, 'KeyGroup', $dominantUnit->getUnitId());
        $schema->addField($submissiveName, 'KeyGroup', $submissiveUnit->getUnitId());

        $schema->addPrimaryIndex('primary', $bridgePrimaryFields);

        return $schema;
    }

    public function getDominantUnit() {
        return $this->_model->getUnit($this->_dominantUnitName);
    }

    public function getDominantUnitName() {
        return $this->_dominantUnitName;
    }

    public function getDominantFieldName() {
        return $this->_dominantFieldName;
    }

    public function getSubmissiveUnitId() {
        return $this->getSubmissiveUnit()->getUnitId();
    }

    public function getSubmissiveUnit() {
        return axis\Model::loadUnitFromId($this->getDominantUnit()->getUnitSchema()->getField($this->_dominantFieldName)->getTargetUnitId());
    }

    public function isVirtualUnitShared() {
        return static::IS_SHARED;
    }

    protected function createSchema($schema) {}

    public function newPartial(array $values=null) {
        return parent::newPartial($values)->isBridge(true);
    }

    public function getBridgeFieldNames($aliasPrefix=null, array $filter=[]) {
        $output = [];

        foreach($this->getUnitSchema()->getFields() as $name => $field) {
            if(in_array($name, $filter)) {
                continue;
            }

            if($aliasPrefix !== null) {
                $name .= ' as '.$aliasPrefix.'.'.$name;
            }

            $output[] = $name;
        }

        return $output;
    }
}
