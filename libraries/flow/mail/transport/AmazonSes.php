<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mail\transport;

use df;
use df\core;
use df\flow;
use df\spur;
    
class AmazonSes extends Base {

    protected $_mediator;

    public static function getDescription() {
        return 'Amazon SES';
    }

    public static function getDefaultConfigValues() {
        return [
            'url' => null,
            'accessKey' => null,
            'secretKey' => null
        ];
    }

    public function __construct(core\collection\ITree $settings=null) {
        if($settings !== null) {
            $this->connect($settings['url'], $settings['accessKey'], $settings['secretKey']);
        }
    }

    public function connect($url, $accessKey, $secretKey) {
        $this->_mediator = new spur\mail\amazonSes\Mediator($url, $accessKey, $secretKey);
        return $this;
    }

    public function send(flow\mail\IMessage $message) {
        if(!$this->_mediator) {
            $config = flow\mail\Config::getInstance();
            $settings = $config->getTransportSettings('AmazonSes');
            $this->__construct($settings);
        }

        $this->_mediator->sendMessage($message);
        return true;
    }
}