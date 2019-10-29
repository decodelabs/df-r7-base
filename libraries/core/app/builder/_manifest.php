<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\app\builder;

use df;
use df\core;

use DecodeLabs\Atlas;
use DecodeLabs\Atlas\Dir;
use DecodeLabs\Terminus\Session;

interface IController
{
    public function getBuildId(): string;
    public function shouldCompile(bool $flag=null);

    public function getRunPath(): string;
    public function getDestination(): Dir;

    public function createBuild(): \Generator;
    public function activateBuild();
}
