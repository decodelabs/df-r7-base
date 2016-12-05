<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug;

use df;
use df\core;
use df\arch as archLib;
use df\axis;
use df\opal;
use df\flex;
use df\mesh;

class Data implements core\ISharedHelper, opal\query\IEntryPoint {

    use core\TSharedHelper;
    use opal\query\TQuery_EntryPoint;


// Validate
    public function newValidator() {
        return new core\validate\Handler();
    }

    public function fetchForAction($source, $primary, $action=null, $chain=null) {
        $output = $this->_queryForAction($this->fetch()->from($source), $primary, $action, $chain);
        $this->_checkRecordAccess($output, $action);

        return $output;
    }

    public function selectForAction($source, $fields, $primary=null, $action=null, $chain=null) {
        if(!is_array($fields)) {
            $chain = $action;
            $action = $primary;
            $primary = $fields;
            $fields = ['*'];
        }

        return $this->_queryForAction($this->select($fields)->from($source), $primary, $action, $chain);
    }

    public function fetchOrCreateForAction($source, $primary, $action=null, $newChain=null, $queryChain=null) {
        if(is_callable($action)) {
            $queryChain = $newChain;
            $newChain = $action;
            $action = null;
        }

        $output = $this->_queryForAction($this->fetch()->from($source), $primary, $action, $queryChain, false);

        if($output) {
            $this->_checkRecordAccess($output, $action);
        } else {
            $output = $this->newRecord($source);

            if($newChain) {
                core\lang\Callback($newChain, $output);
            }
        }

        return $output;
    }

    public function _queryForAction(opal\query\IReadQuery $query, &$primary, &$action, $chain=null, $throw=true) {
        $name = $query->getSource()->getDisplayName();

        if($primary === null) {
            if($throw) {
                $this->context->throwError(404, 'Item not found - '.$name.'#NULL');
            } else {
                return null;
            }
        }

        if(is_callable($action)) {
            $chain = $action;
            $action = null;
        }

        $this->applyQueryActionClause($query, $primary);


        if($chain) {
            $query->chain($chain);
        }

        if((!$output = $query->toRow()) && $throw) {
            if(is_array($primary)) {
                $primary = implode(',', $primary);
            }

            $this->context->throwError(404, 'Item not found - '.$name.'#'.$primary);
        }

        return $output;
    }

    protected function _checkRecordAccess($record, $action) {
        if(!$this->context->getUserManager()->canAccess($record, $action)) {
            $actionName = $action;

            if($actionName === null) {
                $actionName = 'access';
            }

            $this->context->throwError(401, 'Cannot '.$actionName.' '.$name.' items');
        }
    }

    public function applyQueryActionClause(opal\query\IQuery $query, $primary) {
        if(is_array($primary) && is_string(key($primary))) {
            foreach($primary as $key => $value) {
                $query->where($key, '=', $value);
            }
        } else {
            $query->where('@primary', '=', $primary);
        }

        return $this;
    }

    public function beginProcedure($unit, $name, $values, $item=null) {
        return $this->_normalizeUnit($unit)
            ->beginProcedure($name, $values, $item);
    }

    public function getRoutine($unit, $name, core\io\IMultiplexer $multiplexer=null) {
        return $this->_normalizeUnit($unit)
            ->getRoutine($name, $multiplexer);
    }

    public function executeRoutine($unit, $name, ...$args) {
        return $this->getRoutine($unit, $name)->execute(...$args);
    }

    protected function _normalizeUnit($unit) {
        if(!$unit instanceof axis\IUnit) {
            $unit = $this->fetchEntity($unit);

            if(!$unit instanceof axis\IUnit) {
                throw new axis\RuntimeException('Invalid unit passed for procedure');
            }
        }

        return $unit;
    }

    public function newRecord($source, array $values=null) {
        $adapter = $this->_sourceToAdapter($source);
        $output = $adapter->newRecord($values);

        if(!$this->context->getUserManager()->canAccess($output, 'add')) {
            $this->context->throwError(401, 'Cannot add '.$source->getDisplayName().' items');
        }

        return $output;
    }

    public function newPartial($source, array $values=null) {
        return $this->_sourceToAdapter($source)->newPartial($values);
    }

    private function _sourceToAdapter($source) {
        $sourceManager = new opal\query\SourceManager();
        $source = $sourceManager->newSource($source, null);
        return $source->getAdapter();
    }

    public function newJobQueue() {
        return new mesh\job\Queue();
    }

    public function checkAccess($source, $action=null) {
        $actionName = $action;

        if($actionName === null) {
            $actionName = 'access';
        }

        $sourceManager = new opal\query\SourceManager();
        $source = $sourceManager->newSource($source, null);
        $adapter = $source->getAdapter();

        if(!$this->context->getUserManager()->canAccess($adapter, $action)) {
            $this->context->throwError(401, 'Cannot '.$actionName.' '.$source->getDisplayName().' items');
        }

        return $this;
    }




// Model
    public function __get($member) {
        return $this->getModel($member);
    }

    public function getModel($name) {
        return axis\Model::factory($name);
    }

    public function getUnit($unitId) {
        return axis\Model::loadUnitFromId($unitId);
    }

    public function getSchema($unitId) {
        return axis\Model::loadUnitFromId($unitId)->getUnitSchema();
    }

    public function getSchemaField($unitId, $field) {
        return $this->getSchema($unitId)->getField($field);
    }


// Data helpers
    public function hasRelation($record, $field, $idField=null) {
        return (bool)$this->getRelationId($record, $field, $idField);
    }

    public function getRelationId($record, $field, $idField=null) {
        $output = null;

        if($record instanceof opal\record\IRecord) {
            $output = $record->getRawId($field);
        } else if(is_array($record)) {
            if(isset($record[$field])) {
                $output = $record[$field];
            } else {
                $output = null;
            }

            if(is_array($output)) {
                if($idField === null && isset($output['id'])) {
                    $output = $output['id'];
                } else if(isset($output[$idField])) {
                    $output = $output[$idField];
                }
            }

            if($output instanceof opal\record\IRecord) {
                $output = $output->getPrimaryKeySet();
            }

            if($output instanceof opal\record\IPrimaryKeySet
            && !$output->isNull()) {
                $output = $output->getValue();
            }
        }

        return $output;
    }

    public function getRelationRecord($record, $field, $allowFetch=false) {
        if(!isset($record[$field])) {
            return null;
        }

        if($record instanceof opal\record\IRecord && !$allowFetch) {
            $output = $record->getRaw($field);

            if($output instanceof opal\record\IPreparedValueContainer) {
                if(!$output->isPrepared()) {
                    return null;
                }

                $output = $output->getValue();
            }

        } else {
            $output = $record[$field];
        }

        if($output instanceof opal\record\IPrimaryKeySet || is_scalar($output)) {
            return null;
        }

        return $output;
    }

    public function stringToBoolean($string) {
        return flex\Text::stringToBoolean($string);
    }



// Mesh
    public function fetchEntity($locator) {
        return $this->context->getMeshManager()->fetchEntity($locator);
    }

    public function fetchEntityForAction($id, $action=null) {
        $actionName = $action;

        if($actionName === null) {
            $actionName = 'access';
        }

        if(!$output = $this->fetchEntity($id)) {
            $this->context->throwError(404, 'Entity not found - '.$id);
        }

        if(!$this->context->getUserManager()->canAccess($output, $action)) {
            $this->context->throwError(401, 'Cannot '.$actionName.' entity '.$id);
        }

        return $output;
    }



// JSON
    public function jsonEncodeCollectionQuery(opal\query\IReadQuery $query, array $extraData=null, $rowSanitizer=null, int $flags=0) {
        if($extraData === null) {
            $extraData = [];
        }

        $data = $query->toArray();

        if($rowSanitizer) {
            $rowSanitizer = core\lang\Callback::factory($rowSanitizer);

            foreach($data as $key => $row) {
                $data[$key] = $rowSanitizer->invoke($row, $key);
            }
        }

        $extraData['data'] = $data;
        $extraData['paginator'] = $query->getPaginator();
        return flex\json\Codec::encode($extraData, $flags);
    }

    public function jsonEncode($data, int $flags=0) {
        return flex\json\Codec::encode($data, $flags);
    }

    public function jsonDecode($data) {
        return flex\json\Codec::decode($data);
    }

    public function jsonDecodeFile($path) {
        return flex\json\Codec::decodeFile($path);
    }


// Crypt
    public function hash($message, $salt=null) {
        if($salt === null) {
            $salt = $this->context->application->getPassKey();
        }

        return core\crypt\Util::passwordHash($message, $salt);
    }

    public function hexHash($message, $salt=null) {
        return bin2hex($this->hash($message, $salt));
    }

    public function encrypt($message, $password=null, $salt=null) {
        if($password === null) {
            $password = $this->context->application->getPassKey();
            $salt = $this->context->application->getUniquePrefix();
        }

        return core\crypt\Util::encrypt($message, $password, $salt);
    }

    public function decrypt($message, $password=null, $salt=null) {
        if($password === null) {
            $password = $this->context->application->getPassKey();
            $salt = $this->context->application->getUniquePrefix();
        }

        return core\crypt\Util::decrypt($message, $password, $salt);
    }
}
