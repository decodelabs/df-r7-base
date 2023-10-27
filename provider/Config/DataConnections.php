<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace DecodeLabs\R7\Config;

use DecodeLabs\Dovetail\Config;
use DecodeLabs\Dovetail\Repository;
use DecodeLabs\Exceptional;
use df\axis\IUnit;
use Throwable;

class DataConnections implements Config
{
    use EnvNameTrait;

    public const DEFAULT_DSN = 'mysql://user:pass@localhost/database';

    public static function getDefaultValues(): array
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

    public function getAdapterIdFor(IUnit $unit): string
    {
        return $this->getSettingsFor($unit)->adapter->as('string', [
            'default' => 'Rdbms'
        ]);
    }

    public function getSettingsFor(IUnit $unit): Repository
    {
        $connectionId = $this->getConnectionIdFor($unit);

        if (!isset($this->data->connections->{$connectionId})) {
            throw Exceptional::Runtime(
                'There are no connections for ' . $unit->getUnitId() . ', with connection id ' . $connectionId
            );
        }

        return clone $this->data->connections->{$connectionId};
    }

    public function getConnectionIdFor(IUnit $unit): string
    {
        $unitId = $unit->getUnitId();

        if (!isset($this->data->units[$unitId])) {
            $originalId = $unitId;

            $parts = explode('/', $unitId);
            $unitId = array_shift($parts);

            if (!isset($this->data->units[$unitId])) {
                try {
                    $unitId = '@' . $unit->getUnitType();
                } catch (Throwable $e) {
                    $unitId = null;
                }

                if (
                    $unitId === null ||
                    !isset($this->data->units->{$unitId})
                ) {
                    $unitId = 'default';

                    if (!isset($this->data->units[$unitId])) {
                        throw Exceptional::Runtime(
                            'There are no connections matching ' . $originalId
                        );
                    }
                }
            }
        }

        return $this->data->units->{$unitId}->as('string');
    }


    /**
     * @return array<string>
     */
    public function getDefinedUnits(): array
    {
        if (!isset($this->data->units)) {
            return [];
        }

        $output = $this->data->units->toArray();
        unset($output['default'], $output['@search']);

        /** @var array<string> $output */
        $output = array_keys($output);
        return $output;
    }
}
