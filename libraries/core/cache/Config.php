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
        return array(
            'caches' => array(),
            'backends' => array(
                'Memcache' => array(
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'persistent' => true
                )
            )
        );
    }
    
    public function getOptionsFor(ICache $cache) {
        $id = $cache->getCacheId();
        $output = null;
        
        if(isset($this->_caches[$id])) {
            $output = $this->_caches[$id];
        }
        
        if(isset($output['backend'])) {
            $list = array($output['backend']);
        } else if($cache->isCacheDistributed()) {
            $list = array('Memcache'/*, 'File'*/);
        } else {
            $list = array('Apc', 'Memcache'/*, 'Sqlite', 'File'*/);
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
}
