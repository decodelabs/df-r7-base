<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html;

use df;
use df\core;
use df\aura;
use df\flex;


// Exceptions
interface IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
interface IRenderable {
    public function render();
}

interface IElementRepresentation extends core\IStringProvider, IRenderable {}


interface ITagDataContainer extends core\collection\IAttributeContainer {
    // Data attributes
    public function addDataAttributes(array $attributes);
    public function setDataAttribute($key, $value);
    public function getDataAttribute($key, $default=null);
    public function hasDataAttribute($key);
    public function removeDataAttribute($key);
    public function getDataAttributes();

    // Class attributes
    public function setClasses(...$classes);
    public function addClasses(...$classes);
    public function getClasses();
    public function setClass(...$classes);
    public function addClass(...$classes);
    public function removeClass(...$classes);
    public function hasClass(...$classes);
    public function countClasses();

    // Direct attributes
    public function setId($id);
    public function getId();
    public function isHidden(bool $flag=null);
    public function setTitle(string $title=null);
    public function getTitle();


    // Style
    public function setStyles(...$styles);
    public function addStyles(...$styles);
    public function getStyles();
    public function setStyle($key, $value);
    public function getStyle($key, $default=null);
    public function removeStyle(...$keys);
    public function hasStyle(...$keys);
}



interface ITag extends IElementRepresentation, \ArrayAccess, ITagDataContainer, flex\IStringEscapeHandler, core\lang\IChainable {
    // Name
    public function setName($name);
    public function getName();
    public function isInline();
    public function isBlock();

    // Render count
    public function getRenderCount();

    // Strings
    public function open();
    public function close();
    public function renderWith($innerContent=null, $expanded=false);
    public function shouldRenderIfEmpty(bool $flag=null);
}


interface IWidgetFinder {
    public function getFirstWidgetOfType($type);
    public function getAllWidgetsOfType($type);
    public function findFirstWidgetOfType($type);
    public function findAllWidgetsOfType($type);
}




interface IElementContent extends IElementRepresentation, core\lang\IChainable {
    public function setParentRenderContext($parent);
    public function getParentRenderContext();
    public function getElementContentString();
    public function esc($value);
}

interface IElementContentCollection extends
    IElementContent,
    IWidgetFinder,
    core\collection\IIndexedQueue,
    core\collection\IAggregateIteratorCollection {}

trait TElementContent {

    use core\TStringProvider;
    use core\lang\TChainable;
    use core\collection\TArrayCollection;
    use core\collection\TArrayCollection_Constructor;
    use core\collection\TArrayCollection_ProcessedIndexedValueMap;
    use core\collection\TArrayCollection_Seekable;
    use core\collection\TArrayCollection_Sliceable;
    use core\collection\TArrayCollection_ProcessedShiftable;
    use core\collection\TArrayCollection_IndexedMovable;

    protected $_parent;

    public function import(...$input) {
        foreach(core\collection\Util::leaves($input, true) as $value) {
            $this->_collection[] = $value;
        }

        return $this;
    }

    public function setParentRenderContext($parent) {
        $this->_parent = $parent;
        return $this;
    }

    public function getParentRenderContext() {
        return $this->_parent;
    }

    public function toString(): string {
        return $this->getElementContentString();
    }

    public function getElementContentString() {
        $output = '';
        $lastElement = null;

        foreach($this->_collection as $value) {
            if(empty($value) && $value != '0') {
                continue;
            }

            $stringValue = (string)$this->_renderChild($value);
            $isBlock = false;

            if($value instanceof aura\html\widget\IWidget
            || $value instanceof aura\html\widget\IWidgetProxy) {
                $isBlock = $value->isBlock();
                $stringValue = trim($stringValue);
            } else if($value instanceof ITag) {
                $isBlock = $value->isBlock();
            } else if(preg_match('/\<\/?([a-zA-Z0-9]+)( |\>)/i', $stringValue, $matches)) {
                $isBlock = !Tag::isInlineTagName($matches[1]);
            }

            if($isBlock) {
                $stringValue = $stringValue."\n";
            }

            $output .= $stringValue;
            continue;
        }

        return $output;
    }

    protected function _renderChild(&$value) {
        if(is_callable($value) && is_object($value)) {
            $value = $value($this->_parent ?? $this);
            return $this->_renderChild($value);
        }

        if(is_array($value) || $value instanceof \Generator) {
            $output = '';

            foreach($value as $part) {
                $output .= $this->_renderChild($part);
            }

            return $output;
        }

        if($value instanceof aura\html\widget\IWidgetProxy) {
            $value = $value->toWidget();
        }

        if($value instanceof aura\view\IDeferredRenderable
        && $this instanceof aura\view\IDeferredRenderable) {
            $value->setRenderTarget($this->getRenderTarget());
        }

        $test = false;

        if($value instanceof IRenderable) {
            $output = $value->render();
            $test = 1;
        } else if($value instanceof aura\view\IDeferredRenderable) {
            $value = $value->render();

            if(is_string($value)) {
                $value = new ElementString($value);
            }

            $output = $value = $this->_renderChild($value);
            $test = 2;
        } else if($value instanceof aura\view\IRenderable) {
            $value = $value->renderTo($this->getRenderTarget());

            if(is_string($value)) {
                $value = new ElementString($value);
            }

            $output = $value = $this->_renderChild($value);
            $test = 3;
        } else {
            $output = (string)$value;
            $test = 4;
        }


        if(!$value instanceof IElementRepresentation) {
            $output = $this->esc($output);
        }

        return $output;
    }

    protected function _expandInput($input) {
        if(!is_array($input)) {
            $input = [$input];
        }

        foreach($input as $i => $value) {
            if($value instanceof aura\html\widget\IWidgetProxy) {
                $input[$i] = $value->toWidget();
            }
        }

        return $input;
    }

    public function render() {
        return new ElementString($this->toString());
    }



    public function getFirstWidgetOfType($type) {
        foreach($this->_collection as $child) {
            if($child instanceof aura\html\widget\IWidget && $child->getWidgetName() == $type) {
                return $child;
            }
        }

        return null;
    }

    public function getAllWidgetsOfType($type) {
        $output = [];

        foreach($this->_collection as $child) {
            if($child instanceof aura\html\widget\IWidget && $child->getWidgetName() == $type) {
                $output[] = $child;
            }
        }

        return $output;
    }

    public function findFirstWidgetOfType($type) {
        foreach($this->_collection as $child) {
            if($child instanceof aura\html\widget\IWidget
            && $child->getWidgetName() == $type) {
                return $child;
            }

            if($child instanceof IWidgetFinder) {
                if($ret = $child->findFirstWidgetOfType($type)) {
                    return $ret;
                }
            }
        }

        return null;
    }

    public function findAllWidgetsOfType($type) {
        $output = [];

        foreach($this->_collection as $child) {
            if(!$child instanceof aura\html\widget\IWidget
            && $child->getWidgetName() == $type) {
                $output[] = $child;
            }

            if($child instanceof IWidgetFinder) {
                $output = array_merge($output, $child->findAllWidgetsOfType($type));
            }
        }

        return $output;
    }

    public function getReductiveIterator() {
        return new ReductiveIndexIterator($this);
    }
}


class ElementContent implements IElementContentCollection, core\IDumpable {

    use TElementContent;
    use flex\THtmlStringEscapeHandler;

    public static function normalize($content, $parent=null) {
        return new aura\html\ElementString(
            (new self($content, $parent))->toString()
        );
    }

    public function __construct($content=null, $parent=null) {
        $this->setParentRenderContext($parent);

        if($content !== null) {
            $this->import($content);
        }
    }
}


class ElementString implements IElementRepresentation, core\IDumpable {

    protected $_content;

    public function __construct($content) {
        $this->_content = (string)$content;
    }

    public function __toString(): string {
        return $this->_content;
    }

    public function toString(): string {
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

    public function isEmpty() {
        return !strlen($this->_content);
    }

    public function getDumpProperties() {
        return $this->_content;
    }
}


interface IElement extends ITag, IElementContent, IWidgetFinder {}


interface IStyleBlock extends core\IStringProvider {}
interface IStyleCollection extends core\collection\IMap, core\collection\IAggregateIteratorCollection, core\IStringProvider {}
