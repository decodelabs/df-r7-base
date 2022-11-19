<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\geoIp;

use df\core\collection\ITree;
use df\core\Config as ConfigBase;

class Config extends ConfigBase
{
    public const ID = 'GeoIp';

    public function getDefaultValues(): array
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

    public function isEnabled(bool $flag = null)
    {
        if ($flag !== null) {
            $this->values->enabled = $flag;
            return $this;
        }

        return (bool)$this->values['enabled'];
    }

    public function getDefaultAdapter(): ?string
    {
        return $this->values['defaultAdapter'];
    }

    public function setDefaultAdapter(string $adapter): Config
    {
        $this->values->defaultAdapter = $adapter;
        return $this;
    }

    public function getSettingsFor($adapter): ITree
    {
        if ($adapter instanceof Adapter) {
            $adapter = $adapter->getName();
        }

        return $this->values->adapters->{$adapter};
    }
}
