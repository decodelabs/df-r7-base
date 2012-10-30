<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\parser;

use df;
use df\core;
use df\iris;

// Exceptions
interface IException {}

class RuntimeException extends \RuntimeException implements IException {}
class LogicException extends \LogicException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}

class UnexpectedValueException extends \UnexpectedValueException implements IException, core\IDumpable {

    protected $_token;
    
    public function __construct($message, iris\lexer\IToken $token=null) {
        parent::__construct($message);
        
        if($token) {
            $this->_token = clone $token;
        }
    }
    
    public function getToken() {
        return $this->_token;
    }

    public function getDumpProperties() {
        return $this->_token;
    }
}    



// Interfaces
interface IParser {
    public function getSourceUri();

    public function setTokens(array $tokens);
    public function addTokens(array $tokens);
    public function addToken(iris\lexer\IToken $token);
    public function getTokens();
    public function hasToken(iris\lexer\IToken $token);
    public function removeToken(iris\lexer\IToken $token);
    public function clearTokens();
    public function countTokens();

    public function setProcessors(array $processors);
    public function addProcessors(array $processors);
    public function addProcessor(IProcessor $processor);
    public function hasProcessor($name);
    public function getProcessor($name);
    public function removeProcessor($name);
    public function clearProcessors();

    public function parse();
    public function parseRoot();
    public function getLastCommentBody();

    public function extract($count=1);
    public function extractIf($ids, $limit=1);
    public function extractSequence($ids);
    public function extractMatch($type, $subType=null, $value=null);
    public function extractIfMatch($type, $subType=null, $value=null);
    public function extractStatementEnd();
    public function extractWord();
    public function rewind($count=1);
    public function peek($offset=1, $length=1);
    public function purge();
}


interface IProcessor {
    public function getName();
    public function initialize(IParser $parser);
}