<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;

class Hidden extends Base implements IFormDataWidget, core\IDumpable {
    
    use TWidget_FormData;
    
    const PRIMARY_TAG = 'input';
    const ARRAY_INPUT = false;
    
    public function __construct($name, $value=null) {
        $this->setName($name);
        $this->setValue($value);
    }
    
    protected function _render() {
        $tag = $this->getTag();
        
        $tag->setAttribute('type', 'hidden');
        $this->_applyFormDataAttributes($tag);
        
        return $tag;
    }
}
