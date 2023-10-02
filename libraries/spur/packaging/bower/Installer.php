<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\spur\packaging\bower;

use DecodeLabs\Atlas;
use DecodeLabs\Exceptional;
use DecodeLabs\Terminus\Session;
use df\core;

use df\flex;
use df\fuse;
use df\spur;

class Installer implements IInstaller
{
    protected $_installDir;
    protected $_cachePath;
    protected $_mutex;
    protected $_session;
    protected $_resolvers = [];

    public function __construct(Session $session = null)
    {
        $installPath = fuse\Manager::getAssetPath();
        $this->_installDir = Atlas::dir($installPath);
        $this->_cachePath = '/tmp/decode-framework/bower';
        $this->_mutex = Atlas::newMutex('bower', $this->_cachePath);
        $this->setCliSession($session);
    }

    public function getInstallPath()
    {
        return $this->_installDir;
    }

    public function setCliSession(?Session $session = null)
    {
        $this->_session = $session;
        return $this;
    }

    public function getCliSession(): ?Session
    {
        return $this->_session;
    }

    public function installPackages(array $input)
    {
        $packages = [];

        foreach ($input as $name => $version) {
            if ($version instanceof Package) {
                $package = $version;
            } else {
                $package = new Package($name, $version);
            }

            if ($this->_installPackage($package)) {
                $packages[$package->getKey()] = $package;
            }
        }

        foreach ($packages as $package) {
            $this->_installDependencies($package);
        }

        $this->tidyCache();

        return $this;
    }

    public function installPackage(Package $package)
    {
        if ($this->_installPackage($package)) {
            $this->_installDependencies($package);
        }

        $this->tidyCache();

        return $this;
    }

    protected function _installPackage(Package $package, $depLevel = 0, $depParent = null)
    {
        $output = false;
        $this->_preparePackage($package);
        $resolver = $this->_getResolver($package->resolver);
        $this->_mutex->lock(60);

        if ($this->_session) {
            if ($depLevel) {
                $this->_session->write('|' . str_repeat('--', $depLevel) . ' ');

                if ($depParent) {
                    $this->_session->write('[');
                    $this->_session->{'magenta'}($depParent);
                    $this->_session->write('] ');
                }
            }

            $this->_session->{'brightMagenta'}($package->name);
        }

        $currentVersion = null;

        if ($this->_hasPackage($package)) {
            $data = flex\Json::fromFile($this->_installDir . '/' . $package->installName . '/.bower.json');
            $currentVersion = $data['version'];

            if ($this->_session) {
                $this->_session->write('#');
                $this->_session->{'brightCyan'}($currentVersion);
            }
        }

        try {
            if ($resolver->fetchPackage($package, $this->_cachePath, $currentVersion)) {
                if ($this->_session) {
                    $this->_session->write(' => ');
                    $this->_session->inlineSuccess($package->version);
                }

                $this->_extractCache($package);
                $output = true;
            } else {
                if ($this->_session) {
                    $this->_session->write(' - ');
                    $this->_session->inlineSuccess('up to date');
                }
            }
        } catch (\Throwable $e) {
            $this->_mutex->unlock();
            throw $e;
        }

        if ($this->_session) {
            $this->_session->newLine();
        }

        $this->_mutex->unlock();
        return $output;
    }

    protected function _installDependencies(Package $package, $depLevel = 0)
    {
        if (!$data = $this->getPackageBowerData($package)) {
            return;
        }

        $deps = $data->dependencies->toArray();
        $subDeps = [];

        foreach ($deps as $name => $version) {
            $depPackage = new Package($name, $version);

            if ($package->autoInstallName) {
                $depPackage->installName = null;
            }

            $depPackage->isDependency = true;
            $this->_preparePackage($depPackage);

            if ($installed = $this->getPackageInfo($depPackage)) {
                try {
                    $range = flex\VersionRange::factory($depPackage->version);

                    if (!$range->contains($installed->version) && $installed->installName == $depPackage->installName) {
                        throw Exceptional::Runtime(
                            'Unable to satisfy ' . $package->name . ' dependencies - version conflict for ' . $package->name
                        );
                    } else {
                        $depPackage = $installed;
                    }
                } catch (flex\Exception $e) {
                    // never mind
                }
            }

            if ($this->_installPackage($depPackage, $depLevel + 1, $package->installName)) {
                // only install sub dependencies if change is actually made
                $subDeps[$depPackage->installName] = $depPackage;
            }
        }

        foreach ($subDeps as $depPackage) {
            $this->_installDependencies($depPackage, $depLevel + 1);
        }
    }

    public function isPackageInstalled($name)
    {
        if ($name instanceof Package) {
            $name = $name->installName;
        }

        if ($this->_installDir->hasFile($name . '/.bower.json')) {
            return true;
        }

        foreach ($this->_installDir->scanDirs() as $dirName => $dir) {
            if (!$dir->hasFile('.bower.json')) {
                continue;
            }

            $data = flex\Json::fileToTree($dir . '/.bower.json');

            if ($name == $data['name']) {
                return true;
            }
        }

        return false;
    }

    public function _hasPackage($name)
    {
        if ($name instanceof Package) {
            $name = $name->installName;
        }

        return $this->_installDir->hasFile($name . '/.bower.json');
    }

    public function getInstalledPackages()
    {
        $output = [];

        foreach ($this->_installDir->scanDirs() as $dirName => $dir) {
            foreach ($dir->scanDirs() as $subDirName => $subDir) {
                if ($package = $this->_getPackageInfo($dirName . '/' . $subDirName)) {
                    $output[(string)$subDir] = $package;
                }
            }
        }

        return $output;
    }

    public function getPackageInfo($name)
    {
        if ($name instanceof Package) {
            $name = $name->installName;
        }

        if ($this->_installDir->hasFile($name . '/.bower.json')) {
            return $this->_getPackageInfo($name);
        }

        foreach ($this->_installDir->listDirs() as $dirName => $dir) {
            if (!$dir->hasFile('.bower.json')) {
                continue;
            }

            $data = flex\Json::fileToTree($dir . '/.bower.json');

            if ($name == $data['name']) {
                $package = new Package($dirName, $data['url']);
                $package->url = $data['url'];
                $package->name = $data['name'];
                $package->version = $data['version'];

                return $package;
            }
        }
    }

    protected function _getPackageInfo($name)
    {
        if ($name instanceof Package) {
            $name = $name->installName;
        }

        $file = $this->_installDir->getFile($name . '/.bower.json');

        if ($file->exists()) {
            $data = flex\Json::fileToTree($file);

            $package = new Package($name, $data['url']);
            $package->url = $data['url'];
            $package->name = $data['name'];
            $package->version = $data['version'];

            return $package;
        }
    }

    public function getPackageBowerData($name)
    {
        if ($name instanceof Package) {
            $name = $name->installName;
        }

        $file = $this->_installDir->getFile($name . '/bower.json');

        if ($file->exists()) {
            return flex\Json::fileToTree($file);
        }
    }

    public function getPackageJsonData($name)
    {
        if ($name instanceof Package) {
            $name = $name->installName;
        }

        $file = $this->_installDir->getFile($name . '/package.json');

        if ($file->exists()) {
            return flex\Json::fileToTree($file);
        }
    }



    // Resolvers
    protected function _preparePackage(Package $package, $useRegistry = true)
    {
        if (!strlen((string)$package->source)) {
            $package->source = 'latest';
        }

        if (false !== strpos($package->source, '#')) {
            list($package->source, $package->version) = explode('#', $package->source, 2);
        }

        if (preg_match('/^([^\/\@#\:]+)\/([^\/\@#\:]+)$/', $package->source)) {
            $package->source = 'git://github.com/' . $package->source . '.git';
        }


        // Git
        if (preg_match('/^git(\+(ssh|https?))?:\/\//i', $package->source)
        || preg_match('/\.git\/?$/i', $package->source)
        || preg_match('/^git@/i', $package->source)) {
            $package->url = str_replace('/^git\+/', '', $package->source);

            if (!$package->name) {
                $package->name = basename($package->url, '.git');
            }

            if (preg_match('/(?:@|:\/\/)github.com/', $package->url)) {
                $package->resolver = 'Github';
            } else {
                $package->resolver = 'Git';
            }
        }

        // SVN
        elseif (preg_match('/^svn(\+(ssh|https?|file))?:\/\//i', $package->source)) {
            $package->url = $package->source;

            if (!$package->name) {
                $package->name = basename($package->url);
            }

            $package->resolver = 'Svn';
        }

        // HTTP
        elseif (preg_match('/^https?:\/\//i', $package->source)) {
            $package->url = $package->source;

            if (!$package->name) {
                $package->name = basename($package->url);
            }

            $package->resolver = 'Url';
        }

        // Local
        elseif (preg_match('/^\.\.?[\/\\\\]/', $package->source)
        || preg_match('/^~?\//', $package->source)) {
            $package->url = rtrim((string)$package->source, '/');

            if (!$package->name) {
                $package->name = basename($package->url);
            }

            if (is_dir($package->url . '/.git')) {
                $package->resolver = 'GitFileSystem';
            } elseif (is_dir($package->url . '/.svn')) {
                $package->resolver = 'SvnFileSystem';
            } else {
                $package->resolver = 'FileSystem';
            }
        }


        // Registry
        else {
            if ($package->source == 'latest') {
                $package->version = '*';
                $package->source = $package->name;
            }

            try {
                $package->version = flex\VersionRange::factory($package->source);
                $package->source = $package->name;
            } catch (flex\Exception $e) {
                $package->name = $package->source;
            }

            if ($useRegistry) {
                $registry = new spur\packaging\bower\Registry();

                try {
                    $registry = $registry->lookup($package->name);
                    $package->url = $registry['url'];
                    $package->name = $registry['name'];
                    $package->isRegistry = true;
                } catch (ApiException $e) {
                    // never mind
                }

                if ($package->url) {
                    $package->source = $package->url;
                    return $this->_preparePackage($package, false);
                }
            }
        }

        if (!$package->resolver) {
            throw Exceptional::Runtime(
                'No valid resolver could be found for package: ' . $package->name
            );
        }

        if (!$package->installName) {
            $package->autoInstallName = true;
            $resolver = $this->_getResolver($package->resolver);

            if (!$package->isRegistry) {
                $package->name = $resolver->resolvePackageName($package);
            }

            try {
                $range = flex\VersionRange::factory($package->version);
            } catch (flex\Exception $e) {
                $range = null;
            }

            if ($package->isDependency) {
                $dir = $this->_installDir->getDir($package->name);

                if ($dir->exists()) {
                    $versions = $dir->listDirNames();

                    if (in_array('latest', $versions)) {
                        $package->installName = $package->name . '/latest';
                    } else {
                        rsort($versions);

                        foreach ($versions as $versionStr) {
                            try {
                                $version = flex\Version::factory($versionStr);
                            } catch (flex\Exception $e) {
                                continue;
                            }

                            if (!$range || $range->contains($version)) {
                                $package->installName = $package->name . '/' . $versionStr;
                                break;
                            }
                        }
                    }
                }
            }

            if (!$package->installName) {
                if (
                    !$package->isDependency &&
                    (
                        $package->version == '*' ||
                        $package->version == 'latest' ||
                        !strlen((string)$package->version)
                    )
                ) {
                    $package->installName = $package->name . '/latest';
                } else {
                    if ($range) {
                        $version = $range->getMinorGroupVersion();
                    } else {
                        $version = null;
                    }

                    if (!$version) {
                        if (
                            !strlen((string)$package->version) ||
                            $package->version == 'latest'
                        ) {
                            $package->version = '*';
                        }

                        $version = $resolver->getTargetVersion(
                            $package,
                            $this->_cachePath
                        );
                    }

                    if ($version instanceof flex\Version) {
                        $version = $version->getMajor() . '.' . $version->getMinor();
                    }

                    $package->installName = $package->name . '/' . $version;
                }
            }
        }

        if (
            !strlen((string)$package->version) ||
            $package->version == 'latest'
        ) {
            $package->version = '*';
        }
    }

    protected function _getResolver($name)
    {
        if (isset($this->_resolvers[$name])) {
            return $this->_resolvers[$name];
        }

        $class = 'df\\spur\\packaging\\bower\\resolver\\' . $name;

        if (!class_exists($class)) {
            throw Exceptional::Logic(
                $name . ' resolver isn\'t done yet'
            );
        }

        return $this->_resolvers[$name] = new $class();
    }


    // Cache
    public function tidyCache()
    {
        $path = $this->_cachePath . '/packages';

        if (!is_dir($path)) {
            return $this;
        }

        $dir = new \DirectoryIterator($path);
        $time = core\time\Date::factory('-3 days')->toTimestamp();

        foreach ($dir as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if ($file->getMTime() < $time) {
                Atlas::deleteFile($file->getPathname());
            }
        }

        return $this;
    }

    protected function _extractCache(Package $package)
    {
        $sourcePath = $this->_cachePath . '/packages/' . $package->cacheFileName;
        $destination = $this->_installDir->getDir($package->installName);
        $destination->delete();

        if (is_file($sourcePath)) {
            try {
                core\archive\Base::extract(
                    $sourcePath,
                    (string)$destination,
                    true
                );
            } catch (\Throwable $e) {
                Atlas::deleteFile($sourcePath);
                throw $e;
            }
        } elseif (is_dir($sourcePath)) {
            Atlas::copyDir($sourcePath, $destination);
        } else {
            throw Exceptional::Runtime(
                'Unable to locate fetched package source in cache: ' . $package->cacheFileName
            );
        }

        $this->_filterFiles($destination);

        flex\Json::toFile(
            $destination . '/.bower.json',
            [
                'name' => $package->name,
                'url' => $package->url,
                'version' => $package->version
            ]
        );

        //Atlas::deleteFile($sourcePath);
    }

    protected function _filterFiles($destination)
    {
        $force = [];
        $ignore = ['.bower.json', '.git', '.svn'];

        if (is_file($destination . '/bower.json')) {
            $bowerData = flex\Json::fileToTree($destination . '/bower.json');
            $ignore = array_merge($ignore, $bowerData->ignore->toArray());

            if (count($bowerData->main)) {
                $force = array_merge($force, $bowerData->main->toArray());
            } elseif (isset($bowerData['main'])) {
                $force[] = $bowerData['main'];
            }
        }

        $matcher = new spur\packaging\bower\matcher\Ignore($destination);

        foreach ($matcher->match($ignore, $force) as $path => $entry) {
            $entry->delete();
        }
    }
}
