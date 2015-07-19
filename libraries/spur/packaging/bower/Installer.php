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
    
    protected $_installDir;
    protected $_cachePath;
    protected $_lockFile;
    protected $_multiplexer;
    protected $_resolvers = [];

    public function __construct(core\io\IMultiplexer $io=null) {
        $installPath = df\Launchpad::$application->getApplicationPath().'/assets/vendor';
        $this->_installDir = new core\fs\Dir($installPath);
        $this->_cachePath = core\fs\Dir::getGlobalCachePath().'/bower';
        $this->_lockFile = new core\fs\LockFile($this->_cachePath);
        $this->setMultiplexer($io);
    }

    public function setMultiplexer(core\io\IMultiplexer $io=null) {
        $this->_multiplexer = $io;
        return $this;
    }

    public function getMultiplexer() {
        return $this->_multiplexer;
    }

    public function installPackages(array $input) {
        $packages = [];

        foreach($input as $name => $version) {
            if($version instanceof IPackage) {
                $package = $version;
            } else {
                $package = new Package($name, $version);
            }

            if($this->_installPackage($package)) {
                $packages[$package->installName] = $package;
            }
        }

        foreach($packages as $package) {
            $this->_installDependencies($package);
        }

        $this->tidyCache();

        return $this;
    }

    public function installPackage(IPackage $package) {
        if($this->_installPackage($package)) {
            $this->_installDependencies($package);
        }

        $this->tidyCache();

        return $this;
    }

    protected function _installPackage(IPackage $package, $depLevel=0, $depParent=null) {
        $output = false;
        $this->_preparePackage($package);
        $resolver = $this->_getResolver($package->resolver);
        $this->_lockFile->lock();

        if($this->_multiplexer) {
            if($depLevel) {
                $this->_multiplexer->write('|'.str_repeat('--', $depLevel).' ');

                if($depParent) {
                    $this->_multiplexer->write('['.$depParent.'] ');
                }
            }

            $this->_multiplexer->write($package->name);
        }

        $currentVersion = null;

        if($this->_hasPackage($package)) {
            $data = flex\json\Codec::decodeFile($this->_installDir.'/'.$package->installName.'/.bower.json');
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
                $output = true;
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
        return $output;
    }

    protected function _installDependencies(IPackage $package, $depLevel=0) {
        if(!$data = $this->getPackageBowerData($package)) {
            return;
        }

        $deps = $data->dependencies->toArray();
        $subDeps = [];

        foreach($deps as $name => $version) {
            $depPackage = new Package($name, $version);
            $this->_preparePackage($depPackage);

            if($installed = $this->getPackageInfo($depPackage->name)) {
                try {
                    $range = core\string\VersionRange::factory($depPackage->version);
                    if(!$range->contains($installed->version) && $installed->installName == $depPackage->installName) {
                        throw new RuntimeException(
                            'Unable to satisfy '.$package->name.' dependencies - version conflict for '.$package->name
                        );
                    } else {
                        $depPackage = $installed;
                    }
                } catch(core\string\RuntimeException $e) {
                    // never mind
                }
            }

            if($this->_installPackage($depPackage, $depLevel + 1, $package->installName)) {
                // only install sub dependencies if change is actually made
                $subDeps[$depPackage->installName] = $depPackage;
            }
        }

        foreach($subDeps as $depPackage) {
            $this->_installDependencies($depPackage, $depLevel + 1);
        }
    }

    public function isPackageInstalled($name) {
        if($name instanceof IPackage) {
            $name = $name->installName;
        }

        if($this->_installDir->hasFile($name.'/.bower.json')) {
            return true;
        }

        foreach($this->_installDir->scanDirs() as $dirName => $dir) {
            if(!$dir->hasFile('.bower.json')) {
                continue;
            }

            $data = flex\json\Codec::decodeFileAsTree($dir.'/.bower.json');

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

        return $this->_installDir->hasFile($name.'/.bower.json');
    }

    public function getInstalledPackages() {
        $output = [];

        foreach($this->_installDir->scanDirs() as $dirName => $dir) {
            if($package = $this->_getPackageInfo($dirName)) {
                $output[(string)$dir] = $package;
            }
        }

        return $output;
    }

    public function getPackageInfo($name) {
        if($name instanceof IPackage) {
            $name = $name->installName;
        }

        if($this->_installDir->hasFile($name.'/.bower.json')) {
            return $this->_getPackageInfo($name);
        }

        foreach($this->_installDir->listDirs() as $dirName => $dir) {
            if(!$dir->hasFile('.bower.json')) {
                continue;
            }

            $data = flex\json\Codec::decodeFileAsTree($dir.'/.bower.json');

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

        $file = $this->_installDir->getFile($name.'/.bower.json');

        if($file->exists()) {
            $data = flex\json\Codec::decodeFileAsTree($file);

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

        $file = $this->_installDir->getFile($name.'/bower.json');

        if($file->exists()) {
            return flex\json\Codec::decodeFileAsTree($file);
        }
    }



// Resolvers
    protected function _preparePackage(IPackage $package, $useRegistry=true) {
        if(!strlen($package->source)) {
            $package->source = 'latest';
        }

        if(false !== strpos($package->source, '#')) {
            list($package->source, $package->version) = explode('#', $package->source, 2);
        }

        if(preg_match('/^([^\/\@#\:]+)\/([^\/\@#\:]+)$/', $package->source)) {
            $package->source = 'git://github.com/'.$package->source.'.git';
        } 


        // Git
        if(preg_match('/^git(\+(ssh|https?))?:\/\//i', $package->source)
        || preg_match('/\.git\/?$/i', $package->source)
        || preg_match('/^git@/i', $package->source)) {
            $package->url = str_replace('/^git\+/', '', $package->source);

            if(!$package->name) {
                $package->name = basename($package->url, '.git');
            }

            if(preg_match('/(?:@|:\/\/)github.com/', $package->url)) {
                $package->resolver = 'Github';
            } else {
                $package->resolver = 'Git';
            }
        }

        // SVN
        else if(preg_match('/^svn(\+(ssh|https?|file))?:\/\//i', $package->source)) {
            $package->url = $package->source;

            if(!$package->name) {
                $package->name = basename($package->url);
            }

            $package->resolver = 'Svn';
        }

        // HTTP
        else if(preg_match('/^https?:\/\//i', $package->source)) {
            $package->url = $package->source;

            if(!$package->name) {
                $package->name = basename($package->url);
            }

            $package->resolver = 'Url';
        }

        // Local
        else if(preg_match('/^\.\.?[\/\\\\]/', $package->source)
        || preg_match('/^~?\//', $package->source)) {
            $package->url = rtrim($package->source, '/');

            if(!$package->name) {
                $package->name = basename($package->url);
            }

            if(is_dir($package->url.'/.git')) {
                $package->resolver = 'GitFileSystem';
            } else if(is_dir($package->url.'/.svn')) {
                $package->resolver = 'SvnFileSystem';
            } else {
                $package->resolver = 'FileSystem';
            }
        }


        // Registry
        else {
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
                    return $this->_preparePackage($package, false);
                }
            }
        }

        if(!$package->resolver) {
            throw new RuntimeException('No valid resolver could be found for package: '.$package->name);
        }

        if(!strlen($package->version) || $package->version == 'latest') {
            $package->version = '*';
        }
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
                core\fs\File::delete($file->getPathname());
            }
        }

        return $this;
    }

    protected function _extractCache(IPackage $package) {
        $sourcePath = $this->_cachePath.'/packages/'.$package->cacheFileName;
        $destination = $this->_installDir->getDir($package->installName)->unlink();

        if(is_file($sourcePath)) {
            try {
                core\archive\Base::extract(
                    $sourcePath, (string)$destination, true
                );
            } catch(core\archive\IException $e) {
                core\fs\File::delete($sourcePath);
                throw $e;
            }
        } else if(is_dir($sourcePath)) {
            core\fs\Dir::copy($sourcePath, $destination);
        } else {
            throw new RuntimeException(
                'Unable to locate fetched package source in cache: '.$package->cacheFileName
            );
        }

        $this->_filterFiles($destination);

        flex\json\Codec::encodeFile(
            $destination.'/.bower.json', 
            [
                'name' => $package->name,
                'url' => $package->url,
                'version' => $package->version
            ]
        );

        //core\fs\File::delete($sourcePath);
    }

    protected function _filterFiles($destination) {
        $force = [];
        $ignore = ['.bower.json', '.git', '.svn'];

        if(is_file($destination.'/bower.json')) {
            $bowerData = flex\json\Codec::decodeFileAsTree($destination.'/bower.json');
            $ignore = array_merge($ignore, $bowerData->ignore->toArray());

            if(count($bowerData->main)) {
                $force = array_merge($force, $bowerData->main->toArray());
            } else if(isset($bowerData['main'])) {
                $force[] = $bowerData['main'];
            }
        }

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            $destination, 
            \FilesystemIterator::KEY_AS_PATHNAME | 
            \FilesystemIterator::CURRENT_AS_SELF | 
            \FilesystemIterator::SKIP_DOTS
        ), \RecursiveIteratorIterator::SELF_FIRST);

        $delete = [];

        foreach($it as $name => $entry) {
            $path = $entry->getSubPathname();

            if($this->_matchFile($path, $ignore, $force)) {
                $delete[] = $path;
            }
        }

        foreach($delete as $path) {
            core\fs\File::delete($destination.'/'.$path);
        }
    }

    protected function _matchFile($path, array $patterns, array $blacklist) {
        if(in_array($path, $blacklist)) {
            return false;
        }

        foreach($patterns as $pattern) {
            $testPath = $path;

            if($isNegated = 0 === strpos($pattern, '!')) {
                $pattern = substr($pattern, 1);
            }

            if(false !== strpos($pattern, '**')) {
                $pattern = str_replace('**', '*', $pattern);

                if(substr($pattern, 0, 1) == '/'
                || substr($testPath, 0, 1) == '.') {
                    $testPath = '/'.$testPath;
                }

                if(fnmatch($pattern, $testPath, \FNM_PATHNAME)) {
                    return true;
                }
            } else {
                $pattern = ltrim($pattern, '/');
                $testPath = ltrim($testPath, '/');

                $regex = str_replace(['.', '*'], ['\.', '.*'], $pattern);

                if(preg_match('#^'.$regex.'#', $testPath)) {
                    return true;
                }
            }
        }
        
        return false;
    }
}