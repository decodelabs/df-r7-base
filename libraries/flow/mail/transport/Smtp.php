<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mail\transport;

use df;
use df\core;
use df\flow;
use df\halo;

class Smtp extends Base {

    protected $_mediator;

    public static function getDescription() {
        return 'External SMTP connection';
    }

    public static function getDefaultConfigValues() {
        return [
            'dsn' => null,
            'username' => null,
            'password' => null
        ];
    }

    public function __construct(core\collection\ITree $settings=null) {
        if($settings !== null) {
            if(!isset($settings['dsn'])) {
                throw new flow\mail\InvalidArgumentException(
                    'SMTP settings does not include DSN'
                );
            }

            if(!isset($settings['username'])) {
                throw new flow\mail\InvalidArgumentException(
                    'SMTP settings does not include username'
                );
            }

            if(!isset($settings['password'])) {
                throw new flow\mail\InvalidArgumentException(
                    'SMTP settings does not include password'
                );
            }

            $this->connect($settings['dsn'], $settings['username'], $settings['password']);
        }
    }

    public function connect($dsn, $username, $password) {
        if($this->_mediator) {
            $this->_mediator->quit();
        }

        $this->_mediator = new halo\protocol\smtp\Mediator($dsn);
        $this->_mediator->login($username, $password);

        return $this;
    }

    public function send(flow\mail\IMessage $message) {
        if(!$this->_mediator) {
            $config = flow\mail\Config::getInstance();
            $settings = $config->getDefaultTransportSettings('Smtp');
            $this->__construct($settings);
        }

        $this->_mediator->reset();
        $this->_mediator->sendFromAddress($message->getFromAddress());

        foreach($message->getToAddresses() as $address) {
            $this->_mediator->sendRecipientAddress($address);
        }

        $this->_mediator->sendData($message->toString());
        return true;
    }
}