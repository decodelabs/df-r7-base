<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\app\builder;

use df;
use df\core;
use df\flex;

class Controller implements IController {

    public $io;

    protected $_id;
    protected $_shouldCompile = true;

    public function __construct() {
        $this->_id = (string)flex\Guid::uuid1();
        $this->_shouldCompile = !df\Launchpad::$app->isDevelopment();
    }

    public function getBuildId(): string {
        return $this->_id;
    }

    public function shouldCompile(bool $flag=null) {
        if($flag !== null) {
            $this->_shouldCompile = $flag;
            return $this;
        }

        return $this->_shouldCompile;
    }


    public function setMultiplexer(?core\io\IMultiplexer $multiplexer) {
        $this->io = $multiplexer;
        return $this;
    }

    public function getMultiplexer(): core\io\IMultiplexer {
        if(!$this->io) {
            $this->io = core\io\Multiplexer::defaultFactory('task');
        }

        return $this->io;
    }
}
