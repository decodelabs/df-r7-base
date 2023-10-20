<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace DecodeLabs\R7\Config;

use DecodeLabs\Dovetail\Config;
use DecodeLabs\Dovetail\ConfigTrait;
use DecodeLabs\Dovetail\Repository;
use DecodeLabs\R7\Legacy;
use df\user\authentication\IAdapter;

class Authentication implements Config
{
    use ConfigTrait;


    public static function getDefaultValues(): array
    {
        $output = [];

        foreach (Legacy::getLoader()->lookupClassList('user/authentication/adapter') as $name => $class) {
            $output[$name] = $class::getDefaultConfigValues();

            if (!isset($output[$name]['enabled'])) {
                $output[$name]['enabled'] = false;
            }
        }

        return $output;
    }

    public function isAdapterEnabled(
        string|IAdapter|null $adapter
    ): bool {
        if ($adapter === null) {
            return false;
        }

        if ($adapter instanceof IAdapter) {
            $adapter = $adapter->getName();
        }

        return (bool)$this->data->{$adapter}['enabled'];
    }

    /**
     * @return array<string, Repository>
     */
    public function getEnabledAdapters(): array
    {
        $output = [];

        foreach ($this->data as $name => $data) {
            if (!$data['enabled']) {
                continue;
            }

            /** @var Repository $data */
            $output[(string)$name] = $data;
        }

        return $output;
    }

    public function getFirstEnabledAdapter(): ?string
    {
        foreach ($this->data as $name => $data) {
            if ($data['enabled']) {
                return (string)$name;
            }
        }

        return null;
    }

    public function getOptionsFor(
        string|IAdapter $adapter
    ): Repository {
        if ($adapter instanceof IAdapter) {
            $adapter = $adapter->getName();
        }

        return $this->data->{$adapter};
    }
}
