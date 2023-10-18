<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace DecodeLabs\R7\Config;

use DecodeLabs\Coercion;
use DecodeLabs\Dovetail\Config;
use DecodeLabs\Dovetail\ConfigTrait;
use DecodeLabs\Dovetail\Repository;
use df\link\geoIp\Adapter;

class GeoIp implements Config
{
    use ConfigTrait;

    public static function getDefaultValues(): array
    {
        return [
            'enabled' => false,
            'defaultAdapter' => 'MaxMindDb',
            'adapters' => [
                'MaxMindDb' => [
                    'file' => null
                ]
            ]
        ];
    }

    public function isEnabled(): bool
    {
        return (bool)$this->data['enabled'];
    }

    public function getDefaultAdapter(): ?string
    {
        return Coercion::toStringOrNull($this->data['defaultAdapter']);
    }

    public function getSettingsFor(
        string|Adapter $adapter
    ): Repository {
        if ($adapter instanceof Adapter) {
            $adapter = $adapter->getName();
        }

        return $this->data->adapters->{$adapter};
    }
}
