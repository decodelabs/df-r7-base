<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\mail\transport;

use df;
use df\core;
    
abstract class Base implements core\mail\ITransport {

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
            $config = core\mail\Config::getInstance();
            $settings = $config->getDefaultTransportSettings($name);
        }

        return new $class($settings);
    }

    public static function getDefaultTransportName() {
        if(df\Launchpad::$application->isDevelopment()) {
            return 'DevMail';
        } else {
            $config = core\mail\Config::getInstance();
            $name = $config->getDefaultTransport();

            if(!self::getTransportClass($name)) {
                $name = 'Mail';
            }

            return $name;
        }
    }

    public static function getTransportClass($name) {
        $class = 'df\\core\\mail\\transport\\'.$name;

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

        foreach(df\Launchpad::$loader->lookupFileList('core/mail/transport', 'php') as $name => $path) {
            $name = substr($name, 0, -4);

            if(in_array($name, ['Base', '_manifest'])) {
                continue;
            }

            $class = 'df\\core\\mail\\transport\\'.$name;

            if(!class_exists($class)) {
                continue;
            }

            $output[$name] = $class::getDescription();
        }

        return $output;
    }

    protected function _prepareMessage(core\mail\IMessage $message) {
        $config = core\mail\Config::getInstance();

        if(!$isFromValid = $message->isFromAddressValid()) {
            if(!$message->isFromAddressSet()) {
                $message->setFromAddress($config->getDefaultAddress());
                $isFromValid = $message->isFromAddressValid();

                if($isFromValid && !strlen($message->getFromAddress()->getName())) {
                    $message->getFromAddress()->setName(df\Launchpad::$application->getName());
                }
            }

            if(!$isFromValid) {
                throw new core\mail\RuntimeException(
                    'The mail is missing a valid from address'
                );
            }
        }

        if(!$message->hasToAddresses()) {
            throw new core\mail\RuntimeException(
                'The mail is missing a valid to address'
            );
        }

        if(!$message->isPrivate() && count($bcc = $config->getCatchAllBCCAddresses())) {
            foreach($bcc as $address) {
                $address = core\mail\Address::factory($address);

                if(!$message->hasToAddress($address)) {
                    try {
                        $message->addBCCAddress($address);
                    } catch(\Exception $e) {}
                }
            }
        }

        $message->prepareHeaders();
    }

    public function __construct(array $settings=null) {}

    public static function getName() {
        $parts = explode('\\', get_called_class());
        return array_pop($parts);
    }
}