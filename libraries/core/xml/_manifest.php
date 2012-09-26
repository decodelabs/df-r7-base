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
interface ITree extends IReadable, IWritable, core\IAttributeContainer, \Countable, core\IStringProvider {
	// Node info
	public function setTagName($name);
	public function getTagName();

	// Attributes
	public function countAttributes();

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

	// Global access
	public function getById($id);
	public function getByType($type);
	public function getByAttribute($name, $value=null);
	
	public function xPath($path);
	public function xPathFirst($path);

	// Sibling access
	public function getParent();
	public function countSiblings();
	public function hasSiblings();

	public function getPreviousSibling();
	public function getNextSibling();

	// Comments
	public function getPrecedingComment();
	public function getAllComments();

	// Document options
	public function setXmlVersion($version);
	public function getXmlVersion();
	public function setDocumentEncoding($encoding);
	public function getDocumentEncoding();
	public function isDocumentStandalone($flag=null);
	public function normalizeDocument();
	public function getDOMDocument();
}

// Reader
interface IReader extends IReadable {

}

// Writer
interface IWriter extends IWritable {

}