<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\smtp;

use df;
use df\core;
use df\link;

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

class UnexpectedValueException extends \UnexpectedValueException implements IException, Inspectable
{
    protected $_value;

    public function __construct($message, $value=null)
    {
        parent::__construct($message);

        $this->_value = $value;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setValues([$inspector($this->_value)])
            ->setShowKeys(false);
    }
}


// Interfaces
interface IMediator
{
    const CRAMMD5 = 'crammd5';
    const LOGIN = 'login';
    const PLAIN = 'plain';

    public function connect($dsn);
    public function getConnectionId();
    public function sendRequest($request, $responseCode);

    public function login($user, $password, $authType=IMediator::LOGIN);
    public function sendFromAddress($address);
    public function sendRecipientAddress($address);
    public function sendData($data);
    public function verifyUser($user);
    public function reset();
    public function noOp();
    public function quit();
}




class Response
{
    public $info = [];
    public $code;
    public $message;

    public function isValid(): bool
    {
        return empty($this->error);
    }
}
