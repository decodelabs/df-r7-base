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
    protected static $_setKey;

    protected function _clearApc() {
        $isPurge = isset($this->request['purge']);
        $purgeType = null;

        if($isPurge) {
            switch(strtolower($this->request['purge'])) {
                case 'all':
                    $purgeType = 'all';
                    break;

                default:
                    $purgeType = 'app';
                    break;
            }
        }

        if(!$isPurge && !($cacheId = $this->request['cacheId'])) {
            $this->throwError(500, 'Cache id not specified');
        }

        if(!$isPurge) {
            $prefix = $this->application->getUniquePrefix().'-'.$cacheId.':';
        }

        self::$_apcu = version_compare(PHP_VERSION, '5.5.0') >= 0;
        $count = 0;

        if($isPurge) {
            $list = $this->_getCacheList();

            if($purgeType == 'app') {
                $prefix = $this->application->getUniquePrefix().'-';

                foreach($list as $set) {
                    if(0 === strpos($set[self::$_setKey], $prefix)) {
                        $count++;
                        @apc_delete($set[self::$_setKey]);
                    }
                }
            } else {
                $count = count($list);

                if(self::$_apcu) {
                    apc_clear_cache();
                } else {
                    apc_clear_cache('user');
                    apc_clear_cache('system');
                }
            }
        } else if(isset($this->request['remove'])) {
            $key = $this->request['remove'];
            apc_delete($prefix.$key);
            $count++;
        } else if(isset($this->request['clearBegins'])) {
            $key = $this->request['clearBegins'];

            foreach($this->_getCacheList() as $set) {
                if(0 === strpos($set[self::$_setKey], $prefix.$key)) {
                    apc_delete($set[self::$_setKey]);
                    $count++;
                }
            }
        } else if(isset($this->request['clearMatches'])) {
            $regex = $this->request['clearMatches'];
            $prefixLength = strlen($this->_prefix);

            foreach($this->_getCacheList() as $set) {
                if(0 === strpos($set[self::$_setKey], $prefix)
                && preg_match($regex, substr($set[self::$_setKey], $prefixLength))) {
                    apc_delete($set[self::$_setKey]);
                    $count++;
                }
            }
        } else {
            foreach($this->_getCacheList() as $set) {
                if(0 === strpos($set[self::$_setKey], $prefix)) {
                    apc_delete($set[self::$_setKey]);
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

        $output = [];

        if(isset($info['cache_list'])) {
            $output = $info['cache_list'];

            if(isset($output[0])) {
                self::$_setKey = isset($output[0]['key']) ? 'key' : 'info';
            }
        }

        return $output;
    }
}