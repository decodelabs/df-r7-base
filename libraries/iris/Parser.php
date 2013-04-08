<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris;

use df;
use df\core;
use df\iris;
    
abstract class Parser implements IParser, core\IDumpable {

    public $position = 0;
    public $token;
    public $currentNamespace;
    public $lastComment;
    public $unit;

    protected $_tokens = array();
    protected $_extractBuffer = array();
    protected $_extractBufferSize = 100;
    protected $_isStarted = false;
    protected $_hasLastToken = false;
    protected $_processors = array();
    protected $_lexer;

    public function __construct(ILexer $lexer, array $processors=null) {
        $this->_lexer = $lexer;

        if(!empty($processors)) {
            $this->setProcessors($processors);
        }
    }

// Lexer
    public function getLexer() {
        return $this->_lexer;
    }

    public function getSourceUri() {
        return $this->_lexer->getSourceUri();
    }

// Buffer
    public function setExtractBufferSize($size) {
        $this->_extractBufferSize = (int)$size;
        return $this;
    }

    public function getExtractBufferSize() {
        return $this->_extractBufferSize;
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

        if($this->_isStarted) {
            $processor->initialize($this);
        }

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
        
        $this->_isStarted = true;

        foreach($this->_processors as $processor) {
            $processor->initialize($this);
        }

        $this->unit = new iris\map\Unit(new Location($this->getSourceUri()));

        $this->_setCurrentToken();
        $this->parseRoot();

        $this->unit->normalize();

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
            $output = array_shift($this->_tokens);
        } else {
            $output = array_slice($this->_tokens, 0, $count);
            $this->_tokens = array_slice($this->_tokens, $count);
            $this->position += $count;
        }

        $this->_bufferTokens($output);
        $this->_setCurrentToken();

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

    public function extractValue($value) {
        if($this->token->isValue($value)) {
            return $this->extract(1);
        } else {
            throw new UnexpectedTokenException(
                'Value \''.$value.'\' could not be extracted',
                $this->token
            );
        }
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

    public function extractIfValue($values, $limit=1) {
        $limit = (int)$limit;

        if($limit < 1) {
            $limit = 1;
        }

        if($limit === 1) {
            if($this->token->isValue($values)) {
                $output = $this->extract();
            } else {
                $output = null;
            }
        } else {
            $output = array();

            for($i = 0; $i < $limit; $i++) {
                if(!$this->token->isValue($values)) {
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
        $test = array_slice($this->_tokens, 0, $length);
        $output = array();

        $this->_importTokens($length);

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

        $this->extract($length);

        return $output;
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

        if($count > $this->_extractBufferSize) {
            throw new RuntimeException(
                'Cannot rewind further than extractBufferSize ('.$this->_extractBufferSize.')'
            );
        }

        if($count > count($this->_extractBuffer)) {
            throw new RuntimeException(
                'Cannot rewind '.$count.' places, buffer does not contain that many entries'
            );
        }

        $test = $this->_extractBuffer;
        
        for($i = 0; $i < $count; $i++) {
            $testToken = array_pop($test);

            if(!$testToken) {
                throw new RuntimeException(
                    'Cannot rewind '.$count.' places, buffer does not contain that many entries'
                );
            }

            if($testToken->matches('comment')) {
                $count++;
            }
        }

        $this->position -= $count;
        $extractList = array_slice($this->_extractBuffer, -$count);
        $this->_tokens = array_merge($extractList, $this->_tokens);
        $this->_extractBuffer = array_slice($this->_extractBuffer, 0, -$count);
        $this->_setCurrentToken();

        return $this;
    }

    protected function _setCurrentToken() {
        if(empty($this->_tokens) && !$this->_hasLastToken) {
            $this->_importTokens();
        }

        $this->token = isset($this->_tokens[0]) ?
            $this->_tokens[0] : 
            null;

        if($this->token && $this->token->matches('comment')) {
            $comment = $this->token;
            $this->extract();
            $this->lastComment = $comment;
        } else if(@$this->_extractBuffer[count($this->_extractBufferSize) - 1] !== $this->lastComment) {
            $this->lastComment = null;
        }
    }

    protected function _importTokens($count=10) {
        if($this->_hasLastToken) {
            return;
        }

        while($count > 0) {
            $token = $this->_lexer->extractToken();

            if(!$token instanceof IToken) {
                $this->_hasLastToken = true;
                return;
            }

            $this->_tokens[] = $token;
            $count--;

            if($token->is('eof')) {
                $this->_hasLastToken = true;
                return;
            }
        }
    }

    protected function _bufferTokens($tokens) {
        if(!is_array($tokens)) {
            $tokens = func_get_args();
        }

        foreach($tokens as $token) {
            if(is_array($token)) {
                $this->_bufferTokens($token);
            } else if($token instanceof IToken) {
                $this->_extractBuffer[] = $token;
            }
        }

        while(count($this->_extractBuffer) > $this->_extractBufferSize) {
            array_shift($this->_extractBuffer);
        }
    }

    public function peek($offset=1, $length=1) {
        $length = (int)$length;
        $offset = (int)$offset;

        if(count($this->_tokens) < $length + $offset) {
            $this->_importTokens($length + $offset);
        }

        if($length < 1) {
            $length = 1;
        }

        if($length == 1) {
            if(isset($this->_tokens[1])) {
                $output = $this->_tokens[1];
            } else {
                $output = null;
            }
        } else {
            $output = array_slice($this->_tokens, $offset, $length);
        }

        return $output;
    }

    public function peekSequence($ids) {
        $sequence = func_get_args();
        $length = count($sequence);
        $test = array_slice($this->_tokens, 0, $length);
        $output = array();

        $this->_importTokens($length);

        foreach($sequence as $i => $ids) {
            if(!isset($test[$i])) {
                return false;
            }

            $token = $test[$i];

            if(!$token->is($ids)) {
                return false;
            }

            $output[] = $token;
        }

        return $output;
    }

// Dump
    public function getDumpProperties() {
        return [
            'token' => $this->token,
            'position' => $this->position,
            'unit' => $this->unit,
            'tokens' => $this->_tokens,
            'extractBuffer' => count($this->_extractBuffer),
            'processors' => $this->_processors,
            'lexer' => $this->_lexer
        ];
    }
}