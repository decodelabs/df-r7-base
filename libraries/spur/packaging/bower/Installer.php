<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\packaging\bower;

use df;
use df\core;
use df\spur;
use df\flex;

class Installer implements IInstaller {
    
    protected $_installPath;
    protected $_cachePath;
    protected $_lockFile;
    protected $_multiplexer;
    protected $_resolvers = [];

    public function __construct(core\io\IMultiplexer $io=null) {
        $this->_installPath = df\Launchpad::$application->getApplicationPath().'/assets/vendor';
        $this->_cachePath = core\io\Util::getGlobalCachePath().'/bower';
        $this->_lockFile = new core\io\LockFile($this->_cachePath);
        $this->setMultiplexer($io);
    }

    public function setMultiplexer(core\io\IMultiplexer $io=null) {
        $this->_multiplexer = $io;
        return $this;
    }

    public function getMultiplexer() {
        return $this->_multiplexer;
    }

    public function installPackages(array $packages) {
        foreach($packages as $name => $version) {
            if($version instanceof IPackage) {
                $package = $version;
            } else {
                $package = new Package($name, $version);
            }

            $this->installPackage($package);
        }

        return $this;
    }

    public function installPackage(IPackage $package) {
        $this->_installPackage($package);
        $this->_installDependencies($package);
        $this->tidyCache();

        return $this;
    }

    protected function _installPackage(IPackage $package, $depLevel=0) {
        $resolver = $this->_loadResolver($package);
        $this->_lockFile->lock();

        if(!strlen($package->version) || $package->version == 'latest') {
            $package->version = '*';
        }

        if($this->_multiplexer) {
            if($depLevel) {
                $this->_multiplexer->write('|'.str_repeat('--', $depLevel).' ');
            }

            $this->_multiplexer->write($package->name);
        }

        $currentVersion = null;

        if($this->_hasPackage($package)) {
            $data = flex\json\Codec::decode(file_get_contents(
                $this->_installPath.'/'.$package->installName.'/.bower.json'
            ));

            $currentVersion = $data['version'];

            if($this->_multiplexer) {
                $this->_multiplexer->write('#'.$currentVersion);
            }
        }

        try {
            if($resolver->fetchPackage($package, $this->_cachePath.'/packages', $currentVersion)) {
                if($this->_multiplexer) {
                    $this->_multiplexer->write(' => '.$package->version);
                }

                $this->_extractCache($package);
            } else {
                if($this->_multiplexer) {
                    $this->_multiplexer->write(' - up to date');
                }
            }
        } catch(\Exception $e) {
            $this->_lockFile->unlock();
            throw $e;
        }

        if($this->_multiplexer) {
            $this->_multiplexer->writeLine();
        }

        $this->_lockFile->unlock();
    }

    protected function _installDependencies(IPackage $package, $depLevel=0) {
        if(!$data = $this->getPackageBowerData($package)) {
            return;
        }

        $deps = $data->dependencies->toArray();

        foreach($deps as $name => $version) {
            $depPackage = new Package($name, $version);
            $this->_loadResolver($depPackage);

            if($installed = $this->getPackageInfo($depPackage->name)) {
                $range = core\string\VersionRange::factory($depPackage->version);

                if(!$range->contains($installed->version) && $installed->installName == $depPackage->installName) {
                    throw new RuntimeException(
                        'Unable to satisfy '.$package->name.' dependencies - version conflict for '.$package->name
                    );
                } else {
                    $depPackage = $installed;
                }
            }

            $this->_installPackage($depPackage, $depLevel + 1);
            $this->_installDependencies($depPackage, $depLevel + 1);
        }
    }

    public function isPackageInstalled($name) {
        if($name instanceof IPackage) {
            $name = $name->installName;
        }

        if(is_file($this->_installPath.'/'.$name.'/.bower.json')) {
            return true;
        }

        foreach(core\io\Util::listDirsIn($this->_installPath) as $dirName) {
            $path = $this->_installPath.'/'.$dirName;

            if(!is_file($path.'/.bower.json')) {
                continue;
            }

            $data = new core\collection\Tree(flex\json\Codec::decode(
                file_get_contents($path.'/.bower.json')
            ));

            if($name == $data['name']) {
                return true;
            }
        }

        return false;
    }

    public function _hasPackage($name) {
        if($name instanceof IPackage) {
            $name = $name->installName;
        }

        return is_file($this->_installPath.'/'.$name.'/.bower.json');
    }

    public function getInstalledPackages() {
        $output = [];

        foreach(core\io\Util::listDirsIn($this->_installPath) as $dirName) {
            $path = $this->_installPath.'/'.$dirName;

            if($package = $this->_getPackageInfo($dirName)) {
                $output[$path] = $package;
            }
        }

        return $output;
    }

    public function getPackageInfo($name) {
        if($name instanceof IPackage) {
            $name = $name->installName;
        }

        if(is_file($this->_installPath.'/'.$name.'/.bower.json')) {
            return $this->_getPackageInfo($name);
        }

        foreach(core\io\Util::listDirsIn($this->_installPath) as $dirName) {
            $path = $this->_installPath.'/'.$dirName;

            if(!is_file($path.'/.bower.json')) {
                continue;
            }

            $data = new core\collection\Tree(flex\json\Codec::decode(
                file_get_contents($path.'/.bower.json')
            ));

            if($name == $data['name']) {
                $package = new Package($dirName, $data['url']);
                $package->url = $data['url'];
                $package->name = $data['name'];
                $package->version = $data['version'];

                return $package;
            }
        }
    }

    protected function _getPackageInfo($name) {
        if($name instanceof IPackage) {
            $name = $name->installName;
        }

        $path = $this->_installPath.'/'.$name;

        if(is_file($path.'/.bower.json')) {
            $data = new core\collection\Tree(flex\json\Codec::decode(
                file_get_contents($path.'/.bower.json')
            ));

            $package = new Package($name, $data['url']);
            $package->url = $data['url'];
            $package->name = $data['name'];
            $package->version = $data['version'];

            return $package;
        }
    }

    public function getPackageBowerData($name) {
        if($name instanceof IPackage) {
            $name = $name->installName;
        }

        $path = $this->_installPath.'/'.$name.'/bower.json';

        if(is_file($path)) {
            return core\collection\Tree::factory(flex\json\Codec::decode(
                file_get_contents($path)
            ));
        }
    }



// Resolvers
    protected function _loadResolver(IPackage $package, $useRegistry=true) {
        if(!strlen($package->source)) {
            $package->source = 'latest';
        }

        if(false !== strpos($package->source, '#')) {
            list($package->source, $package->version) = explode('#', $package->source, 2);
        }

        if(preg_match('/^([^\/]+)\/([^\/]+)$/', $package->source)) {
            $package->source = 'git://github.com/'.$package->source.'.git';
        }

        if(preg_match('/^git(\+(ssh|https?))?:\/\//i', $package->source)
        || preg_match('/\.git\/?$/i', $package->source)
        || preg_match('/^git@/i', $package->source)) {
            $package->url = str_replace('/^git\+/', '', $package->source);

            if(!$package->name) {
                $package->name = basename($package->url, '.git');
            }

            if(preg_match('/(?:@|:\/\/)github.com/', $package->url)) {
                return $this->_getResolver('Github');
            } else {
                return $this->_getResolver('Git');
            }
        }

        if(preg_match('/^svn(\+(ssh|https?|file))?:\/\//i', $package->source)) {
            $package->url = $package->source;

            if(!$package->name) {
                $package->name = basename($package->url);
            }

            return $this->_getResolver('Svn');
        }

        if(preg_match('/^https?:\/\//i', $package->source)) {
            $package->url = $package->source;

            if(!$package->name) {
                $package->name = basename($package->url);
            }

            return $this->_getResolver('Url');
        }

        if(preg_match('/^\.\.?[\/\\\\]/', $package->source)
        || preg_match('/^~?\//', $package->source)) {
            $package->url = rtrim($package->source, '/');

            if(!$package->name) {
                $package->name = basename($package->url);
            }

            if(is_dir($package->url.'/.git')) {
                return $this->_getResolver('GitFileSystem');
            } else if(is_dir($package->url.'/.svn')) {
                return $this->_getResolver('SvnFileSystem');
            } else {
                return $this->_getResolver('FileSystem');
            }
        }

        if($package->source == 'latest') {
            $package->version = '*';
            $package->source = $package->name;
        }

        try {
            $package->version = core\string\VersionRange::factory($package->source);
            $package->source = $package->name;
        } catch(core\string\IException $e) {
            $package->name = $package->source;
        }

        if($useRegistry) {
            $registry = new spur\packaging\bower\Registry();

            try {
                $registry = $registry->lookup($package->name);
                $package->url = $registry['url'];
                $package->name = $registry['name'];
            } catch(spur\ApiError $e) {
                // never mind
            }

            if($package->url) {
                $package->source = $package->url;
                return $this->_loadResolver($package, false);
            }
        }

        throw new RuntimeException('No valid resolver could be found for package: '.$package->name);
    }

    protected function _getResolver($name) {
        if(isset($this->_resolvers[$name])) {
            return $this->_resolvers[$name];
        }

        $class = 'df\\spur\\packaging\\bower\\resolver\\'.$name;

        if(!class_exists($class)) {
            throw new LogicException($name.' resolver isn\'t done yet');
        }

        return $this->_resolvers[$name] = new $class();
    }


// Cache
    public function tidyCache() {
        $path = $this->_cachePath.'/packages';

        if(!is_dir($path)) {
            return $this;
        }

        $dir = new \DirectoryIterator($path);
        $time = core\time\Date::factory('-3 days')->toTimestamp();

        foreach($dir as $file) {
            if(!$file->isFile()) {
                continue;
            }

            if($file->getMTime() < $time) {
                core\io\Util::deleteFile($file->getPathname());
            }
        }

        return $this;
    }

    protected function _extractCache(IPackage $package) {
        $sourcePath = $this->_cachePath.'/packages/'.$package->cacheFileName;
        $destination = $this->_installPath.'/'.$package->installName;

        core\io\Util::deleteDir($destination);

        if(is_file($sourcePath)) {
            try {
                core\io\archive\Base::extract(
                    $sourcePath, $destination, true
                );
            } catch(core\io\archive\IException $e) {
                core\io\Util::delete($sourcePath);
                throw $e;
            }
        } else if(is_dir($sourcePath)) {
            core\stub($sourcePath);
        } else {
            throw new RuntimeException(
                'Unable to locate fetched package source in cache: '.$package->cacheFileName
            );
        }

        $delete = ['.bower.json', '.git', '.svn'];

        foreach($delete as $entry) {
            core\io\Util::delete($destination.'/'.$entry);
        }

        $data = flex\json\Codec::encode([
            'name' => $package->name,
            'url' => $package->url,
            'version' => $package->version
        ]);

        $data = str_replace('\/', '/', $data);
        file_put_contents($destination.'/.bower.json', $data);
        //core\io\Util::delete($sourcePath);
    }
}