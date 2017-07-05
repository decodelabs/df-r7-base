<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\code;

use df;
use df\core;


// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class LogicException extends \LogicException implements IException {}


// Interfaces
interface ILocation {
    public function setId(string $id);
    public function getId(): string;
    public function setPath($path);
    public function getPath();
    public function setBlackList(array $blackList);
    public function getBlackList();

    public function scan(IScanner $scanner);
    public function getProbes();
}


interface IScanner {
    public function setLocations(array $locations);
    public function addLocations(array $locations);
    public function addFrameworkPackageLocations(bool $allRoot=false, array $blackList=null);
    public function addLocation(ILocation $location);
    public function getLocation($id);
    public function hasLocation($id);
    public function removeLocation($id);
    public function getLocations();
    public function clearLocations();

    public function setProbes(array $probes);
    public function addProbes(array $probes);
    public function addProbe(IProbe $probe);
    public function getProbe($id);
    public function hasProbe($id);
    public function removeProbe($id);
    public function getProbes();
    public function clearProbes();

    public function scan();
}

interface IProbe {
    public function getId(): string;
    public function probe(ILocation $location, $localPath);
    public function exportTo(self $probe);
}

trait TProbe {
    public function getId(): string {
        $parts = explode('\\', get_class($this));
        return lcfirst(array_pop($parts));
    }
}

interface IProbeGroup extends core\collection\IMap {
    public function getAll();
}
