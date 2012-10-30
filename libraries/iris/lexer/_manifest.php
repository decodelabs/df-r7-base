<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\lexer;

use df;
use df\core;
use df\iris;

// Exceptions
interface IException {}

class RuntimeException extends \RuntimeException implements IException {}
class LogicException extends \LogicException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}

class UnexpectedValueException extends \UnexpectedValueException implements IException, core\IDumpable {

    protected $_location;
    
    public function __construct($message, ILocation $location=null) {
        parent::__construct($message);
        
        if($location) {
            $this->_location = clone $location;
        }
    }
    
    public function getLocation() {
        return $this->_location;
    }

    public function getDumpProperties() {
        return $this->_location;
    }
}


// Interfaces    
interface ISourceUri extends core\uri\IUrl {}

interface ISourceUriAware {
    public function getSourceUri();
}

interface ISourceUriProvider {
    public function setSourceUri($uri);
}

trait TSourceUriProvider {

    protected $_sourceUri;

    public function setSourceUri($uri) {
        $this->_sourceUri = SourceUri::factory($uri);
        return $this;
    }

    public function getSourceUri() {
        return $this->_sourceUri;
    }
}

interface ILocationProxy {
    public function getLine();
    public function getColumn();
}

interface ILocationProvider {
    public function getLocation();
}

interface ILocationProxyProvider extends ILocationProxy, ILocationProvider, ISourceUriAware {}

trait TLocationProvider {

    public function getLocation() {
        return new Location($this->getSourceUri(), $this->getLine(), $this->getColumn());
    }
}

interface ILocation extends ILocationProxy, ILocationProvider, ISourceUriProvider {
    public function setLine($line);
    public function advanceLine($count=1);
    public function setColumn($column);
    public function advanceColumn($count=1);
}


trait TLocation {

    protected $_line = 1;
    protected $_column = 1;

    public function setLine($line) {
        $this->_line = (int)$line;
        return $this;
    }

    public function getLine() {
        return $this->_line;
    }

    public function advanceLine($count=1) {
        $this->_line += (int)$count;
        return $this;
    }

    public function setColumn($column) {
        $this->_column = (int)$column;
        return $this;
    }

    public function getColumn() {
        return $this->_column;
    }

    public function advanceColumn($count=1) {
        $this->_column += (int)$count;
        return $this;
    }
}

interface IProcessor {
    public function getName();
    public function getWeight();
    public function initialize(ILexer $lexer);
    public function check(ILexer $lexer);
    public function run(ILexer $lexer);
}



interface ILexer extends ILocationProxyProvider {
    public function getSource();

    public function setProcessors(array $processors);
    public function addProcessors(array $processors);
    public function addProcessor(IProcessor $processor);
    public function hasProcessor($name);
    public function getProcessor($name);
    public function removeProcessor($name);
    public function clearProcessors();

    public function tokenize();
    public function extractToken();
    public function newToken($type, $value=null);
    public function getTokens();

    public function extract($length=1);
    public function peek($offset, $length);
    public function substring($position, $length);
    public function extractAlpha();
    public function extractAlphanumeric();
    public function extractNumeric();
    public function extractWhitespace();
    public function extractRegexRange($regex);
}



interface ISource extends ISourceUriAware, core\string\IEncodingAware {
    public function substring($start, $length=1);
}


interface IToken extends ILocationProxyProvider {
    public function getType();
    public function getSubType();
    public function getTypeString();
    public function getValue();
    public function getWhitespace();

    public function isAfterWhitespace();
    public function isAfterNewline();
    public function isOnNextLine();

    public function eq(IToken $token);
    public function is($compId);
    public function matches($type, $subType=null, $value=null);
}