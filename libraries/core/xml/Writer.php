<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\xml;

use df;
use df\core;
    
class Writer implements IWriter {

    const ELEMENT = 1;
    const CDATA = 2;
    const COMMENT = 3;
    const PI = 4;

    use core\TStringProvider;
    use core\TAttributeContainer;
    use TRootInterchangeProvider;

    protected $_document;
    protected $_isMemory = true;
    protected $_headerWritten = false;
    protected $_dtdWritten = false;
    protected $_rootWritten = false;    

    protected $_elementContent = null;
    protected $_rawAttributeNames = array();
    protected $_currentNode = null;

    public static function fileFactory($path, IRootInterchange $interchange=null) {
        if(empty($path)) {
            throw new InvalidArgumentException(
                'Invalid XML writer path'
            );
        }

        return new self($interchange, $path);
    }

    public static function factory(IRootInterchange $interchange=null) {
        return new self($interchange);
    }

    protected function __construct(IRootInterchange $interchange=null, $path=null) {
        if($interchange) {
            $this->setRootInterchange($interchange);
        }

        $this->_document = new \XMLWriter();

        if($path !== null) {
            $this->_isMemory = false;
            $this->_document->openURI($path);
        } else {
            $this->_document->openMemory();
        }

        $this->_document->setIndent(true);
        $this->_document->setIndentString('    ');
    }


// Header
    public function writeHeader($version='1.0', $encoding='UTF-8', $isStandalone=false) {
        if($this->_headerWritten) {
            throw new LogicException('XML header has already been written');
        }

        if($this->_dtdWritten || $this->_rootWritten) {
            throw new LogicException('XML header cannot be written once the document is open');
        }

        try {
            $this->_document->startDocument($version, $encoding, $isStandalone ? true : null);
        } catch(\ErrorException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        $this->_headerWritten = true;

        return $this;
    }

    public function writeDtd($name, $publicId=null, $systemId=null, $subset=null) {
        if($this->_rootWritten) {
            throw new LogicException('XML DTD cannot be written once the document is open');
        }

        if(!$this->_headerWritten) {
            $this->writeHeader();
        }

        try {
            $this->_document->writeDtd($name, $publicId, $systemId, $subset);
        } catch(\ErrorException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        $this->_dtdWritten = true;

        return $this;
    }

    public function writeDtdAttlist($name, $content) {
        if($this->_rootWritten) {
            throw new LogicException('XML DTD cannot be written once the document is open');
        }

        if(!$this->_headerWritten) {
            $this->writeHeader();
        }

        try {
            $this->_document->writeDtdAttlist($name, $content);
        } catch(\ErrorException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        $this->_dtdWritten = true;

        return $this;
    }

    public function writeDtdElement($name, $content) {
        if($this->_rootWritten) {
            throw new LogicException('XML DTD cannot be written once the document is open');
        }

        if(!$this->_headerWritten) {
            $this->writeHeader();
        }

        try {
            $this->_document->writeDtdElement($name, $content);
        } catch(\ErrorException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        $this->_dtdWritten = true;

        return $this;
    }

    public function writeDtdEntity($name, $content, $pe, $publicId, $systemId, $nDataId) {
        if($this->_rootWritten) {
            throw new LogicException('XML DTD cannot be written once the document is open');
        }

        if(!$this->_headerWritten) {
            $this->writeHeader();
        }

        try {
            $this->_document->writeDtdEntity($name, $content, $pe, $publicId, $systemId, $nDataId);
        } catch(\ErrorException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        $this->_dtdWritten = true;

        return $this;
    }


// Element
    public function writeElement($name, $content=null, array $attributes=null) {
        $this->startElement($name);

        if($attributes !== null) {
            $this->setAttributes($attributes);
        }

        if($content !== null) {
            $this->setElementContent($content);
        }

        return $this->endElement();
    }

    public function startElement($name) {
        $this->_completeCurrentNode();
        $this->_document->startElement($name);
        $this->_currentNode = self::ELEMENT;
        $this->_rootWritten = true;

        return $this;
    }

    public function endElement() {
        if($this->_currentNode != self::ELEMENT) {
            throw new LogicException('XML writer is not currently writing an element');
        }

        $this->_completeCurrentNode();
        $this->_document->endElement();
        $this->_currentNode = self::ELEMENT;

        return $this;
    }

    public function setElementContent($content) {
        $this->_elementContent = $content;
        return $this;
    }

    public function getElementContent() {
        return $this->_elementContent;
    }


// Attributes
    public function setRawAttributeNames($attr) {
        if(!is_array($attr)) {
            $attr = func_get_args();
        }

        $this->_rawAttributeNames = $attr;
        return $this;
    }

    public function getRawAttributeNames() {
        return $this->_rawAttributeNames;
    }


// CData
    public function writeCData($content) {
        $this->startCData();
        $this->writeCDataContent($content);
        $this->endCData();

        return $this;
    }

    public function startCData() {
        $this->_completeCurrentNode();
        $this->_document->startCData();
        $this->_currentNode = self::CDATA;

        return $this;
    }

    public function writeCDataContent($content) {
        if($this->_currentNode != self::CDATA) {
            throw new LogicException('XML writer is not currently writing CData');
        }

        $this->_document->text($content);
        return $this;
    }

    public function endCData() {
        if($this->_currentNode != self::CDATA) {
            throw new LogicException('XML writer is not currently writing CData');
        }

        $this->_document->endCData();
        $this->_currentNode = self::ELEMENT;

        return $this;
    }


// Comment
    public function writeComment($comment) {
        $this->startComment();
        $this->writeCommentContent($comment);
        $this->endComment();

        return $this;
    }

    public function startComment() {
        $this->_completeCurrentNode();
        $this->_document->startComment();
        $this->_currentNode = self::COMMENT;

        return $this;
    }

    public function writeCommentContent($content) {
        if($this->_currentNode != self::COMMENT) {
            throw new LogicException('XML writer is not currently writing a comment');
        }

        $this->_document->text($content);
        return $this;
    }

    public function endComment() {
        if($this->_currentNode != self::COMMENT) {
            throw new LogicException('XML writer is not currently writing a comment');
        }

        $this->_document->endComment();
        $this->_currentNode = self::ELEMENT;
    }


// PI
    public function writeProcessingInstruction($target, $content) {
        $this->startProcessingInstruction($target);
        $this->writeProcessingInstructionContent($content);
        $this->endProcessingInstruction();

        return $this;
    }

    public function startProcessingInstruction($target) {
        $this->_completeCurrentNode();
        $this->_document->startPI($target);
        $this->_currentNode = self::PI;

        return $this;
    }

    public function writeProcessingInstructionContent($content) {
        if($this->_currentNode != self::PI) {
            throw new LogicException('XML writer is not currently writing a processing instruction');
        }

        $this->_document->text($content);
        return $this;
    }

    public function endProcessingInstruction() {
        if($this->_currentNode != self::PI) {
            throw new LogicException('XML writer is not currently writing a processing instruction');
        }

        $this->_document->endPI();
        $this->_currentNode = self::ELEMENT;

        return $this;
    }


// Raw
    public function writeRaw($content) {
        $this->_document->writeRaw($content);
        return $this;
    }

// Misc
    protected function _completeCurrentNode() {
        switch($this->_currentNode) {
            case self::ELEMENT:
                foreach($this->_attributes as $key => $value) {
                    if(in_array($key, $this->_rawAttributeNames)) {
                        $this->_document->startAttribute($key);
                        $this->_document->writeRaw($value);
                        $this->_document->endAttribute();
                    } else {
                        $this->_document->writeAttribute($key, $value);
                    }
                }

                $this->_attributes = array();
                $this->_rawAttributeNames = array();

                if($this->_elementContent !== null) {
                    $this->_document->text($this->_elementContent);
                    $this->_elementContent = null;
                }

                break;

            case self::CDATA:
                $this->endCData();
                break;

            case self::COMMENT:
                $this->endComment();
                break;

            case self::PI:
                $this->endProcessingInstruction();
                break;
        }
    }

    public function finalize() {
        $this->_completeCurrentNode();

        if($this->_headerWritten) {
            $this->_document->endDocument();
        }

        return $this;
    }

    public function toString() {
        return $this->toXmlString();
    }

    public function toXmlString($embedded=false) {
        if($embedded) {
            // TODO: ensure embedded xml
        }

        if($this->_isMemory) {
            return $this->_document->outputMemory();
        }

        core\stub();
    }
}