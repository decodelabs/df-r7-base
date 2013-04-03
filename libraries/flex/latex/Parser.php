<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\latex;

use df;
use df\core;
use df\flex;
use df\iris;
    
class Parser extends iris\Parser {

    public $document;
    public $container;
    public $environment = 'root';

    protected $_containerStack = array();
    protected $_commands = array();
    protected $_environments = array();

    public function __construct(Lexer $lexer) {
        parent::__construct($lexer, [
            new flex\latex\package\Core()
        ]);
    }

    public function parseRoot() {
        $this->document = new flex\latex\map\Document($this->token->getLocation());
        $this->unit->addEntity($this->document);

        $this->parseEnvironment('root');
        core\dump($this);
    }

    public function parseStandardContent(IContainerNode $container, $expectBraceEnd=false) {
        $this->_pushContainer($container);

        $textNode = null;

        while(true) {
            $token = $this->extract();

            if(!$token || $token->matches('eof')) {
                break;
            }

            switch($token->type) {
                case 'command':
                    $this->parseCommand($token->value);

                    if($token->value == 'end') {
                        if($expectBraceEnd) {
                            throw new iris\UnexpectedTokenException(
                                'Expecting brace end, not environment end', 
                                $token
                            );
                        }

                        break 2;
                    }

                    break;

                case 'keySymbol':
                    if($token->isValue('}')) {
                        throw new iris\UnexpectedTokenException(
                            'Wasn\'t expecting a brace end here..', 
                            $token
                        );
                    }

                    $this->parseKeySymbol($token);
                    break;

                case 'symbol':
                case 'word':
                    $this->rewind(1);
                    $this->parseText($textNode, !$expectBraceEnd);
                    break;

                default:
                    throw new iris\UnexpectedTokenException(
                        'Don\'t know what to do with this '.$token->type.' token',
                        $token
                    );
            }

            if($expectBraceEnd && $this->token->isValue('}')) {
                break;
            }
        }

        $this->_popContainer();
    }


// Text
    public function parseText(ITextNode $textNode=null, $ensureParagraph) {
        $location = $this->token->getLocation();

        if($ensureParagraph && !$this->container instanceof IParagraph) {
            $this->_pushContainer(new flex\latex\map\Paragraph($location));
        }

        if($textNode === null) {
            $textNode = new flex\latex\map\TextNode($location);
        }

        while($this->token->is('word', 'symbol')) {
            if(!$textNode->isEmpty() && $this->token->isAfterWhitespace()) {
                $lineCount = $this->token->countNewLines();

                if($lineCount >= 2) {
                    if($this->container instanceof IParagraph) {
                        break;
                    } else {
                        $textNode->appendText("\n\n");
                    }
                } else {
                    $textNode->appendText(' ');
                }
            }

            $textNode->appendText($this->extract()->value);
        }

        if($textNode->isEmpty()) {
            $textNode = null;
        }


        // Check for date
        if($textNode
        && count($this->document) == 1
        && preg_match('/^[0-9]{1,2} [A-Z][a-z]+ [0-9]{4}$/', $textNode->text)
        ) {
            try {
                $this->document->setDate($textNode->text);
                $textNode = null;
            } catch(\Exception $e) {}
        }

        if($textNode) {
            $this->container->push($textNode);
        }

        if($this->container instanceof IParagraph 
        && $this->token->countNewLines() >= 2) {
            $this->_popContainer();
        }

        return $textNode;
    }


// Symbols
    public function parseKeySymbol(iris\IToken $token) {
        switch($token->value) {
            case '$':
                return $this->parseInlineMathMode($token);

            default:
                core\dump($token, $this);
        }
    }



// Math mode
    public function parseInlineMathMode(iris\IToken $token) {
        $doMath = true;

        if($this->token->isValue('^', '_')
        && $this->peek()->isValue('{')) {
            $doMath = false;
            $object = new flex\latex\map\TextNode($token->getLocation());

            $object->addClass(
                $this->token->value == '^' ? 'superscript' : 'subscript'
            );


            $rewind = 2;
            $this->extract(2);

            while(!$this->token->isValue('}')) {
                if(!$this->token->is('word')) {
                    $this->rewind($rewind);
                    $doMath = true;
                    $object = null;
                    break;
                }

                $rewind++;

                if(!$object->isEmpty()) {
                    $object->appendText(' ');
                }

                $object->appendText($this->extract()->value);
            }

            if(!$doMath) {
                $this->extractValue('}');
                $this->extractValue('$');
            }
        } 

        if($doMath) {
            // Math
            $object = new flex\latex\map\MathNode($token->getLocation());

            while(!$this->token->isValue('$')) {
                if(!$object->isEmpty()) {
                    $object->appendSymbols($this->token->getWhitespace());
                }

                $object->appendSymbols($this->extract()->value);
            }

            $this->extractValue('$');
        }

        $this->container->push($object);
        return $object;
    }

 
// Commands
    public function registerCommand($name, IPackage $package) {
        $this->_commands[$name] = $package;
        return $this;
    }

    public function parseCommand($name) {
        $lookup = rtrim($name, '*');

        if(!isset($this->_commands[$lookup])) {
            throw new UnexpectedValueException(
                'Command '.$name.' does not have a handler package'
            );
        }

        $package = $this->_commands[$lookup];
        return $package->parseCommand($name);
    }

// Environments
    public function registerEnvironment($name, IPackage $package) {
        $this->_environments[$name] = $package;
        return $this;
    }

    public function parseEnvironment($name) {
        if(!isset($this->_environments[$name])) {
            throw new UnexpectedValueException(
                'Environment '.$name.' does not have a handler package'
            );
        }

        $lastEnv = $this->environment;
        $this->environment = $name;
        $package = $this->_environments[$name];
        $output = $package->parseEnvironment($name);
        $this->environment = $lastEnv;

        return $output;
    }


// Containers
    protected function _pushContainer(IContainerNode $container) {
        $this->_containerStack[] = $container;
        $this->container = $container;
    }

    protected function _popContainer() {
        $output = array_pop($this->_containerStack);
        $i = count($this->_containerStack) - 1;

        if(isset($this->_containerStack[$i])) {
            $this->container = $this->_containerStack[$i];

            if($output && !$output->isEmpty()) {
                $this->container->push($output);
            }
        } else {
            $this->container = null;
        }

        return $output;
    }

// Dump
    public function getDumpProperties() {
        return array_merge(
            [
                'commands' => count($this->_commands),
                'environments' => count($this->_environments)
            ], 
            parent::getDumpProperties(),
            [
                'container' => $this->container
            ]
        );
    }
}