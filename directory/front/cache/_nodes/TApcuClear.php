<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\cache\_nodes;

use DecodeLabs\Exceptional;

use DecodeLabs\R7\Legacy;
use DecodeLabs\Terminus as Cli;
use df\core;

trait TApcuClear
{
    protected function _clearApcu()
    {
        $isPurge = isset($this->request['purge']);
        $purgeType = null;

        if ($isPurge) {
            switch (strtolower((string)$this->request['purge'])) {
                case 'all':
                    $purgeType = 'all';
                    break;

                default:
                    $purgeType = 'app';
                    break;
            }
        }

        $cacheId = $this->request['cacheId'];

        if ($isPurge) {
            $prefix = null;
        } else {
            if (!$cacheId) {
                throw Exceptional::UnexpectedValue(
                    'Cache id not specified'
                );
            }

            $prefix = Legacy::getUniquePrefix() . '-' . $cacheId . ':';
        }

        if (!extension_loaded('apcu')) {
            if (Cli::isActiveSapi()) {
                Cli::warning('APCU is not enabled');
            }

            return false;
        }


        $count = 0;

        if ($isPurge) {
            $list = core\cache\backend\Apcu::getCacheList();

            if ($purgeType == 'app') {
                $prefix = Legacy::getUniquePrefix() . '-';

                foreach ($list as $set) {
                    if (0 === strpos($set['info'], $prefix)) {
                        $count++;
                        @apcu_delete($set['info']);
                    }
                }
            } else {
                $count = count($list);
                apcu_clear_cache();
            }
        } elseif (isset($this->request['remove'])) {
            $key = $this->request['remove'];
            apcu_delete($prefix . $key);
            $count++;
        } elseif (isset($this->request['clearBegins'])) {
            $key = $this->request['clearBegins'];

            foreach (core\cache\backend\Apcu::getCacheList() as $set) {
                if (0 === strpos($set['info'], $prefix . $key)) {
                    apcu_delete($set['info']);
                    $count++;
                }
            }
        } elseif (isset($this->request['clearMatches'])) {
            $regex = $this->request['clearMatches'];
            $prefixLength = strlen($this->_prefix);

            foreach (core\cache\backend\Apcu::getCacheList() as $set) {
                if (0 === strpos($set['info'], (string)$prefix)
                && preg_match($regex, substr($set['info'], $prefixLength))) {
                    apcu_delete($set['info']);
                    $count++;
                }
            }
        } else {
            foreach (core\cache\backend\Apcu::getCacheList() as $set) {
                if (0 === strpos($set['info'], (string)$prefix)) {
                    apcu_delete($set['info']);
                    $count++;
                }
            }
        }

        return $count;
    }
}
