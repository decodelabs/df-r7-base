<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\xml;

use df;
use df\core;
    
class Tree implements ITree, core\IDumpable {

    use core\TStringProvider;
    use core\TAttributeContainerArrayAccessProxy;
    use TRootInterchangeProvider;

    protected $_element;

    public static function fromXmlFile($xmlFile) {
        try {
            $document = self::_newDOMDocument();
            $document->load($file);
        } catch(\Exception $e) {
            throw new RuntimeException(
                'XML file '.$file.' could not be loaded: '.$e->getMessage()
            );
        }

        return self::fromDOMDocument($document);
    }

    public static function fromXmlString($xmlString) {
        try {
            $document = self::_newDOMDocument();
            $document->loadXML($xmlString);
        } catch(\Exception $e) {
            throw new RuntimeException(
                'XML string could not be loaded: '.$e->getMessage()
            );
        }

        return self::fromDOMDocument($document);
    }

    public static function fromXmlElement(ITree $element) {
        return $element;
    }

    public static function fromDOMDocument($document) {
        if(!$document instanceof \DOMDocument) {
            throw new InvalidArgumentException(
                'DOMDocument was not loaded'
            );
        }

        $document->formatOutput = true;

        return new self($document->documentElement);
    }

    private static function _newDOMDocument() {
        $output = new \DOMDocument();
        $output->formatOutput = true;
        //$output->preserveWhiteSpace = false;
        return $output;
    }

    protected function __construct(\DOMElement $element) {
        $this->_element = $element;
    }


// Node info
    public function setTagName($name) {
        $newNode = $this->_element->ownerDocument->createElement($name);
        $children = array();

        foreach($this->_element->childNodes as $child) {
            $children[] = $this->_element->ownerDocument->importNode($child, true);
        }

        foreach($children as $child) {
            $newNode->appendChild($child);
        }

        foreach($this->_element->attributes as $attrNode) {
            $child = $this->_element->ownerDocument->importNode($attrNode, true);
            $newNode->setAttributeNode($attrNode);
        }

        $this->_element->ownerDocument->replaceChild($newNode, $this->_element);
        $this->_element = $newNode;

        return $this;
    }

    public function getTagName() {
        return $this->_element->nodeName;
    }

// Attributes
    public function setAttributes(array $attributes) {
        foreach($this->_element->attributes as $attrNode) {
            $this->_element->removeAttributeNode($attrNode);
        }

        return $this->addAttributes($attributes);
    }

    public function addAttributes(array $attributes) {
        foreach($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    public function setAttribute($key, $value) {
        $this->_element->setAttribute($key, $value);
        return $this;
    }

    public function getAttributes() {
        $output = array();

        foreach($this->_element->attributes as $attrNode) {
            $output[$attrNode->name] = $attrNode->value;
        }

        return $output;
    }

    public function getAttribute($key, $default=null) {
        $output = $this->_element->getAttribute($key);

        if(empty($output) && $output !== '0') {
            return $default;
        }

        return $output;
    }

    public function removeAttribute($key) {
        $this->_element->removeAttribute($key);
        return $this;
    }

    public function hasAttribute($key) {
        return $this->_element->hasAttribute($key);
    }

    public function countAttributes() {
        return $this->_element->attributes->length;
    }


// Content
    public function setInnerXml($string) {
        $this->removeAllChildren();

        $fragment = $this->_element->ownerDocument->createDocumentFragment();
        $fragment->appendXml($string);
        $this->_element->appendChild($fragment);

        return $this;
    }

    public function getInnerXml() {
        $output = ''; 

        foreach($this->_element->childNodes as $child) { 
            $output .= $this->_element->ownerDocument->saveXML($child);
        } 

        return $output; 
    }

    public function getComposedInnerXml() {
        $output = $this->getInnerXml();
        $output = preg_replace('/  +/', ' ', $output);
        $output = str_replace(["\r", "\n\n", "\n "], ["\n", "\n", "\n"], $output);
        return trim($output);
    }

    public function setTextContent($text) {
        $this->removeAllChildren();

        $text = $this->_element->ownerDocument->createTextNode($text);
        $this->_element->appendChild($text);

        return $this;
    }

    public function getTextContent() {
        return $this->_element->textContent;
    }

    public function getComposedTextContent() {
        $isRoot = $this->_element === $this->_element->ownerDocument->documentElement;
        $output = '';

        foreach($this->_element->childNodes as $node) {
            $value = null;

            switch($node->nodeType) {
                case \XML_ELEMENT_NODE:
                    $value = (new self($node))->getComposedTextContent();

                    if($isRoot) {
                        $value .= "\n";
                    }

                    break;

                case \XML_TEXT_NODE:
                    $value = ltrim($node->nodeValue);

                    if($value != $node->nodeValue) {
                        $value = ' '.$value;
                    }

                    $t = rtrim($value);

                    if($t != $value) {
                        $value = $t.' ';
                    }

                    break;

                case \XML_CDATA_SECTION_NODE:
                    if($value) {
                        $value .= "\n";
                    }

                    $value .= trim($node->nodeValue)."\n";
                    break;
            }

            if(!empty($value)) {
                $output .= $value;
            }
        }

        return trim(str_replace(['  ', "\n "], [' ', "\n"], $output));
    }


    public function setCDataContent($content) {
        $this->removeAllChildren();

        $content = $this->_element->ownerDocument->createCDataSection($content);
        $this->_element->appendChild($content);

        return $this;
    }

    public function prependCDataContent($content) {
        $content = $this->_element->ownerDocument->createCDataSection($content);
        $this->_element->insertBefore($content, $this->_element->firstChild);

        return $this;
    }

    public function appendCDataContent($content) {
        $content = $this->_element->ownerDocument->createCDataSection($content);
        $this->_element->appendChild($content);

        return $this;
    }

    public function getAllCDataSections() {
        $output = array();

        foreach($this->_element->childNodes as $node) {
            if($node->nodeType == \XML_CDATA_SECTION_NODE) {
                $output[] = $node->nodeValue;
            }
        }

        return $output;
    }


// Child access
    public function count() {
        $output = 0;

        foreach($this->_element->childNodes as $node) {
            if($node->nodeType == \XML_ELEMENT_NODE) {
                $output++;
            }
        }

        return $output;
    }

    public function countType($name) {
        $output = 0;

        foreach($this->_element->childNodes as $node) {
            if($node->nodeType == \XML_ELEMENT_NODE
            && $node->nodeName == $name) {
                $output++;
            }
        }

        return $output;
    }

    public function hasChildren() {
        if(!$this->_element->childNodes->length) {
            return false;
        }

        foreach($this->_element->childNodes as $node) {
            if($node->nodeType == \XML_ELEMENT_NODE) {
                return true;
            }
        }

        return false;
    }

    public function __get($name) {
        return $this->_getChildren($name);
    }

    public function getChildren() {
        return $this->_getChildren();
    }

    public function getFirstChild() {
        return $this->_getFirstChild();
    }

    public function getLastChild() {
        return $this->_getLastChild();
    }

    public function getNthChild($index) {
        return $this->_getNthChild($index);
    }

    public function getNthChildren($formula) {
        return $this->_getNthChildren($formula);
    }


    public function getChildrenOfType($name) {
        return $this->_getChildren($name);
    }

    public function getFirstChildOfType($name) {
        return $this->_getFirstChild($name);
    }

    public function getLastChildOfType($name) {
        return $this->_getLastChild($name);
    }

    public function getNthChildOfType($name, $index) {
        return $this->_getNthChild($index, $name);
    }

    public function getNthChildrenOfType($name, $formula) {
        return $this->_getNthChildren($formula, $name);
    }

    protected function _getChildren($name=null) {
        $output = array();

        foreach($this->_element->childNodes as $node) {
            if($node->nodeType == \XML_ELEMENT_NODE) {
                if($name !== null && $node->nodeName != $name) {
                    continue;
                }

                $output[] = new self($node);
            }
        }

        return $output;
    }

    protected function _getFirstChild($name=null) {
        foreach($this->_element->childNodes as $node) {
            if($node->nodeType == \XML_ELEMENT_NODE) {
                if($name !== null && $node->nodeName != $name) {
                    continue;
                }

                return new self($node);
            }
        }
    }

    protected function _getLastChild($name=null) {
        $lastElement = null;

        foreach($this->_element->childNodes as $node) {
            if($node->nodeType == \XML_ELEMENT_NODE) {
                if($name !== null && $node->nodeName != $name) {
                    continue;
                }

                $lastElement = $node;
            }
        }

        return new self($lastElement);
    }

    protected function _getNthChild($index, $name=null) {
        $index = (int)$index;

        if($index < 1) {
            throw new InvalidArgumentException(
                $index.' is an invalid child index'
            );
        }

        foreach($this->_element->childNodes as $node) {
            if($node->nodeType == \XML_ELEMENT_NODE) {
                if($name !== null && $node->nodeName != $name) {
                    continue;
                }

                $index--;

                if($index == 0) {
                    return new self($node);
                }
            }
        }
    }

    protected function _getNthChildren($formula, $name=null) {
        if(is_numeric($formula)) {
            if($output = $this->_getNthChild($formula, $name)) {
                return [$output];
            }
        }

        $formula = strtolower($formula);

        if($formula == 'even') {
            $formula = '2n';
        } else if($formula == 'odd') {
            $formula = '2n+1';
        }

        if(!preg_match('/^([\-]?)([0-9]*)[n]([+]([0-9]+))?$/i', str_replace(' ', '', $formula), $matches)) {
            throw new InvalidArgumentException(
                $formula.' is not a valid nth-child formula'
            );    
        }

        $mod = (int)$matches[2];
        $offset = isset($matches[4]) ? (int)$matches[4] : 0;

        if($matches[1] == '-') {
            $mod *= -1;
        }

        $output = array();
        $i = 0;

        foreach($this->_element->childNodes as $node) {
            if($node->nodeType == \XML_ELEMENT_NODE) {
                if($name !== null && $node->nodeName != $name) {
                    continue;
                }

                $i++;

                if($i % $mod == $offset) {
                    $output[] = new self($node);
                }
            }
        }

        return $output;
    }



// Child construction
    public function prependChild($child, $value=null) {
        $node = $this->_normalizeInputChild($child, $value);
        $node = $this->_element->insertBefore($node, $this->_element->firstChild);

        return new self($node);
    }

    public function appendChild($child, $value=null) {
        $node = $this->_normalizeInputChild($child, $value);
        $this->_element->appendChild($node);

        return new self($node);
    }

    public function replaceChild($origChild, $child, $value=null) {
        if($origChild instanceof ITree) {
            $origChild = $origChild->getDOMElement();
        }

        if(!$origChild instanceof \DOMElement) {
            throw new InvalidArgumentException(
                'Original child is not a valid element'
            );
        }

        $node = $this->_normalizeInputChild($child, $value);
        $this->_element->replaceChild($node, $origChild);

        return new self($node);
    }

    public function putChild($index, $child, $value=null) {
        $newNode = $this->_normalizeInputChild($child, $value);
        $index = $origIndex = (int)$index;
        $count = $this->count();
        $i = 0;

        if($index < 0) {
            $index += $count;
        }

        if($index < 0) {
            throw new OutOfBoundsException(
                'Index '.$origIndex.' is out of bounds'
            );
        }

        if($index == 0) {
            $newNode = $this->_element->insertBefore($newNode, $this->_element->firstChild);
        } else if($index >= $count) {
            $newNode = $this->_element->appendChild($newNode);            
        } else {
            foreach($this->_element->childNodes as $node) {
                if(!$node->nodeType == \XML_ELEMENT_NODE) {
                    continue;
                }

                if($i >= $index + 1) {
                    $newNode = $this->_element->insertBefore($newNode, $node);
                    break;
                }

                $i++;
            }
        }

        return new self($newNode);
    }

    public function insertChildBefore($origChild, $child, $value=null) {
        if($origChild instanceof ITree) {
            $origChild = $origChild->getDOMElement();
        }

        if(!$origChild instanceof \DOMElement) {
            throw new InvalidArgumentException(
                'Original child is not a valid element'
            );
        }

        $node = $this->_normalizeInputChild($child, $value);
        $this->_element->insertBefore($node, $origChild);

        return new self($node);
    }

    public function insertChildAfter($origChild, $child, $value=null) {
        if($origChild instanceof ITree) {
            $origChild = $origChild->getDOMElement();
        }

        if(!$origChild instanceof \DOMElement) {
            throw new InvalidArgumentException(
                'Original child is not a valid element'
            );
        }

        do {
            $origChild = $origChild->nextSibling;
        } while($origChild && $origChild->nodeType != \XML_ELEMENT_NODE);

        $node = $this->_normalizeInputChild($child, $value);

        if(!$origChild) {
            $this->_element->appendChild($node);
        } else {
            $this->_element->insertBefore($node, $origChild);
        }

        return new self($node);
    }

    public function removeChild($child) {
        if(is_numeric($child)) {
            $child = $this->getNthChild($child);
        }

        if($child instanceof ITree) {
            $child = $child->getDOMElement();
        }

        if(!$child instanceof \DOMElement) {
            throw new InvalidArgumentException(
                'Original child is not a valid element'
            );
        }

        $this->_element->removeChild($child);
        return $this;
    }

    public function removeAllChildren() {
        $queue = array();

        foreach($this->_element->childNodes as $node) {
            $queue[] = $node;
        }

        foreach($queue as $node) {
            $this->_element->removeChild($node);
        }

        return $this;
    }


// Sibling access
    public function getParent() {
        if($this->_element->parentNode) {
            return new self($this->_element->parentNode);
        }
    }

    public function countSiblings() {
        if(!$this->_element->parentNode) {
            return 0;
        }

        $output = -1;

        foreach($this->_element->parentNode->childNodes as $node) {
            if($node->nodeType == \XML_ELEMENT_NODE) {
                $output++;
            }
        }


        if($output < 0) {
            $output = 0;
        }

        return $output;
    }

    public function hasSiblings() {
        if(!$this->_element->parentNode) {
            return true;
        }

        if(!$this->_element->previousSibling && !$this->_element->nextSibling) {
            return true;
        }

        foreach($this->_element->parentNode->childNodes as $node) {
            if($node === $this->_element) {
                continue;
            }

            if($node->nodeType == \XML_ELEMENT_NODE) {
                return false;
            }
        }

        return true;
    }

    public function getPreviousSibling() {
        $node = $this->_element->previousSibling;

        while($node && $node->nodeType != \XML_ELEMENT_NODE) {
            if(!$node = $node->previousSibling) {
                return null;
            }
        }

        if($node instanceof \DOMElement) {
            return new self($node);
        }
    }

    public function getNextSibling() {
        $node = $this->_element->nextSibling;

        while($node && $node->nodeType != \XML_ELEMENT_NODE) {
            if(!$node = $node->nextSibling) {
                return null;
            }
        }

        if($node instanceof \DOMElement) {
            return new self($node);
        }
    }


// Sibling construction
    public function insertBefore($sibling, $value=null) {
        $node = $this->_normalizeInputChild($sibling, $value);
        $node = $this->_element->parentNode->insertBefore($node, $this->_element);

        return new self($node);
    }

    public function insertAfter($sibling, $value=null) {
        $node = $this->_normalizeInputChild($sibling, $value);

        $target = $this->_element;

        do {
            $target = $target->nextSibling;
        } while($target && $target->nodeType != \XML_ELEMENT_NODE);

        if(!$target) {
            $node = $this->_element->parentNode->appendChild($node);
        } else {
            $node = $this->_element->parentNode->insertBefore($node, $target);
        }

        return new self($node);
    }

    public function replaceWith($sibling, $value=null) {
        $node = $this->_normalizeInputChild($sibling, $value);
        $this->_element->parentNode->replaceChild($node, $this->_element);
        $this->_element = $node;

        return $this;
    }


// Comments
    public function getPrecedingComment() {
        if($this->_element->previousSibling 
        && $this->_element->previousSibling->nodeType == \XML_COMMENT_NODE) {
            return trim($this->_element->previousSibling->data);
        }
    }

    public function getAllComments() {
        $output = array();

        foreach($this->_element->childNodes as $node) {
            if($node->nodeType == \XML_COMMENT_NODE) {
                $output[] = trim($node->data);
            }
        }

        return $output;
    }



// Global access
    public function getById($id) {
        return $this->xPathFirst('//*[@id=\''.$id.'\']');
    }

    public function getByType($type) {
        $output = array();

        foreach($this->_element->ownerDocument->getElementsByTagName($type) as $node) {
            $output[] = new self($node);
        }

        return $output;
    }

    public function getByAttribute($name, $value=null) {
        if(empty($value) && $value !== '0') {
            $path = '//*[@'.$name.']';
        } else {
            $path = '//*[@'.$name.'=\''.$value.'\']';
        }

        return $this->xPath($path);
    }

    public function xPath($path) {
        $xpath = new \DOMXPath($this->_element->ownerDocument);
        $output = array();

        foreach($xpath->query($path) as $node) {
            $output[] = new self($node);
        }

        return $output;
    }

    public function xPathFirst($path) {
        $xpath = new \DOMXPath($this->_element->ownerDocument);
        $output = $xpath->query($path)->item(0);

        if($output) {
            return new self($output);
        }
    }



// Xml options
    public function setXmlVersion($version) {
        $this->_element->ownerDocument->xmlVersion = $version;
        return $this;
    }

    public function getXmlVersion() {
        return $this->_element->ownerDocument->xmlVersion;
    }

    public function setDocumentEncoding($encoding) {
        $this->_element->ownerDocument->xmlEncoding = $encoding;
        return $this;
    }

    public function getDocumentEncoding() {
        return $this->_element->ownerDocument->xmlEncoding;
    }

    public function isDocumentStandalone($flag=null) {
        if($flag !== null) {
            $this->_element->ownerDocument->xmlStandalone = (bool)$flag;
            return $this;
        }

        return (bool)$this->_element->ownerDocument->xmlStandalone;
    }
    
    public function normalizeDocument() {
        $this->_element->ownerDocument->normalizeDocument();
        return $this;
    }


// Conversion
    public function getDOMDocument() {
        return $this->_element->ownerDocument;
    }

    public function getDOMElement() {
        return $this->_element;
    }

    protected function _normalizeInputChild($child, $value=null) {
        $node = null;

        if($child instanceof ITree) {
            $node = $child->getDOMElement();
        }

        if($node instanceof \DOMElement) {
            $node = $this->_element->ownerDocument->importNode($node, true);
        } else {
            $node = $this->_element->ownerDocument->createElement((string)$child, $value);
        }

        return $node;
    }

// Output
    public function toString() {
        return $this->getComposedTextContent();
    }

    public function toXmlString($embedded=false) {
        if($embedded) {
            // TODO: return embedded xml
            core\stub('Strip header from output xml');
        }

        return $this->_element->ownerDocument->saveXML();
    }

    public function toNodeXmlString() {
        return $this->_element->ownerDocument->saveXML($this->_element);
    }

// Dump
    public function getDumpProperties() {
        return $this->toNodeXmlString();
    }
}