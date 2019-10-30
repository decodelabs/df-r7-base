<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\auth\recaptcha;

use df;
use df\core;
use df\spur;
use df\link;

use DecodeLabs\Glitch;

class Mediator implements IMediator
{
    use spur\TGuzzleMediator;

    const ENDPOINT = 'https://www.google.com/recaptcha/api/siteverify';

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

    public function verify(string $key, $ip=null): IResult
    {
        $response = $this->requestJson('post', self::ENDPOINT, [
            'secret' => $this->_secret,
            'response' => $key,
            'remoteIp' => $ip
        ]);

        if (!$response['success']) {
            foreach ($response->{'error-codes'} as $node) {
                switch ((string)$node) {
                    case 'invalid-input-response':
                    case 'missing-input-response':
                        throw Glitch::ERuntime('Invalid input response: '.$key);

                    case 'invalid-input-secret':
                    case 'missing-input-secret':
                        throw Glitch::ERuntime('Invalid secret: '.$this->_secret);
                }
            }
        }

        return Result::factory($response);
    }
}
