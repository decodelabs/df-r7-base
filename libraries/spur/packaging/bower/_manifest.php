<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\packaging\bower;

use df;
use df\core;
use df\spur;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class LogicException extends \LogicException implements IException {}


// Interfaces
interface IBridge {

    public function setInstallPath($path);
    public function getInstallPath();
    public function setExecPath($path);
    public function getExecPath();
    public function generate(array $deps);
    public function install(array $deps);
}



interface IInstaller {
    public function setMultiplexer(core\io\IMultiplexer $io=null);
    public function getMultiplexer();
    public function installPackages(array $packages);
    public function installPackage(IPackage $package);
    public function isPackageInstalled($name);
    public function getInstalledPackages();
    public function getPackageInfo($name);
    public function getPackageBowerData($name);

    public function tidyCache();
}

interface IPackage {
    public function setName($name);
    public function getName();
    public function setVersion($version);
    public function getVersion();
    public function setInstallName($name);
    public function getInstallName();
    public function setUrl($url);
    public function getUrl();
    public function setCacheFileName($fileName);
    public function getCacheFileName();
}

interface IResolver {
    public function fetchPackage(IPackage $package, $cachePath, $currentVersion=null);
}

interface IRegistry extends spur\IHttpMediator {
    public function lookup($name);
    public function resolveUrl($name);
}