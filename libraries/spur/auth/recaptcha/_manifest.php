<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\auth\recaptcha;

use df;
use df\core;
use df\spur;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface IMediator extends spur\IHttpMediator {
    public function setSecret(string $secret);
    public function getSecret();

    public function verify(string $key, $ip=null): IResult;
}

interface IResult {
    public function isSuccess(): bool;
    public function getErrorCodes(): array;
}
