<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\theme\facet;

use df;
use df\core;
use df\aura;

class TypeKit extends Base {

    protected $_kitId;

    public function setKitId($kitId) {
        $this->_kitId = $kitId;
        return $this;
    }

    public function getKitId() {
        return $this->_kitId;
    }

    public function afterHtmlViewRender(aura\view\IHtmlView $view) {
        if(!$this->_kitId) {
            return;
        }

        $view
            ->linkJs('http://use.typekit.net/'.$this->_kitId.'.js')
            ->addScript('typekit', 'try{Typekit.load();}catch(e){}');
    }
}