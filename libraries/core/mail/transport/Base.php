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
    	if($name !== null) {
    		if(!$class = self::getTransportClass($name)) {
    			$name = null;
    		}
    	}

    	if($name === null) {
    		if(0 && df\Launchpad::$application->isDevelopment()) {
    			$class = 'df\\core\\mail\\transport\\DevMail';
    		} else {
    			$config = core\mail\Config::getInstance();

    			if(!$class = self::getTransportClass($config->getDefaultTransport())) {
    				$class = 'df\\core\\mail\\transport\\Mail';
    			}
    		}
    	}

    	return new $class();
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
    	return [
    		'Mail' => 'PHP native mail()',
    		'DevMail' => 'Dummy transport stored in local database for testing purposes'
    	];
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
}