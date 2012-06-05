<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\search;

use df;
use df\core;
use df\opal;


trait TClient {
    
    protected static function _normalizeSettings($settings) {
        if(!$settings instanceof core\collection\ITree) {
            $settings = new core\collection\Tree($settings);
        }
        
        return $settings;
    }
}
