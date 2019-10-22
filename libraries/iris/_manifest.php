<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris;

use df;
use df\core;
use df\iris;
use df\flex;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

// Exceptions
interface IException
{
}

class RuntimeException extends \RuntimeException implements IException
{
}
class LogicException extends \LogicException implements IException
{
}
class InvalidArgumentException extends \InvalidArgumentException implements IException
{
}

class UnexpectedValueException extends \UnexpectedValueException implements
    ILocationProvider,
    IException,
    Inspectable
{
    protected $_location;

    public function __construct($message, ILocation $location=null)
    {
        parent::__construct($message);

        if ($location) {
            $this->_location = clone $location;
        }
    }

    public function getLocation()
    {
        return $this->_location;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setSingleValue($inspector($this->_location));
    }
}

class UnexpectedCharacterException extends UnexpectedValueException
{
}

class UnexpectedTokenException extends \UnexpectedValueException implements
    ILocationProvider,
    IException,
    Inspectable
{
    protected $_token;

    public function __construct($message, Token $token=null)
    {
        parent::__construct($message);

        if ($token) {
            $this->_token = clone $token;
        }
    }

    public function getToken()
    {
        return $this->_token;
    }

    public function getLocation()
    {
        if ($this->_token) {
            return $this->_token->getLocation();
        }
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setSingleValue($inspector($this->_token));
    }
}


// Interfaces
interface ISourceUri extends core\uri\IUrl
{
}

interface ISourceUriAware
{
    public function getSourceUri();
}

interface ISourceUriProvider
{
    public function setSourceUri($uri);
}

trait TSourceUriProvider
{
    protected $_sourceUri;

    public function setSourceUri($uri)
    {
        $this->_sourceUri = SourceUri::factory($uri);
        return $this;
    }

    public function getSourceUri()
    {
        return $this->_sourceUri;
    }
}

interface ISource extends ISourceUriAware, flex\IEncodingAware
{
    public function substring($start, $length=1);
}

interface ILocationProxy
{
    public function getLine(): ?int;
    public function getColumn();
}

interface ILocationProvider
{
    public function getLocation();
}

interface ILocationProxyProvider extends ILocationProxy, ILocationProvider, ISourceUriAware
{
}

trait TLocationProvider
{
    public function getLocation()
    {
        return new Location($this->getSourceUri(), $this->getLine(), $this->getColumn());
    }
}

interface ILocation extends ILocationProxy, ILocationProvider, ISourceUriProvider
{
    public function setLine($line);
    public function advanceLine($count=1);
    public function setColumn($column);
    public function advanceColumn($count=1);
}


trait TLocation
{
    protected $_line = 1;
    protected $_column = 1;

    public function setLine($line)
    {
        $this->_line = (int)$line;
        return $this;
    }

    public function getLine(): ?int
    {
        return $this->_line;
    }

    public function advanceLine($count=1)
    {
        $this->_line += (int)$count;
        return $this;
    }

    public function setColumn($column)
    {
        $this->_column = (int)$column;
        return $this;
    }

    public function getColumn()
    {
        return $this->_column;
    }

    public function advanceColumn($count=1)
    {
        $this->_column += (int)$count;
        return $this;
    }
}

interface IScanner
{
    public function getName(): string;
    public function getWeight();
    public function initialize(Lexer $lexer);
    public function check(Lexer $lexer);
    public function run(Lexer $lexer);
}



interface IParser
{
    public function getSourceUri();
    public function getLexer();
    public function getUnit();

    public function setExtractBufferSize($size);
    public function getExtractBufferSize();

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

    public function isStarted();
    public function hasRun();

    public function extract($count=1);
    public function extractMatch($type, $subType=null, $value=null);
    public function extractValue($value);
    public function extractIf($ids, $limit=1);
    public function extractIfValue($values, $limit=1);
    public function extractIfMatch($type, $subType=null, $value=null);
    public function extractSequence(...$sequence);
    public function extractStatementEnd();
    public function extractWord();
    public function rewind($count=1);
    public function getLastToken();
    public function peek($offset=1, $length=1);
    public function peekSequence(...$sequence);
}


interface IProcessor
{
    public function getName(): string;
    public function initialize(IParser $parser);
}


interface ITranslator
{
    public function translate();
}
