<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\spur\auth\recaptcha;

use DecodeLabs\Compass\Ip;

use df\spur;

interface IMediator extends spur\IGuzzleMediator
{
    public function setSecret(string $secret);
    public function getSecret();

    public function verify(
        string $key,
        Ip|string|null $ip = null
    ): IResult;
}

interface IResult
{
    public function isSuccess(): bool;
    public function getErrorCodes(): array;
}
