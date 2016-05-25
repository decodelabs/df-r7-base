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
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface IFacetProvider {
    public function loadFacet($name, $callback=null);
    public function hasFacet($name);
    public function getFacet($name);
    public function removeFacet($name);
    public function getFacets();
}

interface ITheme extends IFacetProvider, aura\view\IViewRenderEventReceiver, aura\view\ILayoutMap {
    public function getId();

    public function findAsset($path);
    public function getApplicationImagePath();
    public function getApplicationColor();
    public function mapIcon($name);

    public function getDependencies();
}

interface IFacet extends aura\view\IViewRenderEventReceiver {}


interface IManager extends core\IManager {
    public function getInstalledDependencyFor(ITheme $theme, $name);
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
    public function getMap();
    public function getKey();
    public function getPackage();
    public function getInstallName();
}