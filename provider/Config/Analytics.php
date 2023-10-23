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
use df\spur\analytics\adapter\Base as AdapterBase;
use df\spur\analytics\ILegacyAdapter;

class Analytics implements Config
{
    use ConfigTrait;


    public static function getDefaultValues(): array
    {
        $output = [];

        foreach (AdapterBase::loadAll() as $name => $adapter) {
            if ($adapter instanceof ILegacyAdapter) {
                continue;
            }

            $set = [
                'enabled' => false,
                'options' => $adapter->getOptions()
            ];

            $attrs = $adapter->getDefaultUserAttributes();

            if (!empty($attrs)) {
                $set['userAttributes'] = $attrs;
            }

            $output[lcfirst($name)] = $set;
        }

        return $output;
    }

    public function isEnabled(): bool
    {
        foreach ($this->data as $adapter) {
            if (!($adapter->get('enabled') ?? true)) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function getAdapters(): Repository
    {
        return clone $this->data;
    }

    /**
     * @return array<string, Repository>
     */
    public function getEnabledAdapters(): array
    {
        $output = [];

        foreach ($this->data as $name => $adapter) {
            if ($adapter->get('enabled') ?? true) {
                /** @var Repository $adapter */
                $output[(string)$name] = clone $adapter;
            }
        }

        return $output;
    }

    public function getAdapter(string $name): ?Repository
    {
        if (!isset($this->data->{$name})) {
            return null;
        }

        return $this->data->{$name};
    }

    public function isAdapterEnabled(string $name): bool
    {
        if (!isset($this->data->{$name})) {
            return false;
        }

        return Coercion::toBool($this->data->{$name}->get('enabled') ?? true);
    }
}
