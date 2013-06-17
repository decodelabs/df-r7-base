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
    const USE_ENVIRONMENT_ID_BY_DEFAULT = true;
    const STORE_IN_MEMORY = true;
    
    public function getDefaultValues() {
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
        $output = null;
        
        if(isset($this->_caches[$id])) {
            $output = $this->_values['caches'][$id];
        }

        $list = array();
        
        if(isset($output['backend'])) {
            $list[] = $output['backend'];
        } else if($mergeDefaults) {
            if($cache->isCacheDistributed()) {
                $list = array('Memcache', 'LocalFile');
            } else {
                $list = array('Apc', 'Memcache', /*'Sqlite',*/ 'LocalFile');
            }
        }
        
        foreach($list as $name) {
            $class = 'df\\core\\cache\\backend\\'.$name;
            
            if(!class_exists($class) || !$class::isLoadable()) {
                continue;
            }
            
            $output['backend'] = $name;
            
            if(isset($this->_values['backends'][$output['backend']])) {
                $output = array_merge($output, $this->_values['backends'][$output['backend']]);
            }
            
            break;
        }
        
        return new core\collection\Tree($output);
    }

    public function getBackendOptions($backend) {
        $output = array();

        if(isset($this->_values['backends'][$backend])) {
            $output = $this->_values['backends'][$backend];
        }

        if(!is_array($output)) {
            $output = array($output);
        }

        return $output;
    }
}
