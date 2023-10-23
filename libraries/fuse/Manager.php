<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\fuse;

use DecodeLabs\Atlas;
use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use DecodeLabs\R7\Config\Fuse as FuseConfig;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Terminus\Session;
use df\aura;
use df\core;
use df\fuse;
use df\spur;

class Manager implements IManager
{
    use core\TManager;

    public const REGISTRY_PREFIX = 'manager://fuse';

    protected static $_depCache = [];


    public static function getManifestCachePath(): string
    {
        return Genesis::$hub->getLocalDataPath() . '/theme/dependencies';
    }

    public static function getAssetPath(): string
    {
        return Genesis::$hub->getApplicationPath() . '/assets/vendor';
    }


    public function getInstalledDependencyFor(aura\theme\ITheme $theme, $name)
    {
        $deps = $this->getInstalledDependenciesFor($theme);

        if (!isset($deps[$name])) {
            throw Exceptional::Runtime(
                'Dependency ' . $name . ' is not in the dependency list'
            );
        }

        return $deps[$name];
    }

    public function getInstalledDependenciesFor(aura\theme\ITheme $theme)
    {
        $id = $theme->getId();

        if (!isset(self::$_depCache[$id])) {
            $this->ensureDependenciesFor($theme);
            $path = self::getManifestCachePath() . '/' . $id;
            self::$_depCache[$id] = unserialize(Atlas::getContents($path));
        }

        return self::$_depCache[$id];
    }

    protected function _storeManifest(aura\theme\ITheme $theme, array $dependencies)
    {
        $id = $theme->getId();
        unset(self::$_depCache[$id]);

        $output = [];

        foreach ($dependencies as $dependency) {
            $output[$dependency->id] = $dependency;
        }

        self::$_depCache[$id] = $output;

        $path = self::getManifestCachePath() . '/' . $id;
        Atlas::createFile($path, serialize($output));
    }



    public function ensureDependenciesFor(aura\theme\ITheme $theme, Session $session = null)
    {
        $id = $theme->getId();

        if (isset(self::$_depCache[$id])) {
            return $this;
        }

        $path = self::getManifestCachePath() . '/' . $id;
        $vendorPath = self::getAssetPath();
        $swallow = true;
        $depContent = null;

        if (Genesis::$environment->isDevelopment()) {
            $swallow = false;

            if (!is_dir($vendorPath)) {
                Atlas::deleteDir(dirname($path));
                $swallow = true;
            }

            if (file_exists($path)) {
                $time = filemtime($path);

                if ($time < time() - (30 * 60 * 60)) {
                    $depContent = Atlas::getContents($path);
                    Atlas::deleteFile($path);
                    unset(self::$_depCache[$id]);
                }
            } else {
                $swallow = true;
            }
        }

        if ($depContent === null) {
            $swallow = false;
        }

        if (file_exists($path) && is_dir($vendorPath)) {
            return $this;
        }

        try {
            $this->installDependenciesFor($theme, $session);
        } catch (\Throwable $e) {
            if ($swallow) {
                core\log\Manager::getInstance()->logException($e);
            } else {
                throw $e;
            }
        }

        return $this;
    }

    public function installDependenciesFor(aura\theme\ITheme $theme, Session $session = null)
    {
        $deps = $this->prepareDependenciesFor($theme);
        $this->installDependencies($deps, $session);
        $this->_storeManifest($theme, $deps);
        return $this;
    }

    public function installAllDependencies(Session $session = null)
    {
        $themes = array_unique(Legacy::getThemeMap());
        $dependencies = [];
        $themeDeps = [];

        foreach ($themes as $themeId) {
            $theme = aura\theme\Base::factory($themeId);

            foreach ($this->prepareDependenciesFor($theme) as $dependency) {
                $key = $dependency->getKey();
                $dependencies[$key] = $dependency;
                $themeDeps[$theme->getId()][$key] = true;
            }
        }

        $this->installDependencies($dependencies, $session);

        foreach ($themes as $themeId) {
            $theme = aura\theme\Base::factory($themeId);
            $deps = [];

            if (isset($themeDeps[$theme->getId()])) {
                foreach ($themeDeps[$theme->getId()] as $key => $blah) {
                    $deps[$key] = $dependencies[$key];
                }
            }

            $this->_storeManifest($theme, $deps);
        }

        return $this;
    }

    public function installDependencies(array $dependencies, Session $session = null)
    {
        $packages = [];

        if (empty($dependencies)) {
            return;
        }

        foreach ($dependencies as $i => $dependency) {
            if (!$dependency instanceof fuse\Dependency) {
                unset($dependencies[$i]);
                continue;
            }

            $packages[$dependency->getKey()] = $dependency->getPackage();
        }

        $installer = new spur\packaging\bower\Installer($session);
        $installer->installPackages($packages);

        foreach ($dependencies as $dependency) {
            $key = $dependency->getKey();
            $package = $packages[$key];
            $installPath = $installer->getInstallPath() . '/' . $package->installName;

            $dependency->installName = $package->installName;

            if (empty($dependency->js)) {
                $data = $installer->getPackageBowerData($package);
                $main = null;

                if (isset($data->main)) {
                    if (count($data->main)) {
                        $main = $data->main->toArray();
                    } else {
                        $main = $data['main'];
                    }
                } else {
                    $data = $installer->getPackageJsonData($package);

                    if (isset($data['main'])) {
                        $main = $data['main'];
                    } elseif (is_file($installPath . '/' . $dependency->id . '.js')) {
                        $main = $dependency->id . '.js';
                    }
                }

                if ($main === null) {
                    $main = [];
                }

                if (!is_array($main)) {
                    $main = [$main];
                }

                foreach (array_reverse($main) as $mainEntry) {
                    if (substr($mainEntry, -6) != 'min.js') {
                        $fileName = substr($mainEntry, 0, -3);

                        if (is_file($installPath . '/' . $fileName . '.min.js')) {
                            $mainEntry = $fileName . '.min.js';
                        } elseif (is_file($installPath . '/' . $fileName . '-min.js')) {
                            $mainEntry = $fileName . '-min.js';
                        }
                    }

                    array_unshift($dependency->js, $mainEntry);
                }

                $dependency->js = array_unique($dependency->js);
            }
        }

        return $this;
    }

    public function prepareDependenciesFor(aura\theme\ITheme $theme): array
    {
        $output = [];

        foreach ($this->_normalizeDependencies(FuseConfig::load()->getDependencies()) as $id => $dependency) {
            $output[$dependency->id] = $dependency;
        }

        foreach ($this->_normalizeDependencies($theme->getDependencies()) as $id => $dependency) {
            $output[$dependency->id] = $dependency;
        }


        // Default defs - need a better way to handle this!
        if (!isset($output['jquery'])) {
            $output['jquery'] = new Dependency('jquery#~3.1');
        }

        if (!isset($output['underscore'])) {
            $output['underscore'] = new Dependency('underscore#~1.8', [
                'shim' => '_'
            ]);
        }

        return $output;
    }

    protected function _normalizeDependencies(array $dependencies)
    {
        foreach ($dependencies as $id => $data) {
            if (!is_string($id)) {
                if (isset($data['id'])) {
                    $id = $data['id'];
                } elseif (is_string($data)) {
                    $id = $data;
                    $data = [];
                }
            }

            $dep = new Dependency($id, $data);
            yield $dep->id => $dep;
        }
    }
}
