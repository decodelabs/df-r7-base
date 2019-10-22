<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\xml;

use df;
use df\core;
use df\flex;

use DecodeLabs\Glitch;

// Exceptions
interface IException
{
}
class RuntimeException extends \RuntimeException implements IException
{
}
class LogicException extends \LogicException implements IException
{
}
class InvalidArgumentException extends \InvalidArgumentException implements IException
{
}
class OutOfBoundsException extends \OutOfBoundsException implements IException
{
}


// Interfaces
interface IInterchange
{
    //public function readXml(ITree $reader);
    //public function writeXml(IWriter $writer);
}

interface IReaderInterchange
{
    public static function fromXmlFile($xmlFile);
    public static function fromXmlString($xmlString);
    public static function fromXmlElement(ITree $element);
}

trait TReaderInterchange
{
    public static function fromXml($xml)
    {
        if ($xml instanceof ITree) {
            return static::fromXmlElement($xml);
        } else {
            return static::fromXmlString($xml);
        }
    }

    public static function fromXmlFile($xmlFile)
    {
        $reader = Tree::fromXmlFile($xmlFile);
        return static::fromXmlElement($reader);
    }

    public static function fromXmlString($xmlString)
    {
        $reader = Tree::fromXmlString($xmlString);
        return static::fromXmlElement($reader);
    }

    public static function fromXmlElement(ITree $element)
    {
        $class = get_called_class();
        $ref = new \ReflectionClass($class);

        if ($ref->isAbstract()) {
            throw Glitch::ELogic('Xml reader interchange cannot be instantiated');
        }

        $output = new $class();
        $output->readXml($element);

        return $output;
    }
}


interface IWriterInterchange
{
    public function toXmlString($embedded=false);
}

trait TWriterInterchange
{
    public function toXmlString($embedded=false)
    {
        $writer = Writer::factory($this);

        if (!$embedded) {
            $writer->writeHeader();
            $this->_writeXmlDtd($writer);
        }

        if (method_exists($this, 'writeXml')) {
            $this->writeXml($writer);
        }

        $writer->finalize();
        return $writer->toXmlString();
    }

    protected function _writeXmlDtd(IWritable $writer)
    {
    }
}

interface IRootInterchange extends IInterchange, IReaderInterchange, IWriterInterchange
{
}

interface IRootInterchangeProvider
{
    public function setRootInterchange(IRootInterchange $root=null);
    public function getRootInterchange();
}

trait TRootInterchangeProvider
{
    protected $_rootInterchange;

    public function setRootInterchange(IRootInterchange $root=null)
    {
        $this->_rootInterchange = $root;
        return $this;
    }

    public function getRootInterchange()
    {
        return $this->_rootInterchange;
    }
}


interface IReadable extends IReaderInterchange, IRootInterchangeProvider
{
}

interface IWritable extends IWriterInterchange, IRootInterchangeProvider
{
}



// Tree
interface ITree extends IReadable, IWritable, core\collection\IAttributeContainer, \Countable, \ArrayAccess, core\IStringProvider
{
    // Node info
    public function setTagName($name);
    public function getTagName();

    // Attributes
    public function countAttributes();
    public function getBooleanAttribute($name, $default=null);

    // Content
    public function setInnerXml($string);
    public function getInnerXml();
    public function getComposedInnerXml();

    public function setTextContent($content);
    public function getTextContent();
    public function getTextContentOf(string $name): ?string;
    public function getComposedTextContent();
    public function getComposedTextContentOf(string $name): ?string;

    public function setCDataContent($content);
    public function prependCDataContent($content);
    public function appendCDataContent($content);
    public function getFirstCDataSection();
    public function getAllCDataSections();

    // Child access
    public function count();
    public function countType($name);
    public function hasChildren();
    public function __get($name);

    public function getChildren();
    public function scanChildren(): \Generator;
    public function getFirstChild();
    public function getLastChild();
    public function getNthChild($index);
    public function getNthChildren($formula);

    public function getChildrenOfType($name);
    public function scanChildrenOfType($name): \Generator;
    public function getFirstChildOfType($name);
    public function getLastChildOfType($name);
    public function getNthChildOfType($name, $index);
    public function getNthChildrenOfType($name, $formula);

    public function getChildTextContent($name);

    // Child construction
    public function prependChild($child, $value=null);
    public function appendChild($child, $value=null);
    public function replaceChild($origChild, $child, $value=null);
    public function putChild($index, $child, $value=null);
    public function insertChildBefore($origChild, $child, $value=null);
    public function insertChildAfter($origChild, $child, $value=null);
    public function removeChild($child);
    public function removeAllChildren();

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
    public function isDocumentStandalone(bool $flag=null);
    public function normalizeDocument();

    // Conversion
    public function getDOMDocument();
    public function getDOMElement();

    public function toNodeXmlString();
}


// Writer
interface IWriter extends IWritable, core\collection\IAttributeContainer, core\IStringProvider
{
    // Header
    public function writeHeader($version='1.0', $encoding='UTF-8', $isStandalone=false);
    public function writeDtd($name, $publicId=null, $systemId=null, $subset=null);
    public function writeDtdAttlist($name, $content);
    public function writeDtdElement($name, $content);
    public function writeDtdEntity($name, $content, $pe, $publicId, $systemId, $nDataId);

    // Element
    public function writeElement($name, $content=null, array $attributes=null);
    public function startElement($name);
    public function endElement();
    public function setElementContent($content);
    public function getElementContent();

    // CData
    public function writeCData($content);
    public function writeCDataElement($name, $content, array $attributes=null);
    public function startCData();
    public function writeCDataContent($content);
    public function endCData();

    // Comment
    public function writeComment($comment);
    public function startComment();
    public function writeCommentContent($content);
    public function endComment();

    // PI
    public function writeProcessingInstruction($target, $content);
    public function startProcessingInstruction($target);
    public function writeProcessingInstructionContent($content);
    public function endProcessingInstruction();

    // Attributes
    public function setRawAttributeNames(...$names);
    public function getRawAttributeNames();

    // Raw
    public function writeRaw($content);

    // Misc
    public function finalize();
    public function toTree();
    public function importTreeNode(ITree $tree);
}
