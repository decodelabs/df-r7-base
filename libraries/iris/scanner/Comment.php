<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\scanner;

use df;
use df\core;
use df\iris;
    
class Comment implements iris\IScanner {

    protected $_markers = [
        '//' => "\n",
        '/*' => '*/'
    ];

    protected $_allowNesting = true;

    public function __construct(array $markers=null) {
        if($markers !== null) {
            $this->setMarkers($markers);
        }
    }

    public function getName() {
        return 'Comment';
    }

    public function getWeight() {
        return 500;
    }

    public function setMarkers(array $markers) {
        return $this->clearMarkers()
            ->addMarkers($markers);
    }

    public function addMarkers(array $markers) {
        foreach($markers as $start => $end) {
            $this->addMarker($start, $end);
        }

        return $this;
    }

    public function addMarker($start, $end) {
        $this->_markers[$start] = $end;
        return $this;
    }

    public function hasMarker($start) {
        return isset($this->_markers[$start]);
    }

    public function getMarker($start) {
        if(isset($this->_markers[$start])) {
            return $this->_markers[$start];
        }
    }

    public function removeMarker($start) {
        unset($this->_markers[$start]);
        return $this;
    }

    public function clearMarkers() {
        $this->_markers = array();
        return $this;
    }

    public function allowNesting($flag) {
        if($flag !== null) {
            $this->_allowNesting = (bool)$flag;
            return $this;
        }

        return $this->_allowNesting;
    }

    public function initialize(iris\ILexer $lexer) {
        if(empty($this->_markers)) {
            throw new iris\LogicException(
                'Comment scanner does not have any markers to match'
            );
        }
    }

    public function check(iris\ILexer $lexer) {
        foreach($this->_markers as $start => $end) {
            if($lexer->peek(0, mb_strlen($start)) == $start) {
                return true;
            }
        }

        return false;
    }

    public function run(iris\ILexer $lexer) {
        foreach($this->_markers as $start => $end) {
            $startLength = mb_strlen($start);

            if($lexer->peek(0, $startLength) == $start) {
                break;
            }
        }

        $isSingleLine = $end === "\n";
        $type = $isSingleLine ? 'single' : 'multi';
        $lexer->extract($startLength);
        $comment = '';
        $firstStart = mb_substr($start, 0, 1);
        $firstEnd = mb_substr($end, 0, 1);
        $endLength = mb_strlen($end);
        $level = 1;

        while(true) {
            if($lexer->char == "\n") {
                if($isSingleLine) {
                    break;
                }

                $comment .= $lexer->extract();
            } else if($lexer->char == $firstStart && $lexer->peek(0, $startLength) == $start) {
                $level++;
                $comment .= $lexer->extract($startLength);
            } else if($lexer->char == $firstEnd && $lexer->peek(0, $endLength) == $end) {
                $level--;

                if($level == 0 || !$this->_allowNesting) {
                    $lexer->extract($endLength);
                    break;
                } else {
                    $comment .= $lexer->extract($endLength);
                }
            } else {
                $comment .= $lexer->extract();
            }

            if($lexer->char === false) {
                break;
            }
        }

        return $lexer->newToken('comment/'.$type, $comment);
    }
}