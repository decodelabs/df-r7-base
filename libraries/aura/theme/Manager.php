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

    protected static $_depCache = [];


    public function getInstalledDependencyFor(ITheme $theme, $name) {
        $deps = $this->getInstalledDependenciesFor($theme);

        if(!isset($deps[$name])) {
            throw new RuntimeException(
                'Dependency '.$name.' is not in the dependency list'
            );
        }

        return $deps[$name];
    }

    public function getInstalledDependenciesFor(ITheme $theme) {
        $id = $theme->getId();

        if(!isset(self::$_depCache[$id])) {
            $this->ensureDependenciesFor($theme);
            $path = df\Launchpad::$application->getLocalStoragePath().'/theme/dependencies/'.$id;
            self::$_depCache[$id] = unserialize(core\fs\File::getContentsOf($path));
        }

        return self::$_depCache[$id];
    }

    protected function _storeManifest(ITheme $theme, array $dependencies) {
        $id = $theme->getId();
        unset(self::$_depCache[$id]);

        $output = [];

        foreach($dependencies as $dependency) {
            $output[$dependency->id] = $dependency;
        }

        self::$_depCache[$id] = $output;

        $path = df\Launchpad::$application->getLocalStoragePath().'/theme/dependencies/'.$id;
        core\fs\File::create($path, serialize($output));
    }



    public function ensureDependenciesFor(ITheme $theme, core\io\IMultiplexer $io=null) {
        $id = $theme->getId();

        if(isset(self::$_depCache[$id])) {
            return $this;
        }

        $path = df\Launchpad::$application->getLocalStoragePath().'/theme/dependencies/'.$id;
        $vendorPath = df\Launchpad::$application->getApplicationPath().'/assets/vendor/';
        $swallow = true;
        $depContent = null;

        if(df\Launchpad::$application->isDevelopment()) {
            $swallow = false;

            if(!is_dir($vendorPath)) {
                core\fs\Dir::delete(dirname($path));
                $swallow = true;
            }

            if(file_exists($path)) {
                $time = filemtime($path);

                if($time < time() - (30 * 60 * 60)) {
                    $depContent = core\fs\File::getContentsOf($path);
                    core\fs\File::delete($path);
                    unset(self::$_depCache[$id]);
                }
            } else {
                $swallow = true;
            }
        }

        if($depContent === null) {
            $swallow = false;
        }

        if(file_exists($path) && is_dir($vendorPath)) {
            return $this;
        }

        try {
            $this->installDependenciesFor($theme, $io);
        } catch(\Exception $e) {
            if($swallow) {
                core\log\Manager::getInstance()->logException($e);
            } else {
                throw $e;
            }
        }

        return $this;
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
            $installPath = $installer->getInstallPath().'/'.$package->installName;

            $dependency->installName = $package->installName;

            if(empty($dependency->js)) {
                $data = $installer->getPackageBowerData($package);
                $main = null;

                if(isset($data['main'])) {
                    $main = $data['main'];
                } else {
                    $data = $installer->getPackageJsonData($package);

                    if(isset($data['main'])) {
                        $main = $data['main'];
                    } else if(is_file($installPath.'/'.$dependency->id.'.js')) {
                        $main = $dependency->id.'.js';
                    }
                }

                if(substr($main, -6) != 'min.js') {
                    $fileName = substr($main, 0, -3);

                    if(is_file($installPath.'/'.$fileName.'.min.js')) {
                        $main = $fileName.'.min.js';
                    } else if(is_file($installPath.'/'.$fileName.'-min.js')) {
                        $main = $fileName.'-min.js';
                    }
                }

                if($main !== null) {
                    array_unshift($dependency->js, $main);
                    $dependency->js = array_unique($dependency->js);
                }
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


        // Default defs - need a better way to handle this!
        if(!isset($output['requirejs'])) {
            $output['requirejs'] = new Dependency('requirejs#~2.3');
        }

        if(!isset($output['jquery'])) {
            $output['jquery'] = new Dependency('jquery#~3.1');
        }

        if(!isset($output['underscore'])) {
            $output['underscore'] = new Dependency('underscore#~1.8', [
                'shim' => '_'
            ]);
        }

        return $output;
    }
}