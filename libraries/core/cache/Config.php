<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\cache;

use df\core;

class Config extends core\Config
{
    public const ID = 'Cache';
    public const STORE_IN_MEMORY = true;

    public function getDefaultValues(): array
    {
        return [
            'caches' => [],
            'backends' => [
                'Memcache' => [
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'persistent' => true
                ]
            ]
        ];
    }

    public function getOptionsFor(ICache $cache, $mergeDefaults = true)
    {
        $id = $cache->getCacheId();

        if (isset($this->values->caches->{$id})) {
            $output = clone $this->values->caches->{$id};
            $default = false;
        } else {
            $output = clone $this->values->caches->default;
            $default = true;
        }

        if (is_string($output->getValue())) {
            $output = new core\collection\Tree(['backend' => $output->getValue()]);
        }

        $list = [];

        if (!$default && isset($output->backend)) {
            $list[] = $output['backend'];
        } elseif ($mergeDefaults) {
            if ($cache->isCacheDistributed()) {
                $list = ['Memcached', 'Memcache', 'LocalFile'];
            } else {
                $list = ['Memcached', 'Apcu', 'Memcache', 'LocalFile'];
            }
        }

        if ($default && in_array($output['backend'], $list)) {
            $list = array_merge([$output['backend']], $list);
        }

        foreach ($list as $name) {
            $class = 'df\\core\\cache\\backend\\' . $name;

            if (!class_exists($class) || !$class::isLoadable()) {
                continue;
            }

            $output['backend'] = $name;
            $output->import($this->values->backends->{$output['backend']});

            break;
        }

        return $output;
    }

    public function getBackendOptions($backend)
    {
        return $this->values->backends->{$backend};
    }
}
