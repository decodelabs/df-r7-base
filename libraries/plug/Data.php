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



// Query shortcuts
    public function fetchForAction($source, $primary, $chain=null) {
        return $this->queryByPrimaryForAction($this->fetch()->from($source), $primary, $chain);
    }

    public function selectForAction($source, $fields, $primary=null, $chain=null) {
        return $this->queryByPrimaryForAction($this->select($fields)->from($source), $primary, $chain);
    }

    public function fetchOrCreateForAction($source, $primary, $newChain=null, $queryChain=null) {
        $query = $this->fetch()->from($source);
        $this->applyQueryPrimaryClause($query, $primary);

        if($queryChain) {
            $query->chain($queryChain);
        }

        $output = $query->toRow();

        if(!$output) {
            $output = $this->newRecord($source);

            if($newChain) {
                $values = core\lang\Callback($newChain, $output);

                if(is_array($values)) {
                    $output->import($values);
                }
            }
        }

        return $output;
    }

    public function queryByPrimaryForAction(opal\query\IReadQuery $query, $primary, $chain=null) {
        if($primary === null) {
            $name = $query->getSource()->getDisplayName();
            throw core\Error::{'opal/record/ENotFound'}([
                'message' => 'Item not found - '.$name.'#NULL',
                'http' => 404
            ]);
        }

        $this->applyQueryPrimaryClause($query, $primary);

        if($chain) {
            $query->chain($chain);
        }

        $output = $query->toRow();

        if($output === null) {
            if($primary instanceof \Closure) {
                $primary = '()';
            }

            if(is_array($primary)) {
                $primary = implode(',', $primary);
            }

            $name = $query->getSource()->getDisplayName();
            throw core\Error::{'opal/record/ENotFound'}([
                'message' => 'Item not found - '.$name.'#'.$primary,
                'http' => 404
            ]);
        }

        return $output;
    }


    public function applyQueryPrimaryClause(opal\query\IQuery $query, $primary) {
        if(is_array($primary) && is_string(key($primary))) {
            foreach($primary as $key => $value) {
                $query->where($key, '=', $value);
            }
        } else if($primary instanceof \Closure) {
            $primary($query);
        } else {
            $query->where('@primary', '=', $primary);
        }

        return $this;
    }


    public function queryForAction(opal\query\IReadQuery $query, $chain=null) {
        if($chain) {
            $query->chain($chain);
        }

        $output = $query->toRow();

        if($output === null) {
            $name = $query->getSource()->getDisplayName();
            throw core\Error::{'opal/record/ENotFound'}([
                'message' => 'Item not found - '.$name,
                'http' => 404
            ]);
        }

        return $output;
    }






// Unit objects
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
        return $this->_sourceToAdapter($source)
            ->newRecord($values);
    }

    public function newPartial($source, array $values=null) {
        return $this->_sourceToAdapter($source)
            ->newPartial($values);
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
            throw core\Error::{'opal/record/EUnauthorized'}([
                'message' => 'Cannot '.$actionName.' '.$source->getDisplayName().' items',
                'http' => 401
            ]);
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
            throw core\Error::{'opal/record/ENotFound'}([
                'message' => 'Entity not found - '.$id,
                'http' => 404
            ]);
        }

        if(!$this->context->getUserManager()->canAccess($output, $action)) {
            throw core\Error::{'opal/record/EUnauthorized'}([
                'message' => 'Cannot '.$actionName.' entity '.$id,
                'http' => 401
            ]);
        }

        return $output;
    }



// JSON
    public function queryToJson(opal\query\IReadQuery $query, array $extraData=null, $rowSanitizer=null, int $flags=0): string {
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
        return flex\Json::toString($extraData, $flags);
    }

    public function toJson($data, int $flags=0): string {
        return flex\Json::toString($data, $flags);
    }

    public function toJsonFile($path, $data, int $flags=0): core\fs\IFile {
        return flex\Json::toFile($path, $data, $flags);
    }

    public function fromJson(string $data) {
        return flex\Json::fromString($data);
    }

    public function fromJsonFile($path) {
        return flex\Json::fromFile($path);
    }

    public function jsonToTree(string $data): core\collection\ITree {
        return flex\Json::stringToTree($data);
    }

    public function jsonFileToTree($path): core\collection\ITree {
        return flex\Json::fileToTree($path);
    }


// Crypt
    public function hash($message, $salt=null) {
        if($salt === null) {
            $salt = $this->context->app->getPassKey();
        }

        return core\crypt\Util::passwordHash($message, $salt);
    }

    public function hexHash($message, $salt=null) {
        return bin2hex($this->hash($message, $salt));
    }
}
