<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace DecodeLabs\R7\Config;

use DecodeLabs\Dovetail\Config;
use DecodeLabs\Dovetail\ConfigTrait;
use DecodeLabs\Dovetail\Repository;
use df\core\cache\ICache;

class Cache implements Config
{
    use ConfigTrait;


    public static function getDefaultValues(): array
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

    public function getOptionsFor(
        ICache $cache,
        bool $mergeDefaults = true
    ): Repository {
        $id = $cache->getCacheId();

        if (isset($this->data->caches->{$id})) {
            /** @var Repository $output */
            $output = clone $this->data->caches->{$id};
            $default = false;
        } else {
            /** @var Repository $output */
            $output = clone $this->data->caches->default;
            $default = true;
        }

        if (is_string($output->getValue())) {
            $output = new Repository(['backend' => $output->getValue()]);
        }

        $list = [];

        if (
            !$default &&
            isset($output->backend)
        ) {
            $list[] = $output['backend'];
        } elseif ($mergeDefaults) {
            if ($cache->isCacheDistributed()) {
                $list = ['Memcached', 'Memcache', 'LocalFile'];
            } else {
                $list = ['Memcached', 'Apcu', 'Memcache', 'LocalFile'];
            }
        }

        if (
            $default &&
            in_array($output['backend'], $list)
        ) {
            $list = array_merge([$output['backend']], $list);
        }

        foreach ($list as $name) {
            $class = 'df\\core\\cache\\backend\\' . $name;

            if (
                !class_exists($class) ||
                !$class::isLoadable()
            ) {
                continue;
            }

            $output['backend'] = $name;
            $output->merge($this->data->backends->{$output['backend']});

            break;
        }

        return $output;
    }

    public function getBackendOptions(string $backend): Repository
    {
        return $this->data->backends->{$backend};
    }
}
