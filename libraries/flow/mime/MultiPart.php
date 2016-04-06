<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mime;

use df;
use df\core;
use df\flow;

class MultiPart implements IMultiPart, core\IDumpable {

    use core\TStringProvider;
    use core\collection\THeaderMapProvider;

    private static $_boundaryCounter = 0;

    protected $_parts = [];

    public static function fromString($string) {
        $class = get_called_class();
        return self::_createPartFromString($string, $class);
    }

    protected static function _createPartFromString($string, $class=null) {
        if($class === null) {
            $class = __CLASS__;
        }

        $string = str_replace("\r", '', $string);
        list($headers, $body) = explode("\n\n", $string, 2);

        $headers = new core\collection\HeaderMap($headers);
        $contentType = $headers->get('content-type');

        if(substr($contentType, 0, 10) == 'multipart/') {
            $output = new $class($contentType, $headers);
            $boundary = $output->getBoundary();
            $parts = explode("\n".'--'.$boundary, "\n".trim($body));
            array_shift($parts);
            array_pop($parts);

            foreach($parts as $part) {
                $output->addPart(self::_createPartFromString($part));
            }
        } else {
            $output = new ContentPart($body, $headers, true);
        }

        return $output;
    }

    public function __construct($type=IMultiPart::MIXED, $headers=null) {
        $this->_headers = core\collection\HeaderMap::factory($headers);

        $this->setContentType($type);

        if(!$this->_headers->hasDelimitedValue('content-type', 'boundary')) {
            $this->_headers->setDelimitedValue('content-type', 'boundary', md5(microtime(true).self::$_boundaryCounter++));
        }
    }


    public function isMultiPart() {
        return ($this->count() > 1) || (isset($this->_parts[0]) && $this->_parts[0]->isMultipart());
    }

    public function setContentType($type) {
        $parts = explode(';', $type, 2);
        $type = strtolower(array_shift($parts));
        $suffix = array_pop($parts);

        switch($type) {
            case IMultiPart::ALTERNATIVE:
            case IMultiPart::MIXED:
            case IMultiPart::RELATED:
            case IMultiPart::PARALLEL:
            case IMultiPart::DIGEST:
                if($suffix) {
                    $type .= ';'.$suffix;
                }

                $this->_headers->set('content-type', $type);
                break;

            default:
                throw new InvalidArgumentException(
                    'Invalid multi part type '.$type
                );
        }

        return $this;
    }

    public function getContentType() {
        return $this->_headers->getBase('content-type');
    }

    public function getFullContentType() {
        return $this->_headers->get('content-type');
    }

    public function setBoundary($boundary) {
        $this->_headers->setDelimitedValue('content-type', 'boundary', $boundary);
        return $this;
    }

    public function getBoundary() {
        return $this->_headers->getDelimitedValue('content-type', 'boundary');
    }

    public function setParts(array $parts) {
        $this->clearParts();
        return $this->addParts($parts);
    }

    public function addParts(array $parts) {
        foreach($parts as $part) {
            if(!$part instanceof IPart) {
                $part = $this->newContentPart($part);
            }

            $this->addPart($part);
        }

        return $this;
    }

    public function addPart(IPart $part) {
        $this->_parts[] = $part;
        return $this;
    }

    public function prependPart(IPart $part) {
        array_unshift($this->_parts, $part);
        return $this;
    }

    public function getParts() {
        return $this->_parts;
    }

    public function getPart($index) {
        if(isset($this->_parts[(int)$index])) {
            return $this->_parts[(int)$index];
        }
    }

    public function clearParts() {
        $this->_parts = [];
        return $this;
    }

    public function isEmpty() {
        return !empty($this->_parts);
    }

    public function newContentPart($content) {
        $output = new ContentPart($content);
        $this->addPart($output);
        return $output;
    }

    public function newMultiPart($type=IMultiPart::MIXED) {
        $output = new MultiPart($type);
        $this->addPart($output);
        return $output;
    }

    public function toString() {
        $output = $this->getHeaderString().IPart::LINE_END.IPart::LINE_END;
        $output .= $this->getBodyString();

        return $output;
    }

    public function getHeaderString(array $skipKeys=null) {
        $this->prepareHeaders();

        if($this->isMultiPart()) {
            $headers = $this->_headers;
        } else if(isset($this->_parts[0])) {
            $headers = new core\collection\HeaderMap($this->mergeSinglePartHeaders());
        }

        $output = $headers->toString($skipKeys);
        return $output;
    }

    public function mergeSinglePartHeaders() {
        $output = $this->_headers->toArray();

        if(isset($this->_parts[0])) {
            $output = array_merge($output, $this->_parts[0]->mergeSinglePartHeaders());
        }

        return $output;
    }

    public function getBodyString() {
        $lineEnd = IPart::LINE_END;
        $output = '';

        if($this->isMultiPart()) {
            $boundary = $this->getBoundary();

            foreach($this->_parts as $part) {
                $output .= '--'.$boundary.$lineEnd;

                //if($part->isMessage()) {
                //    $output .= 'Content-Type: message/rfc822'.$lineEnd.$lineEnd;
                //}

                $output .= $part->toString().$lineEnd;
            }

            $output .= '--'.$boundary.'--'.$lineEnd;
        } else if($this->_parts[0]) {
            $output .= $this->_parts[0]->getBodyString();
        }

        return $output;
    }

    public function count() {
        return count($this->_parts);
    }


// Iterator
    public function rewind() {
        reset($this->_parts);
    }

    public function current() {
        return current($this->_parts);
    }

    public function key() {
        return key($this->_parts);
    }

    public function next() {
        next($this->_parts);
    }

    public function valid() {
        return ($this->current() !== false);
    }

    public function hasChildren() {
        return !empty($this->_parts);
    }

    public function getChildren() {
        return $this->_parts;
    }


// Dump
    public function getDumpProperties() {
        $output = [];

        foreach($this->_headers as $key => $header) {
            $output[] = new core\debug\dumper\Property($key, $header, 'protected');
        }

        foreach($this->_parts as $i => $part) {
            $output[] = new core\debug\dumper\Property($i, $part);
        }

        return $output;
    }
}