<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\xml;

use df;
use df\core;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class OutOfBoundsException extends \OutOfBoundsException implements IException {}


// Interfaces
interface IInterchange {
	public function readXml(IReadable $reader);
	public function writeXml(IWritable $writer);
}

interface IReaderInterchange {
	public static function fromXmlFile($xmlFile);	
	public static function fromXmlString($xmlString);
}

interface IWriterInterchange {
	public function toXmlString();
}

interface IRootInterchange extends IInterchange, IReaderInterchange, IWriterInterchange {
	
}

interface IRootInterchangeProvider {
	public function setRootInterchange(IRootInterchange $root=null);
	public function getRootInterchange();
}

interface IReadable extends IReaderInterchange, IRootInterchangeProvider {

}

interface IWritable extends IWriterInterchange, IRootInterchangeProvider {

}



// Tree
interface ITree extends IReadable, IWritable, core\IAttributeContainer, \Countable, \ArrayAccess, core\IStringProvider {
	// Node info
	public function setTagName($name);
	public function getTagName();

	// Attributes
	public function countAttributes();

	// Content
	public function getTextContent();
	public function getComposedTextContent();

	// Child access
	public function count();
	public function countType($name);
	public function hasChildren();

	public function getChildren();
	public function getFirstChild();
	public function getLastChild();
	public function getNthChild($index);
	public function getNthChildren($formula);

	public function getChildrenOfType($name);
	public function getFirstChildOfType($name);
	public function getLastChildOfType($name);
	public function getNthChildOfType($name, $index);
	public function getNthChildrenOfType($name, $formula);

	// Child construction
	public function prependChild($child, $value=null);
	public function appendChild($child, $value=null);
	public function replaceChild($origChild, $child, $value=null);
	public function putChild($index, $child, $value=null);
	public function insertChildBefore($origChild, $child, $value=null);
	public function insertChildAfter($origChild, $child, $value=null);
	public function removeChild($child);

	// Sibling access
	public function getParent();
	public function countSiblings();
	public function hasSiblings();

	public function getPreviousSibling();
	public function getNextSibling();

	// Sibling construction
	public function insertBefore($sibling, $value=null);
	public function insertAfter($sibling, $value=null);
	public function replaceWith($sibling, $value=null);

	// Comments
	public function getPrecedingComment();
	public function getAllComments();

	// Global access
	public function getById($id);
	public function getByType($type);
	public function getByAttribute($name, $value=null);
	
	public function xPath($path);
	public function xPathFirst($path);

	// Document options
	public function setXmlVersion($version);
	public function getXmlVersion();
	public function setDocumentEncoding($encoding);
	public function getDocumentEncoding();
	public function isDocumentStandalone($flag=null);
	public function normalizeDocument();

	// Conversion
	public function getDOMDocument();
	public function getDOMElement();

	public function toNodeXmlString();
}

// Reader
interface IReader extends IReadable {

}

// Writer
interface IWriter extends IWritable {

}