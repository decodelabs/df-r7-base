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

class Recaptcha extends Base implements core\validate\IRecaptchaField {

    protected $_name = 'g-recaptcha-response';
    protected $_secret = null;


// Options
    public function __construct(core\validate\IHandler $handler, $name) {
        $this->validator = $handler;
        $this->_recordName = null;
    }

    public function setSecret($secret) {
        $this->_secret = $secret;
        return $this;
    }

    public function getSecret() {
        return $this->_secret;
    }


// Validate
    public function validate() {
        // Sanitize
        $value = $this->_sanitizeValue($this->data->getValue());
        $this->data->setValue($value);

        if($this->_secret !== null) {
            $secret = $this->_secret;
        } else {
            $config = spur\auth\recaptcha\Config::getInstance();

            if(!$config->isEnabled()) {
                return $value;
            }

            $secret = $config->getSecret();
        }

        if(!$length = $this->_checkRequired($value)) {
            return null;
        }



        // Validate
        $context = arch\Context::getCurrent();

        if($context->runner instanceof core\app\runner\Http) {
            $ip = $context->http->getIp();
        } else {
            $ip = null;
        }

        $m = new spur\auth\recaptcha\Mediator($secret);

        try {
            $result = $m->verify($value, $ip);
        } catch(\Throwable $e) {
            core\logException($e);
            return $value;
        }

        if(!$result->isSuccess()) {
            $this->addError('invalid', $this->validator->_(
                'Sorry, you don\'t appear to be a human!'
            ));
        }



        // Finalize
        $value = $this->_applyCustomValidator($value);
        $this->_applyExtension($value);
        $this->data->setValue($value);

        return $value;
    }

    public function applyValueTo(&$record, $value) {
        if($this->_recordName !== null) {
            parent::applyValueTo($record, $value);
        }

        return $this;
    }
}
