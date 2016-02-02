<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\cache\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;

trait TApcClear {

    protected static $_ext;
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

        if(extension_loaded('apc')) {
            self::$_ext = 'apc';
        } else if(extension_loaded('apcu')) {
            self::$_ext = 'apcu';
        } else {
            $this->io->writeLine('APC is not enabled');
            return false;
        }

        self::$_apcu = extension_loaded('apcu');

        $count = 0;

        if($isPurge) {
            $list = $this->_getCacheList();

            if($purgeType == 'app') {
                $prefix = $this->application->getUniquePrefix().'-';

                foreach($list as $set) {
                    if(0 === strpos($set[self::$_setKey], $prefix)) {
                        $count++;
                        @call_user_func(self::$_ext.'_delete', $set[self::$_setKey]);
                    }
                }
            } else {
                $count = count($list);

                if(self::$_ext == 'apcu') {
                    apcu_clear_cache();
                } else if(self::$_apcu) {
                    apc_clear_cache();
                } else {
                    apc_clear_cache('user');
                    apc_clear_cache('system');
                }
            }
        } else if(isset($this->request['remove'])) {
            $key = $this->request['remove'];
            call_user_func(self::$_ext.'_delete', $prefix.$key);
            $count++;
        } else if(isset($this->request['clearBegins'])) {
            $key = $this->request['clearBegins'];

            foreach($this->_getCacheList() as $set) {
                if(0 === strpos($set[self::$_setKey], $prefix.$key)) {
                    call_user_func(self::$_ext.'_delete', $set[self::$_setKey]);
                    $count++;
                }
            }
        } else if(isset($this->request['clearMatches'])) {
            $regex = $this->request['clearMatches'];
            $prefixLength = strlen($this->_prefix);

            foreach($this->_getCacheList() as $set) {
                if(0 === strpos($set[self::$_setKey], $prefix)
                && preg_match($regex, substr($set[self::$_setKey], $prefixLength))) {
                    call_user_func(self::$_ext.'_delete', $set[self::$_setKey]);
                    $count++;
                }
            }
        } else {
            foreach($this->_getCacheList() as $set) {
                if(0 === strpos($set[self::$_setKey], $prefix)) {
                    call_user_func(self::$_ext.'_delete', $set[self::$_setKey]);
                    $count++;
                }
            }
        }

        return $count;
    }

    protected function _getCacheList() {
        if(self::$_ext === 'apcu') {
            $info = apcu_cache_info();
        } else if(self::$_apcu) {
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