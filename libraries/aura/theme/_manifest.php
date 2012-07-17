<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\theme;

use df;
use df\core;
use df\aura;

// Exceptions
interface IException {}


// Interfaces
interface ITheme extends aura\view\IRenderable {
    public function getId();
    public function findAsset(core\IApplication $application, $path);
    public function mapIcon($name);
}
