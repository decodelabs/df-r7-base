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
interface ITheme extends aura\view\IRenderable, aura\view\ILayoutMap {
    public function getId();
    public function findAsset($path);
    public function mapIcon($name);
}
