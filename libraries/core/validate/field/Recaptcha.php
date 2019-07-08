<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
use df\arch;
use df\spur;

class Recaptcha extends Base implements core\validate\IRecaptchaField
{
    const KEY = 'g-recaptcha-response';

    protected $_name = 'recaptcha';
    protected $_secret = null;


    // Options
    public function __construct(core\validate\IHandler $handler, string $name)
    {
        $this->validator = $handler;
        $this->_recordName = null;
        $this->_name = $name;
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
        $context = arch\Context::getCurrent();
        $value = $this->data->getValue();

        if ($value === null && $context->runner instanceof core\app\runner\Http) {
            $value = $context->http->post[self::KEY];
        }

        // Sanitize
        $value = $this->_sanitizeValue($value);
        $this->data->setValue($value);

        if ($this->_secret !== null) {
            $secret = $this->_secret;
        } else {
            $config = spur\auth\recaptcha\Config::getInstance();

            if (!$config->isEnabled()) {
                return $value;
            }

            $secret = $config->getSecret();
        }

        if (empty($value)) {
            $this->addError('invalid', $this->validator->_(
                'Please confirm you are not a robot'
            ));
        } else {
            // Validate
            if ($context->runner instanceof core\app\runner\Http) {
                $ip = $context->http->getIp();
            } else {
                $ip = null;
            }

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
