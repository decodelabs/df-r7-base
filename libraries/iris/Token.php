<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris;

use df;
use df\core;
use df\iris;

class Token implements IToken, core\IDumpable {

    public $type;
    public $subType;
    public $value;
    public $whitespace;
    public $line;
    public $column;
    public $sourceUri;

    public function __construct($type, $value, $whitespace, $line, $column, ISourceUri $sourceUri) {
        $parts = explode('/', $type, 2);
        $this->type = array_shift($parts);
        $this->subType = array_shift($parts);
        $this->value = $value;
        $this->whitespace = $whitespace;
        $this->line = $line;
        $this->column = $column;
        $this->sourceUri = $sourceUri;
    }


    public function getType() {
        return $this->type;
    }

    public function getSubType() {
        return $this->subType;
    }

    public function getTypeString() {
        $output = $this->type;

        if($this->subType) {
            $output .= '/'.$this->subType;
        }

        return $output;
    }

    public function getValue() {
        return $this->value;
    }

    public function getWhitespace() {
        return $this->whitespace;
    }

    public function getWhitespaceBeforeNewLine() {
        $parts = explode("\n", str_replace("\r", '', $this->whitespace));
        return array_shift($parts);
    }

    public function getWhitespaceAfterLastNewLine() {
        $parts = explode("\n", str_replace("\r", '', $this->whitespace));
        return array_pop($parts);
    }

    public function isWhitespaceSingleNewLine() {
        return str_replace("\r", '', $this->whitespace) == "\n";
    }

    public function isAfterWhitespace() {
        return strlen($this->whitespace) > 0;
    }

    public function isAfterNewline() {
        return false !== strpos($this->whitespace, "\n");
    }

    public function isOnNextLine() {
        return mb_substr_count($this->whitespace, "\n") == 1;
    }

    public function countNewLines() {
        return mb_substr_count($this->whitespace, "\n");
    }


    public function eq(IToken $token) {
        return $this->type == $token->type
            && $this->subType == $token->subType
            && $this->value = $token->value;
    }

    public function is(...$ids) {
        foreach(core\collection\Util::flatten($ids) as $id) {
            $type = $subType = $value = null;
            @list($type, $value) = explode('=', $id, 2);
            @list($type, $subType) = explode('/', $type, 2);

            if($this->matches($type, $subType, $value)) {
                return true;
            }
        }

        return false;
    }

    public function isValue(...$values) {
        foreach(core\collection\Util::flatten($values) as $value) {
            if($value == $this->value) {
                return true;
            }
        }

        return false;
    }

    public function matches($type, $subType=null, $value=null) {
        if(empty($type)) {
            $type = null;
        }

        if(empty($subType)) {
            $subType = null;
        }

        if(empty($value)) {
            $value = null;
        }

        if($type != $this->type) {
            return false;
        }

        if($subType !== null && $subType != $this->subType) {
            return false;
        }

        if($value !== null && $value != $this->value) {
            return false;
        }

        return true;
    }


// Location
    public function getLine(): ?int {
        return $this->line;
    }

    public function getColumn() {
        return $this->column;
    }

    public function getSourceUri() {
        return $this->sourceUri;
    }

    public function getLocation() {
        return new Location($this->sourceUri, $this->line, $this->column);
    }

// Dump
    public function getDumpProperties() {
        $output = $this->getTypeString();

        if($this->value !== null) {
            $value = $this->value;

            if($value === true) {
                $value = 'true';
            } else if($value === false) {
                $value = 'false';
            }

            $output .= ' '.trim($value);
        }

        $output .= ' ['.$this->line.':'.$this->column.']';

        return $output;
    }
}
