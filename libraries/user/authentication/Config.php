<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\user\authentication;

use df;
use df\core;
use df\user;

use DecodeLabs\R7\Legacy;

class Config extends core\Config
{
    public const ID = 'Authentication';

    public function getDefaultValues(): array
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

    public function isAdapterEnabled($adapter, bool $flag=null)
    {
        if ($adapter instanceof IAdapter) {
            $adapter = $adapter->getName();
        }

        if ($flag !== null) {
            $this->values->{$adapter}->enabled = $flag;
            return $this;
        } else {
            return (bool)$this->values->{$adapter}['enabled'];
        }
    }

    public function getEnabledAdapters()
    {
        $output = [];

        foreach ($this->values as $name => $data) {
            if (!$data['enabled']) {
                continue;
            }

            $output[$name] = $data;
        }

        return $output;
    }

    public function getFirstEnabledAdapter()
    {
        foreach ($this->values as $name => $data) {
            if ($data['enabled']) {
                return $name;
            }
        }

        return null;
    }

    public function setOptionsFor($adapter, $options)
    {
        if ($adapter instanceof IAdapter) {
            $adapter = $adapter->getName();
        }

        if (!isset($options['enabled'])) {
            $options['enabled'] = false;
        }

        $this->values->{$adapter} = $options;
        return $this;
    }

    public function getOptionsFor($adapter)
    {
        if ($adapter instanceof IAdapter) {
            $adapter = $adapter->getName();
        }

        return $this->values->{$adapter};
    }
}
