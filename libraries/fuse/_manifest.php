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

use DecodeLabs\Terminus\Session;

interface IManager extends core\IManager
{
    public function getInstalledDependencyFor(aura\theme\ITheme $theme, $name);
    public function getInstalledDependenciesFor(aura\theme\ITheme $theme);

    public function ensureDependenciesFor(aura\theme\ITheme $theme, Session $cliSession=null);
    public function installDependenciesFor(aura\theme\ITheme $theme, Session $cliSession=null);
    public function installAllDependencies(Session $cliSession=null);
    public function installDependencies(array $dependencies, Session $cliSession=null);

    public function prepareDependenciesFor(aura\theme\ITheme $theme);
}
