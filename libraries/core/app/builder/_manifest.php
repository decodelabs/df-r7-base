<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\app\builder;

use df;
use df\core;

interface IController {

    public function getBuildId(): string;
    public function shouldCompile(bool $flag=null);

    public function setMultiplexer(?core\io\IMultiplexer $multiplexer);
    public function getMultiplexer(): core\io\IMultiplexer;
}
