<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\auth\recaptcha;

use df\core;

class Config extends core\Config
{
    public const ID = 'Recaptcha';

    public function getDefaultValues(): array
    {
        return [
            'enabled' => false,
            'siteKey' => null,
            'secret' => null
        ];
    }

    public function isEnabled(bool $flag = null)
    {
        if ($flag !== null) {
            $this->values->enabled = $flag;
            return $this;
        }

        return (bool)$this->values['enabled']
            && isset($this->values['siteKey'])
            && isset($this->values['secret']);
    }

    public function setSiteKey($key)
    {
        $this->values->siteKey = $key;
        return $this;
    }

    public function getSiteKey()
    {
        return $this->values['siteKey'];
    }

    public function setSecret($secret)
    {
        $this->values->secret = $secret;
        return $this;
    }

    public function getSecret()
    {
        return $this->values['secret'];
    }
}
