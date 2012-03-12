<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html;

use df;
use df\core;
use df\aura;


// Exceptions
interface IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
interface IRenderable {
    public function render();
}

interface IElementRepresentation extends core\IStringProvider, IRenderable {}


interface ITagDataContainer extends core\IAttributeContainer {
    // Data attributes
    public function setDataAttribute($key, $value);
    public function getDataAttribute($key, $default=null);
    public function hasDataAttribute($key);
    public function removeDataAttribute($key);
    public function getDataAttributes();
    
    // Class attributes
    public function setClasses($classes);
    public function addClasses($classes);
    public function getClasses();
    public function setClass($class);
    public function addClass($class);
    public function removeClass($class);
    public function hasClass($class);
    
    // Id
    public function setId($id);
    public function getId();
    
    // Style
    public function setStyles($styles);
    public function addStyles($styles);
    public function getStyles();
    public function setStyle($key, $value);
    public function getStyle($key, $default=null);
    public function removeStyle($key);
    public function hasStyle($key);
}



interface ITag extends IElementRepresentation, \ArrayAccess, ITagDataContainer, core\string\IStringEscapeHandler {
    // Name
    public function setName($name);
    public function getName();
    
    // Render count
    public function getRenderCount();
    
    // Strings
    public function open();
    public function close();
    public function renderWith($innerContent=null, $expanded=false);
}



interface IElementContent extends IElementRepresentation {
    public function getElementContentString();
    public function esc($value);
}

interface IElementContentCollection extends 
    IElementContent,
    core\collection\IIndexedQueue, 
    core\collection\IAggregateIteratorCollection {}

trait TElementContent {
    
    use core\TStringProvider;
    use core\collection\TArrayCollection_Queue;
    
    public function toString() {
        return $this->getElementContentString();
    }
    
    public function getElementContentString() {
        $output = '';
        $lastElement = null;
        
        foreach($this->_collection as $value) {
            if(empty($value) && $value != '0') {
                continue;
            }
            
            $stringValue = $this->_renderChild($value);
            $isWidget = false;
            
            if($value instanceof aura\html\widget\IWidget) {
                $isWidget = true;
                $stringValue = trim($stringValue)."\n";
            }
            
            if(($isWidget || $value instanceof IElementRepresentation)
            && $lastElement instanceof IElementRepresentation) {
                $stringValue = "\n".$stringValue;
            }
            
            $output .= $stringValue;
            $lastElement = $value;
            continue;
        }
        
        return rtrim($output);
    }

    protected function _renderChild($value) {
        if(is_array($value)) {
            $output = '';
            
            foreach($value as $part) {
                $output .= $this->_renderChild($part);
            }
            
            return $output;
        }
        
        if($value instanceof IRenderable) {
            $output = $value->render();
        } else {
            $output = (string)$value;
        }
        
        if(!$value instanceof IElementRepresentation
        && !$value instanceof aura\view\content\ErrorContainer) {
            $output = $this->esc($output);
        }
        
        return $output;
    }
    
    public function render() {
        return new ElementString($this->toString());
    }
}


class ElementContent implements IElementContentCollection, core\IDumpable {
    
    use TElementContent;
    use core\string\THtmlStringEscapeHandler;
}


class ElementString implements IElementRepresentation, core\IDumpable {
    
    protected $_content;
    
    public function __construct($content) {
        $this->_content = (string)$content;
    }
    
    public function __toString() {
        return $this->_content;
    }
    
    public function toString() {
        return $this->_content;
    }
    
    public function render() {
        return $this;
    }
    
    public function prepend($str) {
        $this->_content = $str.$this->_content;
        return $this;
    }
    
    public function append($str) {
        $this->_content .= $str;
        return $this;
    }
    
    public function getDumpProperties() {
        return $this->_content;
    }
}


interface IElement extends ITag, IElementContent {}


interface IStyleBlock extends core\IStringProvider {}
interface IStyleCollection extends core\IStringProvider {}
