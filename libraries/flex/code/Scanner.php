<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\code;

use df;
use df\core;
use df\flex;
use df\axis;

use DecodeLabs\Exceptional;

class Scanner implements IScanner
{
    public $locations = [];
    public $probes = [];

    public function __construct(array $locations=null, array $probes=null)
    {
        if (!empty($locations)) {
            $this->addLocations($locations);
        }

        if (!empty($probes)) {
            $this->addProbes($probes);
        }
    }



    // Locations
    public function setLocations(array $locations)
    {
        return $this->clearLocations()->addLocations($locations);
    }

    public function addLocations(array $locations)
    {
        foreach ($locations as $location) {
            if (empty($location)) {
                continue;
            } elseif (!$location instanceof Location) {
                throw Exceptional::Runtime(
                    'Invalid location'
                );
            }

            $this->addLocation($location);
        }

        return $this;
    }

    public function addFrameworkPackageLocations(bool $allRoot=false, array $blackList=null)
    {
        if ($allRoot) {
            $model = axis\Model::factory('package');
            $packages = $model->getInstalledPackageList();
        } else {
            $packages = df\Launchpad::$loader->getPackages();
        }

        foreach ($packages as $name => $package) {
            $pathBlackList = [];

            if (is_array($package)) {
                $path = $package['path'];
                $name = $package['name'];
            } else {
                $path = $package->path;
            }

            if ($blackList !== null && in_array($name, $blackList)) {
                continue;
            }

            switch ($name) {
                case 'app':
                    $pathBlackList = [
                        'data',
                        'dev',
                        'static',
                        'assets/lib/vendor',
                        'vendor'
                    ];

                    break;

                case 'root':
                    $pathBlackList = [
                        'base/libraries/core/i18n/module/cldr',
                    ];

                    break;
            }

            $this->addLocation(new Location($name, $path, $pathBlackList));
        }

        return $this;
    }

    public function addLocation(Location $location)
    {
        $this->locations[$location->getId()] = $location;
        return $this;
    }

    public function getLocation($id)
    {
        if (isset($this->locations[$id])) {
            return $this->locations[$id];
        }
    }

    public function hasLocation($id)
    {
        if ($id instanceof Location) {
            $id = $id->getId();
        }

        return isset($this->locations[$id]);
    }

    public function removeLocation($id)
    {
        if ($id instanceof Location) {
            $id = $id->getId();
        }

        unset($this->locations[$id]);
        return $this;
    }

    public function getLocations()
    {
        return $this->locations;
    }

    public function clearLocations()
    {
        $this->locations = [];
        return $this;
    }


    // Probes
    public function setProbes(array $probes)
    {
        return $this->clearProbes()->addProbes($probes);
    }

    public function addProbes(array $probes)
    {
        foreach ($probes as $probe) {
            if (empty($probe)) {
                continue;
            } elseif (!$probe instanceof IProbe) {
                throw Exceptional::Runtime(
                    'Invalid probe'
                );
            }

            $this->addProbe($probe);
        }

        return $this;
    }

    public function addProbe(IProbe $probe)
    {
        $this->probes[$probe->getId()] = $probe;
        return $this;
    }

    public function getProbe($id)
    {
        if (isset($this->probes[$id])) {
            return $this->probes[$id];
        }
    }

    public function hasProbe($id)
    {
        if ($id instanceof IProbe) {
            $id = $id->getId();
        }

        return isset($this->probes[$id]);
    }

    public function removeProbe($id)
    {
        if ($id instanceof IProbe) {
            $id = $id->getId();
        }

        unset($this->probes[$id]);
        return $this;
    }

    public function getProbes()
    {
        return $this->probes;
    }

    public function clearProbes()
    {
        $this->probes = [];
        return $this;
    }



    // Exec
    public function scan()
    {
        $output = [];

        foreach ($this->probes as $id => $probe) {
            $output[$id] = new ProbeGroup();
        }

        foreach ($this->locations as $id => $location) {
            foreach ($location->scan($this) as $probeId => $probe) {
                $output[$probeId]->set($id, $probe);
            }
        }

        return $output;
    }
}
