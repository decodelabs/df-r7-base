<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis;

use df;
use df\core;
use df\axis;

use DecodeLabs\Exceptional;

class Config extends core\Config
{
    const ID = 'DataConnections';
    const USE_ENVIRONMENT_ID_BY_DEFAULT = true;
    const DEFAULT_DSN = 'mysql://user:pass@localhost/database';

    protected $_isSetup = null;

    public function getDefaultValues(): array
    {
        return [
            'connections' => [
                'master' => [
                    'adapter' => 'Rdbms',
                    'dsn' => self::DEFAULT_DSN
                ],
                'search' => [
                    'adapter' => 'Elastic'
                ]
            ],
            'units' => [
                'default' => 'master',
                '@search' => 'search'
            ]
        ];
    }

    public function isSetup()
    {
        if ($this->_isSetup === null) {
            if (!isset($this->values->connections->master)) {
                $this->_isSetup = false;
            } else {
                $node = $this->values->connections->master;

                if ($node->adapter->hasValue() && $node['adapter'] != 'Rdbms') {
                    $this->_isSetup = true;
                } elseif ($node->dsn->hasValue() && $node['dsn'] != self::DEFAULT_DSN) {
                    $this->_isSetup = true;
                } else {
                    $this->_isSetup = false;
                }
            }
        }

        return $this->_isSetup;
    }

    public function getAdapterIdFor(IUnit $unit)
    {
        return $this->getSettingsFor($unit)->get('adapter');
    }

    public function getSettingsFor(IUnit $unit)
    {
        $connectionId = $this->getConnectionIdFor($unit);

        if (!isset($this->values->connections->{$connectionId})) {
            throw Exceptional::Runtime(
                'There are no connections for '.$unit->getUnitId().', with connection id '.$connectionId
            );
        }

        if ($connectionId == 'master' && !$this->isSetup()) {
            return new core\collection\Tree([
                'adapter' => 'Rdbms',
                'dsn' => 'sqlite://default'
            ]);
        }

        return $this->values->connections->{$connectionId};
    }

    public function getConnectionIdFor(IUnit $unit)
    {
        $unitId = $unit->getUnitId();

        if (!isset($this->values->units[$unitId])) {
            $originalId = $unitId;

            $parts = explode('/', $unitId);
            $unitId = array_shift($parts);

            if (!isset($this->values->units[$unitId])) {
                try {
                    $unitId = '@'.$unit->getUnitType();
                } catch (\Throwable $e) {
                    $unitId = null;
                }

                if ($unitId === null || !isset($this->values->units->{$unitId})) {
                    $unitId = 'default';

                    if (!isset($this->values->units[$unitId])) {
                        throw Exceptional::Runtime(
                            'There are no connections matching '.$originalId
                        );
                    }
                }
            }
        }

        return (string)$this->values->units[$unitId];
    }

    public function getConnectionsOfType(...$adapters)
    {
        if ($this->values->connections->isEmpty()) {
            return [];
        }

        $output = [];
        $adapters = core\collection\Util::flatten($adapters);

        foreach ($this->values->connections as $id => $set) {
            if (!isset($set->adapter) || !in_array($set['adapter'], $adapters)) {
                continue;
            }

            $output[$id] = $set;
        }

        return $output;
    }

    public function getDefinedUnits()
    {
        if (!isset($this->values->units)) {
            return [];
        }

        $output = $this->values->units->toArray();
        unset($output['default'], $output['@search']);

        return array_keys($output);
    }
}
