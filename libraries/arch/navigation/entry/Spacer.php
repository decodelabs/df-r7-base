<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\navigation\entry;

use df;
use df\core;
use df\arch;

class Spacer extends Base {
    
    public function getId() {
        if($this->_id === null) {
            return $this->_id = 'spacer-'.md5(uniqid('spacer', true));
        }
        
        return parent::getId();
    }
}
