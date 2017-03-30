<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cache;

use df;
use df\core;

class Config extends core\Config {

    const ID = 'Cache';
    const STORE_IN_MEMORY = true;

    public function getDefaultValues(): array {
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

    public function getOptionsFor(ICache $cache, $mergeDefaults=true) {
        $id = $cache->getCacheId();
        $output = clone $this->values->caches->{$id};

        $list = [];

        if(isset($output->backend)) {
            $list[] = $output['backend'];
        } else if($mergeDefaults) {
            if($cache->isCacheDistributed()) {
                $list = ['Memcache', 'LocalFile'];
            } else if($cache->mustCacheBeLocal()) {
                $list = ['Apcu', 'LocalFile'];
            } else {
                $list = ['Apcu', 'Memcache', 'LocalFile'];
            }
        }

        foreach($list as $name) {
            $class = 'df\\core\\cache\\backend\\'.$name;

            if(!class_exists($class) || !$class::isLoadable()) {
                continue;
            }

            $output['backend'] = $name;
            $output->import($this->values->backends->{$output['backend']});

            break;
        }

        return $output;
    }

    public function getBackendOptions($backend) {
        return $this->values->backends->{$backend};
    }
}
