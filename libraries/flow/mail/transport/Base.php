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

        foreach(df\Launchpad::$loader->lookupFileList('flow/mail/transport', 'php') as $name => $path) {
            $name = substr($name, 0, -4);

            if(in_array($name, ['Base', '_manifest'])) {
                continue;
            }

            $class = 'df\\flow\\mail\\transport\\'.$name;

            if(!class_exists($class)) {
                continue;
            }

            $output[$name] = $class::getDefaultConfigValues();
        }

        return $output;
    }

    public static function getDefaultConfigValues() {
        return [];
    }

    public static function factory($name=null) {
        $settings = null;

        if($name !== null) {
            if(!$class = self::getTransportClass($name)) {
                $name = null;
            }
        }

        if($name === null) {
            $name = self::getDefaultTransportName();
            $class = self::getTransportClass($name);
            $config = flow\mail\Config::getInstance();
            $settings = $config->getDefaultTransportSettings($name);
        }

        return new $class($settings);
    }

    public static function getDefaultTransportName() {
        if(df\Launchpad::$application->isDevelopment()) {
            return 'DevMail';
        } else {
            $config = flow\mail\Config::getInstance();
            $name = $config->getDefaultTransport();

            if(!self::getTransportClass($name)) {
                $name = 'Mail';
            }

            return $name;
        }
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
        $output = array();

        foreach(df\Launchpad::$loader->lookupFileList('flow/mail/transport', 'php') as $name => $path) {
            $name = substr($name, 0, -4);

            if(in_array($name, ['Base', '_manifest'])) {
                continue;
            }

            $class = 'df\\flow\\mail\\transport\\'.$name;

            if(!class_exists($class)) {
                continue;
            }

            $output[$name] = $class::getDescription();
        }

        return $output;
    }

    protected function _prepareMessage(flow\mail\IMessage $message) {
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
    }

    public function __construct(core\collection\ITree $settings=null) {}

    public static function getName() {
        $parts = explode('\\', get_called_class());
        return array_pop($parts);
    }
}