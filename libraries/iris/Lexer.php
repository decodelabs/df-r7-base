<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris;

use df;
use df\core;
use df\iris;
    
class Lexer implements ILexer, core\IDumpable {

    use TLocationProvider;

    public $char = '';
    public $position = 0;
    public $line = 1;
    public $column = 1;
    public $lastLine = 0;
    public $linePosition = 0;
    public $lastWhitespace = '';

    protected $_scanners = array();
    protected $_source;
    protected $_isStarted = false;
    protected $_latchLine = 1;
    protected $_latchColumn = 1;

    public function __construct(ISource $source, array $scanners=null) {
        $this->_source = $source;
        $this->char = $source->substring(0);

        if($scanners) {
            $this->setScanners($scanners);
        }
    }

    public function getSource() {
        return $this->_source;
    }

    public function getSourceUri() {
        return $this->_source->getSourceUri();
    }

    public function getLine() {
        return $this->line;
    }

    public function getColumn() {
        return $this->column;
    }


// Scanners
    public function setScanners(array $scanners) {
        return $this->clearScanners()
            ->addScanners($scanners);
    }

    public function addScanners(array $scanners) {
        foreach($scanners as $scanner) {
            if(!$scanner instanceof IScanner) {
                throw new InvalidArgumentException(
                    'Invalid scanner detected'
                );
            }

            $this->addScanner($scanner);
        }

        return $this;
    }

    public function addScanner(IScanner $scanner) {
        if($this->hasScanner($scanner)) {
            throw new LogicException(
                'Scanner '.$scanner->getName().' has already been added to the lexer'
            );
        }

        $this->_scanners[$scanner->getName()] = $scanner;
        return $this;
    }

    public function hasScanner($name) {
        if($name instanceof IScanner) {
            $name = $name->getName();
        }

        $name = ucfirst($name);

        return isset($this->_scanners[$name]);
    }

    public function getScanner($name) {
        $name = ucfirst($name);

        if(isset($this->_scanners[$name])) {
            return $this->_scanners[$name];
        }
    }

    public function removeScanner($name) {
        if($name instanceof IScanner) {
            $name = $name->getName();
        }

        $name = ucfirst($name);

        unset($this->_scanners[$name]);
        return $this;
    }

    public function clearScanners() {
        $this->_scanners = array();
        return $this;
    }


// Exec
    public function tokenize() {
        if($this->_isStarted) {
            throw new LogicException(
                'Lexer has already been started'
            );
        }

        if(empty($this->_scanners)) {
            throw new LogicException(
                'Lexer has no scanners'
            );  
        }


        uasort($this->_scanners, function($a, $b) {
            return $a->getWeight() > $b->getWeight();
        });

        foreach($this->_scanners as $scanner) {
            $scanner->initialize($this);
        }

        $this->_isStarted = true;

        do {
            $token = $this->extractToken();
            $this->_tokens[] = $token;
        } while(!$token->is('eof'));

        return $this->_tokens;
    }

    public function extractToken() {
        $this->lastWhitespace = $this->extractWhitespace();
        $this->_latchLine = $this->line;
        $this->_latchColumn = $this->column;

        $token = null;

        foreach($this->_scanners as $scanner) {
            if($scanner->check($this)) {
                if($token = $scanner->run($this)) {
                    break;
                }
            }
        }

        if(!$token) {
            if(!$this->char) {
                $token = $this->newToken('eof');
            } else {
                throw new UnexpectedCharacterException(
                    'Lexer doesn\'t have a scanner to handle char: '.$this->char,
                    $this->getLocation()
                );
            }
        }

        $this->lastLine = $this->line;
        $this->lastWhitespace = '';

        return $token;
    }

    public function newToken($type, $value=null) {
        return new Token($type, $value, $this->lastWhitespace, $this->_latchLine, $this->_latchColumn, $this->_source->getSourceUri());
    }

    public function getTokens() {
        return $this->_tokens;
    }



// Extract
    public function extract($length=1) {
        $length = (int)$length;

        if($length < 1) {
            return '';
        }

        $output = '';

        for($i = 0; $i < $length; $i++) {
            if($this->char === false) {
                break;
            } else if($this->char == "\n") {
                $this->line++;
                $this->column = 1;
                $this->linePosition = $this->position + 1;
            } else {
                $this->column++;
            }

            $output .= $this->char;

            $this->position++;
            $this->char = $this->_source->substring($this->position, 1);
        }

        return $output;
    }

    public function peek($offset, $length) {
        $length = (int)$length;
        $offset = (int)$offset;

        if($length < 1) {
            return '';
        }

        return $this->_source->substring($this->position + $offset, $length);
    }

    public function substring($position, $length) {
        $length = (int)$length;
        $position = (int)$position;

        if($length < 1) {
            return '';
        }

        return $this->_source->substring($position, $length);
    }

    public function extractAlpha() {
        $output = '';

        while(core\string\Util::isAlpha($this->char)) {
            $output .= $this->char;
            $this->extract();
        }
        
        return $output;
    }

    public function extractAlphanumeric() {
        $output = '';

        while(core\string\Util::isAlphanumeric($this->char)) {
            $output .= $this->char;
            $this->extract();
        }
        
        return $output;
    }

    public function extractNumeric() {
        $output = '';

        while(core\string\Util::isDigit($this->char)) {
            $output .= $this->char;
            $this->extract();
        }
        
        return $output;
    }

    public function extractWhitespace() {
        $output = '';

        while(core\string\Util::isWhitespace($this->char)) {
            $output .= $this->char;
            $this->extract();
        }
        
        return $output;
    }

    public function extractRegexRange($regex) {
        $output = '';

        while(preg_match('/^['.$regex.']$/', $this->char)) {
            $output .= $this->char;
            $this->extract();
        }

        return $output;
    }


// Dump
    public function getDumpProperties() {
        return [
            'char' => $this->char,
            'position' => $this->position,
            'line' => $this->line,
            'column' => $this->column,
            'lastLine' => $this->lastLine,
            'linePosition' => $this->linePosition,
            'lastWhitespace' => $this->lastWhitespace,
            'tokens' => $this->_tokens,
            'scanners' => $this->_scanners
        ];
    }
}