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


interface IManager extends core\IManager {
    public function getInstalledDependenciesFor(ITheme $theme);

    public function ensureDependenciesFor(ITheme $theme, core\io\IMultiplexer $io=null);
    public function installDependenciesFor(ITheme $theme, core\io\IMultiplexer $io=null);
    public function installAllDependencies(core\io\IMultiplexer $io=null);
    public function installDependencies(array $dependencies, core\io\IMultiplexer $io=null);

    public function getPreparedDependencyDefinitions(ITheme $theme);
}

interface IDependency {
    public function getId();
    public function getVersion();
    public function getSource();
    public function getJs();
    public function getCss();
    public function getShim();
    public function getKey();
    public function getPackage();
    public function getInstallName();
}