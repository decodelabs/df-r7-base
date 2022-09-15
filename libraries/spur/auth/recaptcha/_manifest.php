<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\spur\auth\recaptcha;

use df;
use df\core;
use df\spur;

use DecodeLabs\Compass\Ip;

interface IMediator extends spur\IGuzzleMediator
{
    public function setSecret(string $secret);
    public function getSecret();

    public function verify(
        string $key,
        Ip|string|null $ip=null
    ): IResult;
}

interface IResult
{
    public function isSuccess(): bool;
    public function getErrorCodes(): array;
}
