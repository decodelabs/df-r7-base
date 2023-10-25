<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flow\mail\transport;

use DecodeLabs\Dovetail\Repository;
use DecodeLabs\R7\Config\Mail as MailConfig;
use df\flow;
use df\spur;

class AmazonSes extends Base
{
    protected $_mediator;

    public static function getDescription()
    {
        return 'Amazon SES';
    }

    public static function getDefaultConfigValues()
    {
        return [
            'url' => null,
            'accessKey' => null,
            'secretKey' => null
        ];
    }

    public function __construct(Repository $settings = null)
    {
        if ($settings !== null) {
            $this->connect($settings['url'], $settings['accessKey'], $settings['secretKey']);
        }
    }

    public function connect($url, $accessKey, $secretKey)
    {
        $this->_mediator = new spur\mail\amazonSes\Mediator($url, $accessKey, $secretKey);
        return $this;
    }

    public function send(flow\mail\IMessage $message, flow\mime\IMultiPart $mime)
    {
        if (!$this->_mediator) {
            $config = MailConfig::load();
            $settings = $config->getTransportSettings('AmazonSes');
            $this->__construct($settings);
        }

        $this->_mediator->sendRawMessage($message, $mime);
        return true;
    }
}
