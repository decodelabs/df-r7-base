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

    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        $value = $this->_sanitizeValue($value);

        if($this->_secret !== null) {
            $secret = $this->_secret;
        } else {
            $config = spur\auth\recaptcha\Config::getInstance();

            if(!$config->isEnabled()) {
                return $this->_finalize($node, $value);
            }

            $secret = $config->getSecret();
        }

        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }

        $context = arch\Context::getCurrent();

        if($context->application instanceof core\application\Http) {
            $ip = $context->http->getIp();
        } else {
            $ip = null;
        }

        $m = new spur\auth\recaptcha\Mediator($secret);

        try {
            $result = $m->verify($value, $ip);
        } catch(\Exception $e) {
            core\logException($e);
            return $this->_finalize($node, $value);
        }

        if(!$result->isSuccess()) {
            $this->_applyMessage($node, 'invalid', $this->validator->_(
                'Sorry, you don\'t appear to be a human!'
            ));
        }

        return $this->_finalize($node, $value);
    }

    public function applyValueTo(&$record, $value) {
        if($this->_recordName !== null) {
            parent::applyValueTo($record, $value);
        }

        return $this;
    }
}