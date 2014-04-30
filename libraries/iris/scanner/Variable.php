<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\scanner;

use df;
use df\core;
use df\iris;
    
class Variable implements iris\IScanner, core\IDumpable {

    protected $_markers = [];

    public function __construct(array $markers) {
        $this->setMarkers($markers);
    }

    public function getName() {
        return 'Variable';
    }

    public function getWeight() {
        return 20;
    }

    public function setMarkers(array $markers) {
        return $this->clearMarkers()
            ->addMarkers($markers);
    }

    public function addMarkers(array $markers) {
        foreach($markers as $symbol => $type) {
            $this->addMarker($symbol, $type);
        }

        return $this;
    }

    public function addMarker($symbol, $type) {
        $this->_markers[$symbol] = $type;
        return $this;
    }

    public function hasMarker($symbol) {
        return isset($this->_markers[$symbol]);
    }

    public function getMarker($symbol) {
        if(isset($this->_markers[$symbol])) {
            return $this->_markers[$symbol];
        }
    }

    public function removeMarker($symbol) {
        unset($this->_markers[$symbol]);
        return $this;
    }

    public function clearMarkers() {
        $this->_markers = [];
        return $this;
    }


    public function initialize(iris\ILexer $lexer) {
        if(empty($this->_markers)) {
            throw new iris\LogicException(
                'Variable processor does not have any markers to match'
            );
        }
    }

    public function check(iris\ILexer $lexer) {
        if(!isset($this->_markers[$lexer->char])) {
            return false;
        }

        $peek = $lexer->peek(1, 1);
        return core\string\Util::isAlpha($peek) || $peek == '_';
    }

    public function run(iris\ILexer $lexer) {
        $type = $this->_markers[$lexer->char];
        $lexer->extract();
        $word = $lexer->extractRegexRange('a-zA-Z0-9_');

        return $lexer->newToken($type, $word);
    }

// Dump
    public function getDumpProperties() {
        return $this->_markers;
    }
}