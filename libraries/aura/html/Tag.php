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

class Tag implements ITag, core\IDumpable {

    use core\lang\TChainable;
    use core\collection\TArrayAccessedAttributeContainer;
    use core\TStringProvider;
    use flex\THtmlStringEscapeHandler;

    private static $_closedTags = [
        'area', 'base', 'br', 'col', 'command', 'embed',
        'hr', 'img', 'input', 'keygen', 'link', 'meta',
        'param', 'source', 'wbr'
    ];

    private static $_inlineTags = [
        'a', 'br', 'bdo', 'abbr', 'blink', 'nextid', 'acronym', 'basefont',
        'b', 'em', 'big', 'cite', 'input', 'spacer', 'listing',
        'i', 'rp', 'del', 'code', 'label', 'strike', 'marquee',
        'q', 'rt', 'ins', 'font', 'small', 'strong',
        's', 'tt', 'sub', 'mark',
        'u', 'xm', 'sup', 'nobr',
                   'var', 'ruby',
                   'wbr', 'span',
                          'time',
    ];

    private static $_booleanAttributes = [
        'spellcheck'
    ];

    protected $_name;
    protected $_isClosable = true;
    protected $_classes = [];
    protected $_renderCount = 0;
    protected $_renderIfEmpty = true;

    public static function isClosableTagName($name) {
        return in_array(strtolower($name), self::$_closedTags);
    }

    public static function isInlineTagName($name) {
        return in_array(strtolower($name), self::$_inlineTags);
    }

    public function __construct($name, array $attributes=null) {
        $this->setName($name);

        if($attributes !== null) {
            $this->addAttributes($attributes);
        }
    }


// Name
    public function setName($name) {
        if(false !== strpos($name, '#')) {
            $parts = explode('#', $name, 2);
            $name = array_shift($parts);
            $id = array_shift($parts);

            if(false !== strpos($id, '.')) {
                $classes = explode('.', $id);
                $id = array_shift($classes);
                $this->addClasses($classes);
            }

            if(!empty($id)) {
                $this->setId($id);
            }
        }

        if(false !== strpos($name, '.')) {
            $classes = explode('.', $name);
            $name = array_shift($classes);
            $this->addClasses($classes);
        }

        $this->_name = $name;
        $this->_isClosable = !in_array($this->_name, self::$_closedTags);

        return $this;
    }

    public function getName() {
        return $this->_name;
    }

    public function isInline() {
        return in_array(strtolower($this->_name), self::$_inlineTags);
    }

    public function isBlock() {
        return !in_array(strtolower($this->_name), self::$_inlineTags);
    }


// Render count
    public function getRenderCount() {
        return $this->_renderCount;
    }


// Accessors
    public function __get($member) {
        switch($member) {
            case 'class':
                return $this->getClasses();

            case 'style':
                return $this->getStyles();
        }

        return null;
    }

    public function __set($member, $value) {
        switch($member) {
            case 'class':
                return $this->setClasses($value);

            case 'style':
                return $this->setStyles($value);
        }
    }


// Attributes
    public function getAttributes() {
        $output = $this->_attributes;

        if(!empty($this->_classes)) {
            $output['class'] = implode(' ', $this->_classes);
        }

        return $output;
    }

    public function setAttribute($key, $value) {
        $key = strtolower($key);

        if($key == 'style' && !$value instanceof IStyleCollection) {
            $value = new StyleCollection($value);
        } else if($key == 'class') {
            return $this->setClasses($value);
        }

        if($value === null) {
            return $this->removeAttribute($key);
        }

        $this->_attributes[$key] = $value;

        return $this;
    }

    public function getAttribute($key, $default=null) {
        $key = strtolower($key);

        if(isset($this->_attributes[$key])) {
            return $this->_attributes[$key];
        }

        if($key == 'style') {
            if(!$default instanceof IStyleCollection) {
                $default = new StyleCollection($default);
            }

            $this->_attributes[$key] = $default;
        } else if($key == 'class') {
            return $this->getClasses();
        }

        return $default;
    }

    public function removeAttribute($key) {
        $key = strtolower($key);

        if($key == 'class') {
            $this->_classes = [];
        } else {
            unset($this->_attributes[$key]);
        }

        return $this;
    }

    public function hasAttribute($key) {
        $key = strtolower($key);

        if($key == 'class') {
            return !empty($this->_classes);
        } else {
            return array_key_exists($key, $this->_attributes);
        }
    }

    public function countAttributes() {
        $output = count($this->_attributes);

        if(!empty($this->_classes)) {
            $output++;
        }

        return $output;
    }


// Data attributes
    public function addDataAttributes(array $attributes) {
        foreach($attributes as $key => $value) {
            $this->setDataAttribute($key, $value);
        }

        return $this;
    }

    public function setDataAttribute($key, $value) {
        $key = strtolower($key);

        /*
        if(preg_match('/[^a-zA-Z\:]/', $key)) {
            throw new InvalidArgumentException('Invalid data name '.$key.'!');
        }
        */

        $key = 'data-'.$key;
        $this->_attributes[$key] = $value;

        return $this;
    }

    public function getDataAttribute($key, $default=null) {
        $key = strtolower('data-'.$key);

        if(isset($this->_attributes[$key])) {
            return $this->_attributes[$key];
        }

        return $default;
    }

    public function hasDataAttribute($key) {
        return array_key_exists(strtolower('data-'.$key), $this->_attributes);
    }

    public function removeDataAttribute($key) {
        unset($this->_attributes[strtolower('data-'.$key)]);
        return $this;
    }

    public function getDataAttributes() {
        $output = [];

        foreach($this->_attributes as $key => $val) {
            if(substr($key, 0, 5) == 'data-') {
                $output[substr($key, 5)] = $val;
            }
        }

        return $output;
    }


// Classes
    public function setClasses($classes) {
        $this->_classes = array_unique($this->_normalizeClassList($classes));
        return $this;
    }

    public function addClasses($classes) {
        $this->_classes = array_unique(array_merge(
            $this->_classes, $this->_normalizeClassList($classes)
        ));

        return $this;
    }

    public function getClasses() {
        return $this->_classes;
    }

    public function setClass($class) {
        return $this->setClasses($class);
    }

    public function addClass($class) {
        return $this->addClasses($class);
    }

    public function removeClass($class) {
        foreach($this->_classes as $i => $value) {
            if($class == $value) {
                unset($this->_classes[$i]);
                break;
            }
        }

        return $this;
    }

    public function hasClass($class) {
        return in_array($class, $this->_classes);
    }

    public function countClasses() {
        return count($this->_classes);
    }

    protected function _normalizeClassList($classes) {
        if($classes === null) {
            return [];
        }

        if(!is_array($classes)) {
            $classes = explode(' ', $classes);
        }

        foreach($classes as &$class) {
            $class = $this->esc($class);
        }
        unset($class);

        return $classes;
    }


// Id
    public function setId($id) {
        if($id === null) {
            $this->removeAttribute('id');
            return $this;
        }

        if(preg_match('/[^a-zA-Z0-9\-_]/', $id)) {
            throw new InvalidArgumentException('Invalid tag id '.$id.'!');
        }

        $this->setAttribute('id', $id);
        return $this;
    }

    public function getId() {
        return $this->getAttribute('id');
    }

    public function isHidden($flag=null) {
        if($flag !== null) {
            if($flag) {
                $this->setAttribute('hidden', true);
            } else {
                $this->removeAttribute('hidden');
            }

            return $this;
        }

        return $this->hasAttribute('hidden');
    }

    public function setTitle($title) {
        return $this->setAttribute('title', $title);
    }

    public function getTitle() {
        return $this->getAttribute('title');
    }


// Style
    public function setStyles($styles) {
        $this->getAttribute('style')->clear()->import($styles);
        return $this;
    }

    public function addStyles($styles) {
        $this->getAttribute('style')->import($styles);
        return $this;
    }

    public function getStyles() {
        return $this->getAttribute('style');
    }

    public function setStyle($key, $value) {
        $this->getAttribute('style')->set($key, $value);
        return $this;
    }

    public function getStyle($key, $default=null) {
        return $this->getAttribute('style')->get($key, $default);
    }

    public function removeStyle($key) {
        $this->getAttribute('style')->remove($key);
        return $this;
    }

    public function hasStyle($key) {
        return $this->getAttribute('style')->has($key);
    }


// Strings
    public function open() {
        $attributes = [];

        foreach($this->_attributes as $key => $value) {
            if($value === null) {
                $attributes[] = $key;
            } else if(is_bool($value)) {
                if(substr($key, 0, 5) == 'data-' || in_array($key, self::$_booleanAttributes)) {
                    $attributes[] = $key.'="'.($value ? 'true' : 'false').'"';
                } else {
                    if($value) {
                        $attributes[] = $key;
                    } else {
                        continue;
                    }
                }
            } else if($value instanceof IElementRepresentation) {
                $attributes[] = $key.'="'.(string)$value.'"';
            } else {
                $attributes[] = $key.'="'.$this->esc($value).'"';
            }
        }

        if(!empty($this->_classes)) {
            $attributes[] = 'class="'.implode(' ', $this->_classes).'"';
        }

        if($attributes = implode(' ', $attributes)) {
            $attributes = ' '.$attributes;
        }

        $this->_renderCount++;

        $output = '<'.$this->_name.$attributes;

        if(!$this->_isClosable) {
            $output .= ' /';
        }

        $output .= '>';
        return $output;
    }

    public function close() {
        if(!$this->_isClosable) {
            return '';
        }

        return '</'.$this->_name.'>';
    }

    public function renderWith($innerContent=null, $expanded=false) {
        if($this->_isClosable && (!empty($innerContent) || $innerContent == '0')) {
            if(!$innerContent instanceof IElementContent) {
                $innerContent = new ElementContent($innerContent);
            }

            if($innerContent instanceof IElement && $innerContent !== $this) {
                $innerContent = $innerContent->render();
            } else {
                $innerContent = $innerContent->getElementContentString();
            }

            if(empty($innerContent) && !$this->_renderIfEmpty) {
                return null;
            }

            if($expanded) {
                $innerContent = "\n".$innerContent."\n";
            }
        } else if(!$this->_renderIfEmpty) {
            return null;
        } else {
            $innerContent = null;
        }

        $string = $this->open().$innerContent.$this->close();

        if($expanded) {
            $string .= "\n";
        }

        return new ElementString($string);
    }

    public function render() {
        return $this->renderWith();
    }

    public function toString() {
        return (string)$this->open();
    }

    public function shouldRenderIfEmpty($flag=null) {
        if($flag !== null) {
            $this->_renderIfEmpty = (bool)$flag;
            return $this;
        }

        return $this->_renderIfEmpty;
    }



// Dump
    public function getDumpProperties() {
        return (string)$this->render();
    }
}
