<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\validate\field;

use DecodeLabs\Disciple;
use DecodeLabs\Genesis;
use DecodeLabs\R7\Config\Recaptcha as RecaptchaConfig;
use DecodeLabs\R7\Legacy;
use df\core;
use df\spur;

class Recaptcha extends Base implements core\validate\IRecaptchaField
{
    public const KEY = 'g-recaptcha-response';

    protected $_name = 'recaptcha';
    protected $_secret = null;


    // Options
    public function __construct(core\validate\IHandler $handler, string $name)
    {
        parent::__construct($handler, $name);
        $this->_recordName = null;
    }

    public function setSecret($secret)
    {
        $this->_secret = $secret;
        return $this;
    }

    public function getSecret()
    {
        return $this->_secret;
    }


    // Validate
    public function validate()
    {
        $value = $this->data->getValue();

        if (
            $value === null &&
            Genesis::$kernel->getMode() === 'Http'
        ) {
            $value = Legacy::$http->getPostData()[self::KEY];
        }

        // Sanitize
        $value = $this->_sanitizeValue($value);
        $this->data->setValue($value);

        if ($this->_secret !== null) {
            $secret = $this->_secret;
        } else {
            $config = RecaptchaConfig::load();

            if (!$config->isEnabled()) {
                return $value;
            }

            $secret = (string)$config->getSecret();
        }

        if (empty($value)) {
            $this->addError('invalid', $this->validator->_(
                'Please confirm you are not a robot'
            ));
        } else {
            // Validate
            $ip = Disciple::getIp();
            $m = new spur\auth\recaptcha\Mediator($secret);

            try {
                $result = $m->verify($value, $ip);
            } catch (\Throwable $e) {
                core\logException($e);
                return $value;
            }

            if (!$result->isSuccess()) {
                $this->addError('invalid', $this->validator->_(
                    'Please confirm you are not a robot'
                ));
            }
        }


        // Finalize
        $this->_applyExtension($value);
        $this->data->setValue($value);

        return $value;
    }

    public function applyValueTo(&$record, $value)
    {
        if ($this->_recordName !== null) {
            parent::applyValueTo($record, $value);
        }

        return $this;
    }
}
