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

	protected $_element;
	protected $_rootInterchange;

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
		$output->preserveWhiteSpace = false;
		return $output;
	}

	protected function __construct(\DOMElement $element) {
		$this->_element = $element;
	}

// Root interchange
	public function setRootInterchange(IRootInterchange $root=null) {
		$this->_rootInterchange = $root;
		return $this;
	}

	public function getRootInterchange() {
		return $this->_rootInterchange;
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

	public function getDOMDocument() {
		return $this->_element->ownerDocument;
	}

// Output
	public function toString() {
		return $this->_element->ownerDocument->saveXML($this->_element);
	}

	public function toXmlString() {
		return $this->_element->ownerDocument->saveXML();
	}

// Dump
	public function getDumpProperties() {
		return $this->toString();
	}
}