<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\cache\_actions;

use df;
use df\core;
use df\apex;
use df\arch;

trait TApcClear {
    
    protected static $_apcu;

    protected function _clearApc() {
        $isPurge = isset($this->request->query->purge);

        if(!$isPurge && !($cacheId = $this->request->query['cacheId'])) {
            $this->throwError(500, 'Cache id not specified');
        }

        if(!$isPurge) {
            $prefix = $this->application->getUniquePrefix().'-'.$cacheId.':';
        }
        
        self::$_apcu = version_compare(PHP_VERSION, '5.5.0') >= 0;
        $setKey = self::$_apcu ? 'key' : 'info';
        $count = 0;

        if($isPurge) {
            $count = count($this->_getCacheList());

            if(self::$_apcu) {
                apc_clear_cache();
            } else {
                apc_clear_cache('user');
                apc_clear_cache('system');
            }
        } else if(isset($this->request->query->remove)) {
            $key = $this->request->query['remove'];
            apc_delete($prefix.$key);
            $count++;
        } else if(isset($this->request->query->clearBegins)) {
            $key = $this->request->query['clearBegins'];

            foreach($this->_getCacheList() as $set) {
                if(0 === strpos($set[$setKey], $prefix.$key)) {
                    apc_delete($set[$setKey]);
                    $count++;
                }
            }
        } else if(isset($this->request->query->clearMatches)) {
            $regex = $this->request->query['clearMatches'];
            $prefixLength = strlen($this->_prefix);

            foreach($this->_getCacheList() as $set) {
                if(0 === strpos($set[$setKey], $prefix)
                && preg_match($regex, substr($set[$setKey], $prefixLength))) {
                    apc_delete($set[$setKey]);
                    $count++;
                }
            }
        } else {
            foreach($this->_getCacheList() as $set) {
                if(0 === strpos($set[$setKey], $prefix)) {
                    apc_delete($set[$setKey]);
                    $count++;
                }
            }
        }

        return $count;
    }

    protected function _getCacheList() {
        if(self::$_apcu) {
            $info = apc_cache_info();
        } else {
            $info = apc_cache_info('user');
        }

        if(isset($info['cache_list'])) {
            return $info['cache_list'];
        }

        return [];
    }
}