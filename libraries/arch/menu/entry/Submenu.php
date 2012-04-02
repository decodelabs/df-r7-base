<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\menu;

use df;
use df\core;
use df\arch;

class Submenu extends Base {
    
    protected $_delegate;
    protected $_text;
    protected $_icon;
    
    protected static function _fromArray(array $entry) {
        $tree = new core\collection\Tree($entry);
        
        return (new self(
                $tree['delegate'],
                $tree['text'],
                $tree['icon']
            ))
            ->setId($tree['id'])
            ->setWeight($tree['weight']);
    }
    
    public function __construct($delegate, $text, $icon=null) {
        $this->setDelegate($delegate);
        $this->setText($text);
        $this->setIcon($icon);
    }
    
    public function toArray() {
        return array(
            'type' => 'Submenu',
            'id' => $this->getId(),
            'weight' => $this->getWeight(),
            'delegate' => $this->_delegate,
            'text' => $this->_text,
            'icon' => $this->_icon
        );
    }
    
    public function getId() {
        if($this->_id === null) {
            return $this->_id = 'submenu-'.md5((string)$this->getDelegate());
        }
        
        return parent::getId();
    }
    
    public function setDelegate($delegate) {
        $this->_delegate = (string)arch\menu\Base::normalizeId($delegate);
        return $this;
    }
    
    public function getDelegate() {
        return $this->_delegate;
    }
    
    public function setText($text) {
        $this->_text = $text;
        return $this;
    }
    
    public function getText() {
        return $this->_text;
    }
    
    public function setIcon($icon) {
        $this->_icon = $icon;
        return $this;
    }
    
    public function getIcon() {
        return $this->_icon;
    }
}
