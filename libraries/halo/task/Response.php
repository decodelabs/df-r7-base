<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\task;

use df;
use df\core;
use df\halo;
    
class Response extends core\io\Multiplexer implements IResponse {

    const REGISTRY_KEY = 'taskResponse';

    public static function defaultFactory() {
        if(isset($_SERVER['argv']) && !df\Launchpad::$invokingApplication) {
            $channel = new core\io\channel\Std();
        } else {
            $channel = new core\io\channel\Memory();
        }

        return new self([$channel]);
    }

    


// Registry
    public function getRegistryObjectKey() {
        return static::REGISTRY_KEY;
    }

    public function onApplicationShutdown() {}



}