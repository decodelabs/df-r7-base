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
interface ITheme extends aura\view\IViewRenderEventReceiver, aura\view\ILayoutMap {
    public function getId();

    public function findAsset($path);
    public function getApplicationImagePath();
    public function getApplicationColor();
    public function mapIcon($name);
    public function getDependencies();

    public function loadFacet($name);
    public function hasFacet($name);
    public function getFacet($name);
    public function removeFacet($name);
    public function getFacets();
}

interface IFacet extends aura\view\IViewRenderEventReceiver {}
