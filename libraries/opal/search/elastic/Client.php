<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\search\elastic;

use df;
use df\core;
use df\opal;

class Client implements opal\search\IClient {
    
    use opal\search\TClient;
    
    public static function factory($settings) {
        $settings = self::_normalizeSettings($settings);
        
        // TODO: cache output?
        
        return new self($settings);
    }
    
    protected function __construct(core\collection\ITree $settings) {
        
    }
    
    public function getIndex($name) {
        return new Index($this, $name);
    }
    
    public function getIndexList() {
        
    }
}
