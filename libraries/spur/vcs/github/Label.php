<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\github;

use df;
use df\core;
use df\spur;

class Label implements ILabel {
    
    use TApiObject;

    protected $_color;

    protected function _importData(core\collection\ITree $data) {
        $this->_id = $data['name'];
        $this->_color = $data['color'];
    }

    public function getName() {
        return $this->_id;
    }

    public function getColor() {
        return $this->_color;
    }
}