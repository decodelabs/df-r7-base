<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\spur\auth\recaptcha;

use DecodeLabs\Compass\Ip;

use DecodeLabs\Exceptional;
use df\spur;

class Mediator implements IMediator
{
    use spur\TGuzzleMediator;

    public const ENDPOINT = 'https://www.google.com/recaptcha/api/siteverify';

    protected $_secret;

    public function __construct(string $secrect)
    {
        $this->setSecret($secrect);
    }

    public function setSecret(string $secret)
    {
        $this->_secret = $secret;
        return $this;
    }

    public function getSecret()
    {
        return $this->_secret;
    }

    public function verify(
        string $key,
        Ip|string|null $ip = null
    ): IResult {
        $response = $this->requestJson('post', self::ENDPOINT, [
            'secret' => $this->_secret,
            'response' => $key,
            'remoteIp' => $ip ? (string)$ip : null
        ]);

        if (!$response['success']) {
            foreach ($response->{'error-codes'} as $node) {
                switch ((string)$node) {
                    case 'invalid-input-response':
                    case 'missing-input-response':
                        throw Exceptional::Runtime(
                            'Invalid input response: ' . $key
                        );

                    case 'invalid-input-secret':
                    case 'missing-input-secret':
                        throw Exceptional::Runtime(
                            'Invalid secret: ' . $this->_secret
                        );
                }
            }
        }

        return Result::factory($response);
    }
}
