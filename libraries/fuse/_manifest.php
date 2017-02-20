<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fuse;

use df;
use df\core;
use df\fuse;
use df\aura;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface IManager extends core\IManager {
    public function getInstalledDependencyFor(aura\theme\ITheme $theme, $name);
    public function getInstalledDependenciesFor(aura\theme\ITheme $theme);

    public function ensureDependenciesFor(aura\theme\ITheme $theme, core\io\IMultiplexer $io=null);
    public function installDependenciesFor(aura\theme\ITheme $theme, core\io\IMultiplexer $io=null);
    public function installAllDependencies(core\io\IMultiplexer $io=null);
    public function installDependencies(array $dependencies, core\io\IMultiplexer $io=null);

    public function getPreparedDependencyDefinitions(aura\theme\ITheme $theme);
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
