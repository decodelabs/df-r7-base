<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\navigation\entry;

use df;
use df\core;
use df\arch;

class Link extends Base {
    
    protected $_location;
    protected $_text;
    protected $_icon;
    protected $_tooltip;
    protected $_description;
    protected $_accessLocks = array();
    protected $_hideIfInaccessible = false;
    protected $_altMatches = array(); 
    protected $_checkMatch = false;
    protected $_newWindow = false;
    protected $_class;
    
    protected static function _fromArray(array $entry) {
        $tree = new core\collection\Tree($entry);
        
        return (new self(
                $tree['location'],
                $tree['text'],
                $tree['icon']
            ))
            ->setId($tree['id'])
            ->setTooltip($tree['tooltip'])
            ->setDescription($tree['id'])
            ->addAccessLocks($tree->accessLocks->toArray())
            ->shouldHideIfInaccessible((bool)$tree['hideIfInaccessible'])
            ->shouldCheckMatch((bool)$tree['checkMatch'])
            ->addAltMatches($tree->altMatches->toArray())
            ->shouldOpenInNewWindow((bool)$tree['newWindow'])
            ->setWeight($tree['weight'])
            ->setClass($tree['class']);
    }
    
    public function __construct($location, $text, $icon=null) {
        $this->setLocation($location);
        $this->setText($text);
        $this->setIcon($icon);
    }
    
    public function toArray() {
        return [
            'type' => 'Link',
            'id' => $this->getId(),
            'weight' => $this->getWeight(),
            'location' => $this->_location,
            'text' => $this->_text,
            'icon' => $this->_icon,
            'tooltip' => $this->_tooltip,
            'description' => $this->_description,
            'accessLocks' => $this->_accessLocks,
            'hideIfInaccessible' => $this->_hideIfInaccessible,
            'checkMatch' => $this->_checkMatch,
            'altMatches' => $this->_altMatches,
            'newWindow' => $this->_newWindow,
            'class' => $this->_class
        ];
    }
    
    public function getId() {
        if($this->_id === null) {
            return $this->_id = 'link-'.md5((string)$this->getLocation());
        }
        
        return parent::getId();
    }
    
    public function setLocation($location) {
        $this->_location = $location;
        return $this;
    }
    
    public function getLocation() {
        return $this->_location;
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

    public function setTooltip($tooltip) {
        $this->_tooltip = $tooltip;
        return $this;
    }

    public function getTooltip() {
        return $this->_tooltip;
    }
    
    public function setDescription($description) {
        $this->_description = $description;
        return $this;
    }
    
    public function getDescription() {
        return $this->_description;
    }
    
    public function addAccessLocks($locks) {
        if(!is_array($locks)) {
            $locks = func_get_args();
        }
        
        foreach($locks as $lock) {
            $this->addAccessLock($lock);
        }
        
        return $this;
    }
    
    public function addAccessLock($lock) {
        $this->_accessLocks[]  = $lock;
        return $this;
    }
    
    public function getAccessLocks() {
        return $this->_accessLocks;
    }
    
    public function shouldHideIfInaccessible($flag=null) {
        if($flag !== null) {
            $this->_hideIfInaccessible = (bool)$flag;
            return $this;
        }
        
        return $this->_hideIfInaccessible;
    }
    
    public function shouldCheckMatch($flag=null) {
        if($flag !== null) {
            $this->_checkMatch = (bool)$flag;
            return $this;
        }
        
        return $this->_checkMatch;
    }
    
    public function addAltMatches($matches) {
        if(!is_array($matches)) {
            $matches = func_get_args();
        }
        
        foreach($matches as $match) {
            $this->addAltMatch($match);
        }
        
        return $this;
    }
    
    public function addAltMatch($match) {
        $match = trim($match);
        
        if(strlen($match)) {
            $this->_altMatches[] = $match;
        }
        
        return $this;
    }
    
    public function getAltMatches() {
        return $this->_altMatches;
    }

    public function clearAltMatches() {
        $this->_altMatches = array();
        return $this;
    }
    
    public function shouldOpenInNewWindow($flag=null) {
        if($flag !== null) {
            $this->_newWindow = (bool)$flag;
            return $this;
        }
        
        return $this->_newWindow;
    }

    public function setClass($class) {
        $this->_class = $class;
        return $this;
    }

    public function getClass() {
        return $this->_class;
    }
}
