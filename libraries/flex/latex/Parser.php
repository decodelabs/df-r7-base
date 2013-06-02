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
            new flex\latex\package\Foundation(),
            new flex\latex\package\Textcomp()
        ]);
    }

    public function parse() {
        parent::parse();
        return $this->document;
    }

    public function parseRoot() {
        $this->document = new flex\latex\map\Document($this->token->getLocation());
        $this->unit->addEntity($this->document);

        $this->parseEnvironment('root');
        return $this->document;
    }

    public function parseStandardContent(IContainerNode $container, $expectEnd=false, $addToParent=true) {
        $pop = $this->pushContainer($container);

        $expectBraceEnd = (bool)$expectEnd;

        if($expectEnd === true) {
            $expectEnd = '}';
        }

        while(true) {
            $comment = $this->lastComment;
            $token = $this->extract();

            if(!$token || $token->matches('eof')) {
                break;
            }

            if($expectBraceEnd && $token->isValue($expectEnd)) {
                break;
            }

            switch($token->type) {
                case 'command':
                    if(!$this->lastComment) {
                        $this->lastComment = $comment;
                    }

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
                    if($token->isValue($expectEnd)) {
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
                    $this->parseText(!$expectBraceEnd);
                    break;

                default:
                    throw new iris\UnexpectedTokenException(
                        'Don\'t know what to do with this '.$token->type.' token',
                        $token
                    );
            }

            if($expectBraceEnd && $this->token->isValue($expectEnd)) {
                break;
            }
        }

        if($pop) {
            $this->popContainer($addToParent);
        }
    }


// Text
    public function parseText($ensureParagraph) {
        $location = $this->token->getLocation();

        if($ensureParagraph && !$this->container instanceof IParagraph) {
            $this->pushContainer(new flex\latex\map\Paragraph($location));
        }

        $textNode = $this->container->getLast();
        $append = false;

        if(!$textNode instanceof flex\latex\map\TextNode
         || $textNode->hasClasses()) {
            $textNode = new flex\latex\map\TextNode($location);
            $append = true;
        }

        $first = true;

        while($this->token->is('word', 'symbol')) {
            if($first && $this->token->isAfterWhitespace()) {
                $last = $this->getLastToken();

                if($last 
                && ($last->matches('keySymbol', null, '}') || $last->matches('keySymbol', null, '$'))
                && strlen($this->token->getWhitespaceAfterLastNewLine())) {
                    $textNode->appendText(' ');
                }
            }

            $first = false;

            if(!$textNode->isEmpty() && $this->token->isAfterWhitespace()) {
                $lineCount = $this->token->countNewLines();

                if($lineCount >= 2) {
                    if($this->container instanceof IParagraph) {
                        break;
                    } else {
                        $textNode->appendText("\n\n");
                    }
                } else if(substr($textNode->text, -1) != ' ') {
                    $textNode->appendText(' ');
                }
            }

            $word = $this->extract()->value;

            // Quotes
            if($word == '`') {
                if($this->token->value == '`') {
                    $this->extract();
                    $word = '“';
                } else {
                    $word = '‘';
                }
            } else if($word == '\'') {
                if($this->token->value == '\'') {
                    $this->extract();
                    $word = '”';
                } else {
                    $word = '’';
                }
            }

            // Dashes
            if($word == '-') {
                if($this->token->value == '-') {
                    $this->extract();

                    if($this->token->value == '-') {
                        $this->extract();
                        $word = '—';
                    } else {
                        $word = '–';
                    }
                } else {
                    $word = '-';
                }
            }

            $textNode->appendText($word);
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
            if(($this->token->matches('command') || $this->token->matches('keySymbol', null, '$'))
            && $this->token->isAfterWhitespace()) {
                $textNode->appendText($this->token->getWhitespaceBeforeNewLine());
            }

            if($append) {
                $this->container->push($textNode);
            }
        }

        if($this->container instanceof IParagraph 
        && $this->token->countNewLines() >= 2) {
            $this->popContainer();
        }

        return $textNode;
    }

    public function closeParagraph() {
        if($this->container instanceof flex\latex\map\Paragraph) {
            $this->popContainer(true);
        }
    }

    public function extractRefId() {
        $output = $this->extractWord()->value;

        while(
            //!$this->token->isAfterWhitespace() 
        //&& 
        $this->token->is('word', 'symbol')
        && !$this->token->isValue('}')) {
            $output .= $this->extract()->value;
        }

        return $output;
    }

    public function writeToTextNode($text) {
        $pop = false;
        
        if(!$this->container) {
            $pop = true;
            $this->pushContainer(new flex\latex\map\Paragraph($this->token));
        }

        $textNode = $this->container->getLast();

        if(!$textNode instanceof flex\latex\map\TextNode) {
            $textNode = new flex\latex\map\TextNode($this->token);
        }

        if(substr($text, 0, 1) == ' ' && substr($textNode->getText(), -1) == ' ') {
            $text = substr($text, 1);
        }

        $textNode->appendText($text);

        if($pop) {
            $this->popContainer(true);
        }

        return $textNode;
    }


// Symbols
    public function parseKeySymbol(iris\IToken $token) {
        switch($token->value) {
            case '$':
                return $this->parseInlineMathMode($token);

            case '~':
                // TODO: join connecting nodes
                return;

            case '}':
            case '{':
                // Recoverable syntax error
                return;

            default:
                $this->rewind(4);
                core\dump($token, $this);
        }
    }



// Math mode
    public function parseInlineMathMode(iris\IToken $token) {
        $doMath = true;
        $end = '$';

        $lastToken = $this->getLastToken();

        if($lastToken->isWhitespaceSingleNewLine()) {
            $this->writeToTextNode(' ');
        }

        if($this->token->isValue('^', '_')) {
            $doMath = false;
            $object = new flex\latex\map\TextNode($token->getLocation());

            $object->addClass(
                $this->token->value == '^' ? 'superscript' : 'subscript'
            );


            $rewind = 1;
            $this->extract();

            if($this->token->isValue('{')) {
                $rewind++;
                $this->extract();
                $end = '}';
            }

            while(!$this->token->isValue($end)) {
                if(!$this->token->is('word')) {
                    $this->rewind($rewind);
                    $doMath = true;
                    $object = null;
                    break;
                }

                $rewind++;
                $token = $this->extract();

                if(!$object->isEmpty()) {
                    $object->appendText(' ');
                }

                $object->appendText($token->value);
            }

            if(!$doMath) {
                $this->extractValue($end);

                if($end != '$') {
                    $this->extractValue('$');
                }
            }
        } 

        if($doMath) {
            // Math
            $object = new flex\latex\map\MathNode($token->getLocation());
            $object->isInline(true);

            while(true) {
                $token = $this->extract();

                if(!$object->isEmpty()) {
                    $object->appendSymbols($token->getWhitespace());
                }

                if($token->matches('command') || $token->matches('symbol', 'esc')) {
                    $object->appendSymbols('\\');
                }

                $object->appendSymbols($token->value);

                if($this->token->isValue('$')) {
                    $peek = $this->peek(1);

                    if($peek->is('keySymbol=_') && !$peek->isAfterWhitespace()) {
                        // Hack to fix triplets
                        $this->extract();
                        continue;
                    }
                    
                    break;
                }
            }

            $this->extractValue('$');
        }

        $this->container->push($object);
        return $object;
    }

    protected $_mathCounter = 0;

    public function parseBlockMathMode($blockType) {
        $object = new flex\latex\map\MathNode($this->token->getLocation());
        $object->isInline(false);
        $object->setBlockType($blockType);
        $object->setNumber(++$this->_mathCounter);

        // Put on containerStack to give label command a target  
        $this->_containerStack[] = $object;

        while(!$this->token->is('command=end')) {
            $token = $this->extract();

            if($token->is('command=label')) {
                $this->parseCommand('label');
            } else {
                if(!$object->isEmpty()) {
                    $object->appendSymbols($token->getWhitespace());
                }

                if($token->matches('command') || $token->matches('symbol', 'esc')) {
                    $object->appendSymbols('\\');
                }

                $object->appendSymbols($token->value);
            }
        }

        // Pop back off stack
        array_pop($this->_containerStack);

        $this->container->push($object);
        return $object;
    }

 
// Commands
    public function registerCommand($name, IPackage $package) {
        $this->_commands[$name] = $package;
        return $this;
    }

    public function parseCommand($name) {
        if($name == 'begin') {
            $comment = $this->lastComment;

            $this->extractValue('{');
            $envName = $this->extractWord()->value;
            $this->extractValue('}');

            if(!$this->lastComment) {
                $this->lastComment = $comment;
            }

            return $this->parseEnvironment($envName);
        } else if($name == 'end') {
            $this->extractValue('{');
            $env = $this->extractWord();
            $this->extractValue('}');

            if($env->value != $this->environment) {
                throw new iris\UnexpectedTokenException(
                    'Found end of '.$env->value.' environment, expected end of '.$this->environment,
                    $env
                );
            }

            if($env->value == 'document') {
                while($this->token) {
                    $this->extract();
                }
            }

            return;
        }


        $lookup = rtrim($name, '*');

        if(!isset($this->_commands[$lookup])) {
            throw new UnexpectedValueException(
                'Command '.$name.' does not have a handler package'
            );
        }

        $package = $this->_commands[$lookup];

        if($package instanceof IActivePackage) {
            return $package->parseCommand($name);
        } else {
            if($isStar = (substr($name, -1) == '*')) {
                $name = substr($name, 0, -1);
            }

            $args = [];

            if(strlen($name) == 1) {
                $func = 'command_callSymbol';
                $args[] = $name;
            } else {
                $func = 'command_'.str_replace(['@'], ['AT'], ltrim($name, '\\'));
            }

            $args[] = $isStar;

            if(!method_exists($package, $func)) {
                throw new flex\latex\UnexpectedValueException(
                    'Package '.$package->getName().' does not have a parser for command '.$name
                );
            }

            return call_user_func_array([$package, $func], $args);
        }
    }

    public function extractMacroFromCommand() {
        $value = $this->parseCommand($this->extract()->value);
        
        if(!$value instanceof IMacro) {
            throw new UnexpectedValueException(
                'Expecting macro'
            );
        }

        return $value;
    }

    public function skipCommand($requireBlock=true) {
        $this->extractOptionList();
        
        if(($requireBlock && $this->extractValue('{'))
        || $this->extractIfValue('{')) {
            $level = 1;

            while(true) {
                $token = $this->extract();

                if($token->isValue('{')) {
                    $level++;
                } else if($token->isValue('}')) {
                    $level--;

                    if(!$level) {
                        break;
                    }
                }
            }
        }

        $this->extractOptionList();
    }

    public function extractOptionList() {
        $options = array();

        if($this->extractIfValue('[')) {
            while(true) {
                if($this->token->is('command')) {
                    $value = $this->extractMacroFromCommand();
                    $option = $value->name;
                } else {
                    $word = $this->extractWord();
                    $option = $word->value;
                    $value = true;

                    if($this->extractIfValue('=')) {
                        $value = $this->extractWord()->value;
                    } 
                }

                $options[$option] = $value;

                if($this->extractIfValue(']')) {
                    break;
                } else {
                    $this->extractValue(',');
                }
            }
        }

        return $options;
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

        if($this->container instanceof flex\latex\map\Paragraph) {
            $this->popContainer();
        }

        $lastEnv = $this->environment;
        $this->environment = $name;
        $package = $this->_environments[$name];

        $func = 'environment_'.$name;

        if(!method_exists($package, $func)) {
            throw new flex\latex\UnexpectedValueException(
                'Package '.$package->getName().' does not have a parser for environment '.$name
            );
        }

        $output = call_user_func_array([$package, $func], []);
        $this->environment = $lastEnv;

        return $output;
    }

    public function moveToEnvironment($name) {
        $this->extractMatch('command', null, 'begin');
        $peek = $this->peek(1);

        if($peek->value != $name) {
            throw new iris\UnexpectedTokenException(
                'Environment '.$peek->value.' was found instead of '.$name,
                $peek
            );
        }

        return $this->parseCommand('begin');
    }


// Containers
    public function pushContainer(IContainerNode $container) {
        if($container !== $this->container) {
            $this->_containerStack[] = $container;
            $this->container = $container;

            return true;
        }

        return false;
    }

    public function popContainer($addToParent=true) {
        $output = array_pop($this->_containerStack);
        $i = count($this->_containerStack) - 1;

        if(isset($this->_containerStack[$i])) {
            $this->container = $this->_containerStack[$i];

            if($addToParent && $output && !$output->isEmpty()) {
                $this->container->push($output);
            }
        } else {
            $this->container = null;
        }

        return $output;
    }

    public function getContainerStack() {
        return $this->_containerStack;
    }

// Dump
    public function getDumpProperties() {
        return array_merge(
            [
                'commands' => count($this->_commands),
                'environments' => count($this->_environments),
                'token' => $this->token,
                'document' => $this->document
            ], 
            parent::getDumpProperties(),
            [
                'container' => $this->container
            ]
        );
    }
}