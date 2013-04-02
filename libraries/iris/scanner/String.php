<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\scanner;

use df;
use df\core;
use df\iris;
    
class String implements iris\IScanner {

    protected $_containers = [
        '"' => 'derefString', 
        "'" => 'string'
    ];

    protected $_allowChars = true;
    protected $_charSymbol = '@';
    protected $_charContainer = "'";
    protected $_escapeSymbol = '\\';
    protected $_alignMultiLine = true;

    public function __construct(array $containers=null) {
        if($containers !== null) {
            $this->setContainers($containers);
        }
    }

    public function getName() {
        return 'String';
    }

    public function getWeight() {
        return 60;
    }

    public function setContainers(array $containers) {
        return $this->clearContainers()
            ->addContainers($containers);
    }

    public function addContainers(array $containers) {
        foreach($containers as $container => $type) {
            $this->addContainer($container, $type);
        }

        return $this;
    }

    public function addContainer($container, $type) {
        $this->_containers[$container] = $type;
        return $this;
    }

    public function hasContainer($container) {
        return isset($this->_containers[$container]);
    }

    public function getContainerType($container) {
        if(isset($this->_containers[$container])) {
            return $this->_containers[$container];
        }
    }

    public function removeContainer($container) {
        unset($this->_containers[$container]);
        return $this;
    }

    public function clearContainers() {
        $this->_containers = array();
        return $this;
    }

    public function allowChars($flag=null) {
        if($flag !== null) {
            $this->_allowChars = (bool)$flag;

            if($this->_allowChars && $this->_charSymbol === null) {
                $this->_charSymbol = '@';
            }

            return $this;
        }

        return $this->_allowChars;
    }

    public function setCharSymbol($symbol) {
        $this->allowChars((bool)$symbol);
        $this->_charSymbol = (string)$symbol;

        return $this;
    }

    public function getCharSymbol() {
        return $this->_charSymbol;
    }

    public function setCharContainer($container) {
        $this->_charContainer = (string)$container;

        if(empty($this->_charContainer)) {
            $this->_charContainer = "'";
        }

        return $this;
    }

    public function getCharContainer() {
        return $this->_charContainer;
    }

    public function setEscapeSymbol($symbol) {
        $this->_escapeSymbol = $symbol;
        return $this;
    }

    public function getEscapeSymbol() {
        return $this->_escapeSymbol;
    }

    public function shouldAlignMultiLine($flag=null) {
        if($flag !== null) {
            $this->_alignMultiLine = (bool)$flag;
            return $this;
        }

        return $this->_alignMultiLine;
    }

    public function initialize(iris\ILexer $lexer) {
        
    }

    public function check(iris\ILexer $lexer) {
        if(isset($this->_containers[$lexer->char])) {
            return true;
        }

        if($this->_allowChars && $lexer->char == $this->_charSymbol && $lexer->peek(1, 1) == $this->_charContainer) {
            return true;
        }

        return false;
    }

    public function run(iris\ILexer $lexer) {
        if($this->_allowChars && $lexer->char == $this->_charSymbol) {
            // Char
            $lexer->extract(2);

            if($lexer->char == $this->_escapeSymbol) {
                // Escape
                $c = core\string\Util::mbOrd($this->_processEscape($lexer));
            } else if($lexer->char == '0' && strtolower($lexer->peek(1, 1)) == 'x') {
                // Hex
                $lexer->extract(2);
                $string = $lexer->extractRegexRange('a-fA-F0-9');

                $c = core\string\Manipulator::baseConvert($string, 16, 10);
            } else {
                // Abstract
                $c = core\string\Util::mbOrd($lexer->extractRegexRange('^'.$this->_charContainer));
            }

            if($lexer->char != $this->_charContainer) {
                throw new iris\UnexpectedCharacterException(
                    'Expected close of char literal, instead got '.$lexer->char,
                    $lexer->getLocation()
                );
            }

            $lexer->extract();
            return $lexer->newToken('literal/char', $c);
        }

        $container = $lexer->extract();
        $openLine = $lexer->linePosition;
        $openPosition = $lexer->position;
        $string = '';

        while(true) {
            if($lexer->char === false) {
                // EOF
                throw new iris\UnexpectedCharacterException(
                    'Unexpected string termination',
                    $lexer->getPosition()
                );
            }

            if($lexer->char == $container) {
                // End of string
                $lexer->extract();
                break;
            }

            if($lexer->char === "\n") {
                // Multiline
                $string .= $lexer->extract();

                if($this->_alignMultiLine) {
                    $this->_skipStringWhitespace($lexer, $openLine, $openPosition);
                }
            } else if($lexer->char == $this->_escapeSymbol) {
                // Escape
                $this->_processEscape($lexer);
            } else {
                // Everything else
                $string .= $lexer->extract();
            }
        }

        return $lexer->newToken('literal/'.$this->_containers[$container], $string);
    }

    protected function _processEscape($lexer) {
        if($lexer->char != $this->_escapeSymbol) {
            throw new iris\UnexpectedCharacterException(
                'Expected escape symbol',
                $lexer->getLocation()
            );
        }

        $lexer->extract();

        switch($lexer->char) {
            case 'b':
                $lexer->extract();
                return "\b";

            case 'f':
                $lexer->extract();
                return "\f";

            case 'n':
                $lexer->extract();
                return "\n";

            case 'r':
                $lexer->extract();
                return "\r";

            case 't':
                $lexer->extract();
                return "\t";

            case 'u':
                $unicode = $lexer->peek(1, 4);
                $lexer->extract();

                if(!preg_match('/^[a-fA-F0-9]{4}$/', $unicode)) {
                    return 'u';
                }

                $lexer->extract(4);
                return mb_convert_encoding(pack('H*', $unicode), 'UTF-8', 'UCS-2BE');

            case '"':
            case '$':
            case '\'':
            case '`':
            case '\\':
                return $lexer->extract();
        }
    }

    protected function _skipStringWhitespace($lexer, $openLine, $openPosition) {
        for($i = $openLine; $i < $openPosition; $i++) {
            $a = $lexer->substring($i, 1);

            if(($a == "\t" && $lexer->char != "\t")
            || ($a != "\t" && $lexer->char != ' ')) {
                if($lexer->char == "\n") {
                    return true;
                }

                $numTabs = 0;
                $numSpaces = 0;

                for($j = $openLine; $j < $openPosition; ++$j) {
                    if($lexer->substring($j, 1) == "\t") {
                        ++$numTabs;
                    } else {
                        ++$numSpaces;
                    }
                }

                if($numTabs == 0) {
                    throw new iris\UnexpectedCharacterException(
                        'Leading space in multi-line string must be '.$numSpaces.' spaces, ',
                        $lexer->getLocation()
                    );
                } else {
                    throw new iris\UnexpectedCharacterException(
                        'Leading space in multi-line string must be '.$numTabs.' tabs and '.$numSpaces.' spaces, ',
                        $lexer->getLocation()
                    );
                }

                return false;
            }

            $lexer->extract();
        }

        return true;
    }
}