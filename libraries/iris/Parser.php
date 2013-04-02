<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris;

use df;
use df\core;
use df\iris;
    
abstract class Parser implements IParser {

    public $position = 0;
    public $token;
    public $currentNamespace;
    public $lastComment;
    public $unit;

    protected $_sourceUri;
    protected $_tokens;
    protected $_isStarted = false;
    protected $_processors = array();

    public function __construct($sourceUri, array $tokens=null, array $processors=null) {
        if($sourceUri instanceof ISourceUriAware) {
            $sourceUri = $sourceUri->getSourceUri();
        }

        $this->_sourceUri = $sourceUri;

        if($tokens !== null) {
            $this->setTokens($tokens);
        }

        if(!empty($processors)) {
            $this->setProcessors($processors);
        }
    }

// Source
    public function getSourceUri() {
        return $this->_sourceUri;
    }


// Tokens
    public function setTokens(array $tokens) {
        return $this->clearTokens()
            ->addTokens($tokens);
    }

    public function addTokens(array $tokens) {
        foreach($tokens as $token) {
            if(!$token instanceof IToken) {
                throw new InvalidArgumentException(
                    'Invalid token added to parser'
                );
            }

            $this->_tokens[] = $token;
        }
        return $this;
    }

    public function addToken(IToken $token) {
        $this->_tokens[] = $token;
        return $this;
    }

    public function getTokens() {
        return $this->_tokens;
    }

    public function hasToken(IToken $token) {
        foreach($this->_tokens as $test) {
            if($token === $test) {
                return true;
            }
        }

        return false;
    }

    public function removeToken(IToken $token) {
        if($this->_isStarted) {
            throw new LogicException(
                'Cannot remove tokens, parser already started'
            );
        }

        foreach($this->_tokens as $i => $test) {
            if($token === $test) {
                unset($this->_tokens[$i]);
                break;
            }
        }

        return $this;
    }

    public function clearTokens() {
        if($this->_isStarted) {
            throw new LogicException(
                'Cannot clear tokens, parser already started'
            );
        }

        $this->_tokens = array();
        return $this;
    }

    public function countTokens() {
        return count($this->_tokens);
    }


// Processors
    public function setProcessors(array $processors) {
        return $this->clearProcessors()
            ->addProcessors($processors);
    }

    public function addProcessors(array $processors) {
        foreach($processors as $processor) {
            if(!$processor instanceof IProcessor) {
                throw new InvalidArgumentException(
                    'Invalid processor detected'
                );
            }

            $this->addProcessor($processor);
        }

        return $this;
    }

    public function addProcessor(IProcessor $processor) {
        if($this->hasProcessor($processor)) {
            throw new LogicException(
                'Processor '.$processor->getName().' has already been added to the lexer'
            );
        }

        $this->_processors[$processor->getName()] = $processor;
        return $this;
    }

    public function hasProcessor($name) {
        if($name instanceof IProcessor) {
            $name = $name->getName();
        }

        $name = ucfirst($name);

        return isset($this->_processors[$name]);
    }

    public function getProcessor($name) {
        $name = ucfirst($name);

        if(isset($this->_processors[$name])) {
            return $this->_processors[$name];
        }
    }

    public function removeProcessor($name) {
        if($name instanceof IProcessor) {
            $name = $name->getName();
        }

        $name = ucfirst($name);

        unset($this->_processors[$name]);
        return $this;
    }

    public function clearProcessors() {
        $this->_processors = array();
        return $this;
    }

    public function __get($member) {
        return $this->getProcessor($member);
    }


// Parse
    public function parse() {
        if($this->_isStarted) {
            throw new LogicException(
                'Parser has already been started'
            );
        }

        foreach($this->_processors as $processor) {
            $processor->initialize($this);
        }

        $this->_isStarted = true;
        $this->unit = new iris\map\Unit(new Location($this->getSourceUri()));

        if(!isset($this->_tokens[$this->position])) {
            return $this->unit;
        }

        $this->_setCurrentToken();
        $this->parseRoot();

        return $this->unit;
    }

    public function getLastCommentBody() {
        if($this->lastComment) {
            return $this->lastComment->value;
        }
    }


    public function extract($count=1) {
        $count = (int)$count;

        if($count < 1) {
            $count = 1;
        }

        if($count == 1) {
            $this->position++;
            $output = $this->token;
        } else {
            $output = array_slice($this->_tokens, $this->position, $count);
            $this->position += $count;
        }

        $this->_setCurrentToken();

        return $output;
    }

    public function extractIf($ids, $limit=1) {
        $limit = (int)$limit;

        if($limit < 1) {
            $limit = 1;
        }

        if($limit === 1) {
            if($this->token->is($ids)) {
                $output = $this->extract();
            } else {
                $output = null;
            }
        } else {
            $output = array();

            for($i = 0; $i < $limit; $i++) {
                if(!$this->token->is($ids)) {
                    break;
                }

                $output[] = $this->extract();
            }
        }

        return $output;
    }

    public function extractSequence($ids) {
        $sequence = func_get_args();
        $length = count($sequence);
        $test = array_slice($this->_tokens, $this->position, $length);
        $output = array();

        foreach($sequence as $i => $ids) {
            if(!isset($test[$i])) {
                throw new UnexpectedTokenException(
                    'Sequence could not be extracted, reached end of token stream',
                    array_pop($test)
                );
            }

            $token = $test[$i];

            if(!$token->is($ids)) {
                throw new UnexpectedTokenException(
                    'Sequence could not be extracted',
                    $token
                );
            }

            $output[] = $token;
        }

        $this->position += $length;
        return $output;
    }

    public function extractMatch($type, $subType=null, $value=null) {
        if($this->token->matches($type, $subType, $value)) {
            return $this->extract(1);
        } else {
            $id = ucfirst($type);

            if($subType !== null) {
                $id .= '/'.$subType;
            }

            if($value !== null) {
                $id .= ' '.trim($value);
            }

            throw new UnexpectedTokenException(
                $id.' could not be extracted',
                $this->token
            );
        }
    }

    public function extractIfMatch($type, $subType=null, $value=null) {
        if($this->token->matches($type, $subType, $value)) {
            return $this->extract(1);
        }
    }

    public function extractStatementEnd() {
        return $this->extractMatch('symbol', null, ';');
    }

    public function extractWord() {
        if(!$output = $this->extractIf(['word', 'keyword'])) {
            throw new UnexpectedTokenException(
                'Word could not be extracted',
                $this->token
            );
        }

        return $output;
    }

    public function rewind($count=1) {
        $count = (int)$count;

        if($count < 1) {
            $count = 1;
        }


        if($this->position - $count < 0) {
            throw new RuntimeException(
                'Cannot rewind '.$count.' places'
            );
        }

        $this->position -= $count;
        $this->_setCurrentToken();

        return $this;
    }

    protected function _setCurrentToken() {
        $this->token = isset($this->_tokens[$this->position]) ?
            $this->_tokens[$this->position] : 
            null;

        if($this->token && $this->token->matches('comment')) {
            $this->lastComment = $this->token;
            $this->extract();
        } else if(@$this->_tokens[$this->position - 1] !== $this->lastComment) {
            $this->lastComment = null;
        }
    }

    public function peek($offset=1, $length=1) {
        $length = (int)$length;

        if($length < 1) {
            $length = 1;
        }

        if($length == 1) {
            $position = $this->position + $length;

            if(isset($this->_tokens[$position])) {
                $output = $this->_tokens[$position];
            } else {
                $output = null;
            }
        } else {
            $output = array_slice($this->_tokens, $this->position, $length);
        }

        return $output;
    }

    public function purge() {
        if($this->position > 0) {
            $this->_tokens = array_slice($this->_tokens, $this->position);
            $this->position = 0;

            $this->_setCurrentToken();
        }

        return $this;
    }
}