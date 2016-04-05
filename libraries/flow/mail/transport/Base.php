<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mail\transport;

use df;
use df\core;
use df\flow;

abstract class Base implements flow\mail\ITransport {

    public static function getAllDefaultConfigValues() {
        $output = [];

        foreach(df\Launchpad::$loader->lookupClassList('flow/mail/transport') as $name => $class) {
            $output[$name] = $class::getDefaultConfigValues();
        }

        return $output;
    }

    public static function getDefaultConfigValues() {
        return [];
    }

    public static function factory($name) {
        if(!$class = self::getTransportClass($name)) {
            throw new flow\mail\RuntimeException(
                'Mail transport '.$name.' could not be found'
            );
        }

        $config = flow\mail\Config::getInstance();
        $settings = $config->getTransportSettings($name);

        return new $class($settings);
    }

    public static function getTransportClass($name) {
        $class = 'df\\flow\\mail\\transport\\'.$name;

        if(class_exists($class)) {
            return $class;
        }

        return null;
    }

    public static function isValidTransport($name) {
        return (bool)self::getTransportClass($name);
    }

    public static function getAvailableTransports() {
        $output = [];

        foreach(df\Launchpad::$loader->lookupClassList('flow/mail/transport') as $name => $class) {
            $output[$name] = $class::getDescription();
        }

        return $output;
    }

    protected function _prepareLegacyMessage(flow\mail\ILegacyMessage $message) {
        $config = flow\mail\Config::getInstance();

        if(!$isFromValid = $message->isFromAddressValid()) {
            if(!$message->isFromAddressSet()) {
                $message->setFromAddress($config->getDefaultAddress());
                $isFromValid = $message->isFromAddressValid();

                if($isFromValid && !strlen($message->getFromAddress()->getName())) {
                    $message->getFromAddress()->setName(df\Launchpad::$application->getName());
                }
            }

            if(!$isFromValid) {
                throw new flow\mail\RuntimeException(
                    'The mail is missing a valid from address'
                );
            }
        }

        if(!$message->getReturnPath() && ($returnPath = $config->getDefaultReturnPath())) {
            $message->setReturnPath($returnPath);
        }

        if(!$message->hasToAddresses()) {
            throw new flow\mail\RuntimeException(
                'The mail is missing a valid to address'
            );
        }

        if(!$message->isPrivate() && count($bcc = $config->getCatchAllBCCAddresses())) {
            foreach($bcc as $address) {
                $address = flow\mail\Address::factory($address);

                if(!$message->hasToAddress($address)) {
                    try {
                        $message->addBCCAddress($address);
                    } catch(\Exception $e) {}
                }
            }
        }

        $message->prepareHeaders();
        $headers = $message->getHeaders();

        if(!$headers->has('message-id')) {
            $domain = null;

            if(isset($_SERVER['SERVER_NAME'])) {
                $domain = $_SERVER['SERVER_NAME'];
            } else {
                $config = core\application\http\Config::getInstance();

                if($url = $config->getRootUrl()) {
                    $domain = df\link\http\Url::factory($url)->getDomain();
                }
            }

            if($domain) {
                $headers->set('message-id', sprintf(
                    "<%s.%s@%s>",
                    base_convert(microtime(), 10, 36),
                    base_convert(bin2hex(openssl_random_pseudo_bytes(8)), 16, 36),
                    $domain
                ));
            }
        }
    }

    public function __construct(core\collection\ITree $settings=null) {}

    public static function getName() {
        $parts = explode('\\', get_called_class());
        return array_pop($parts);
    }
}