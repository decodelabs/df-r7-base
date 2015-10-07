<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\theme;

use df;
use df\core;
use df\aura;
use df\spur;

class Manager implements IManager {

    use core\TManager;

    const REGISTRY_PREFIX = 'manager://theme';

    public function ensureDependenciesFor(ITheme $theme, core\io\IMultiplexer $io=null) {
        $path = df\Launchpad::$application->getLocalStoragePath().'/theme/dependencies/'.$theme->getId();
        $vendorPath = df\Launchpad::$application->getApplicationPath().'/assets/vendor/';

        if(df\Launchpad::$application->isDevelopment()) {
            if(!is_dir($vendorPath)) {
                core\fs\Dir::delete(dirname($path));
            }

            if(file_exists($path)) {
                $time = filemtime($path);

                if($time < time() - (30 * 60 * 60)) {
                    core\fs\File::delete($path);
                }
            }
        }

        if(file_exists($path) && is_dir($vendorPath)) {

            return $this;
        }

        return $this->installDependenciesFor($theme, $io);
    }

    protected function _storeManifest(ITheme $theme, array $dependencies) {
        $path = df\Launchpad::$application->getLocalStoragePath().'/theme/dependencies/'.$theme->getId();
        core\fs\File::create($path, serialize($dependencies));
    }

    public function installDependenciesFor(ITheme $theme, core\io\IMultiplexer $io=null) {
        $deps = $this->getPreparedDependencyDefinitions($theme);
        $this->installDependencies($deps, $io);
        $this->_storeManifest($theme, $deps);
        return $this;
    }

    public function installAllDependencies(core\io\IMultiplexer $io=null) {
        $config = Config::getInstance();
        $themes = array_unique($config->getThemeMap());
        $dependencies = [];
        $themeDeps = [];

        foreach($themes as $themeId) {
            $theme = aura\theme\Base::factory($themeId);

            foreach($this->getPreparedDependencyDefinitions($theme) as $dependency) {
                $key = $dependency->getKey();
                $dependencies[$key] = $dependency;
                $themeDeps[$theme->getId()][$key] = true;
            }
        }

        $this->installDependencies($dependencies, $io);

        foreach($themes as $themeId) {
            $theme = aura\theme\Base::factory($themeId);
            $deps = [];

            if(isset($themeDeps[$theme->getId()])) {
                foreach($themeDeps[$theme->getId()] as $key => $blah) {
                    $deps[$key] = $dependencies[$key];
                }
            }

            $this->_storeManifest($theme, $deps);
        }

        return $this;
    }

    public function installDependencies(array $dependencies, core\io\IMultiplexer $io=null) {
        $packages = [];

        if($io) {
            $io->write('Installing theme dependencies...');
            $io->incrementLineLevel();
        }

        if(empty($dependencies)) {
            if($io) {
                $io->writeLine(' none found');
                $io->decrementLineLevel();
            }

            return;
        }

        foreach($dependencies as $i => $dependency) {
            if(!$dependency instanceof IDependency) {
                unset($dependencies[$i]);
                continue;
            }

            $packages[$dependency->getKey()] = $dependency->getPackage();
        }

        if($io) {
            $io->writeLine();
        }

        $installer = new spur\packaging\bower\Installer($io);
        $installer->installPackages($packages);

        if($io) {
            $io->decrementLineLevel();
        }

        foreach($dependencies as $dependency) {
            $key = $dependency->getKey();
            $package = $packages[$key];

            $dependency->installName = $package->installName;

            $data = $installer->getPackageBowerData($package);

            if(isset($data['main'])) {
                $dependency->js[] = $data['main'];
                $dependency->js = array_unique($dependency->js);
            }
        }

        return $this;
    }

    public function getPreparedDependencyDefinitions(ITheme $theme) {
        $dependencies = $theme->getDependencies();
        $output = [];

        foreach($dependencies as $id => $data) {
            if(!is_string($id)) {
                if(isset($data['id'])) {
                    $id = $data['id'];
                } else if(is_string($data)) {
                    $id = $data;
                    $data = [];
                }
            }

            $dependency = new Dependency($id, $data);
            $output[$dependency->id] = $dependency;
        }

        return $output;
    }
}