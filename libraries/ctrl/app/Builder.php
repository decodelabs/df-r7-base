<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\ctrl\app;

use df;
use df\core;
use df\ctrl;

class Builder {
    
    const DELETE_PACKAGE_BUILD_DIR = true;
    const PURGE_OLD_BUILDS = true;
    
    protected static $_appExport = [
        'libraries', 'daemons', 'directory', 'models', 'themes', 'tests'
    ];
    
    protected $_loader;
    
    public function __construct(core\ILoader $loader) {
        $this->_loader = $loader;
    }
    
    public function build() {
        ini_set('phar.readonly', 0);
        
        if(df\Launchpad::IS_COMPILED) {
            throw new \Exception(
                'Compiled installations can only be built from source development instances'
            );
        }
        
        
        try {
            $timestamp = date('Ymdhis');
            
            $appPath = df\Launchpad::$applicationPath;
            $environmentId = df\Launchpad::$environmentId;
            $prefix = df\Launchpad::$uniquePrefix;
            
            $pharPath = $appPath.'/static/local/run/Df-'.$timestamp.'.phar';
            $tempPath = $appPath.'/data/packageBuild';
            
            
            
            $umask = umask(0);
            core\io\Util::ensureDirExists(dirname($pharPath));
            
            if(is_dir($tempPath)) {
                if(!core\io\Util::emptyDir($tempPath)) {
                    throw new \Exception(
                        'Unable to empty existing temp build path'
                    );
                }
            } else {
                core\io\Util::ensureDirExists($tempPath);
            }
            
            core\io\Util::ensureDirExists($tempPath.'/apex/');
            
            
            // Generate Df.php
            $dfFile = file_get_contents(df\Launchpad::ROOT_PATH.'/Df.php');
            
            $dfFile = str_replace('IS_COMPILED = false', 'IS_COMPILED = true', $dfFile);
            $dfFile = str_replace('IN_PHAR = false', 'IN_PHAR = true', $dfFile);

            $dfFile = str_replace('COMPILE_TIMESTAMP = null', 'COMPILE_TIMESTAMP = '.time(), $dfFile);
            $dfFile = str_replace('COMPILE_KEY = null', 'COMPILE_KEY = \''.$prefix.'\'', $dfFile);
            
            file_put_contents($tempPath.'/Df.php', $dfFile);
            
            
            // Copy packages
            foreach(array_reverse($this->_loader->getPackages()) as $package) {
                if($package->name == 'app') {
                    foreach(scandir($package->path) as $entry) {
                        if($entry == '.' || $entry == '..') {
                            continue;
                        }

                        if(!in_array($entry, self::$_appExport)) {
                            continue;
                        }
                        
                        if($entry == 'libraries') {
                            core\io\Util::copyDirInto($package->path.'/'.$entry, $tempPath);
                            continue;
                        }
                        
                        if(is_dir($package->path.'/'.$entry)) {
                            core\io\Util::copyDir($package->path.'/'.$entry, $tempPath.'/apex/'.$entry, true);
                        }
                    }
                } else {
                    if(is_dir($package->path.'/libraries')) {
                        core\io\Util::copyDirInto($package->path.'/libraries', $tempPath);
                    }
                    
                    if(file_exists($package->path.'/Package.php')) {
                        core\io\Util::copyFile($package->path.'/Package.php', $tempPath.'/apex/packages/'.$package->name.'/Package.php');
                    }
                    
                    foreach(scandir($package->path) as $entry) {
                        if($entry == '.' 
                        || $entry == '..' 
                        || $entry == 'libraries') {
                            continue;
                        }
                        
                        if(is_dir($package->path.'/'.$entry)) {
                            core\io\Util::copyDir($package->path.'/'.$entry, $tempPath.'/apex/'.$entry, true);
                        }
                    }
                }
            }
            
            
            $phar = new \Phar($pharPath);
            $phar->setDefaultStub('Df.php', 'Df.php');
            $phar->buildFromDirectory($tempPath);
            
            if(static::DELETE_PACKAGE_BUILD_DIR) {
                core\io\Util::deleteDir($tempPath);
            }
            
            
            
            // Entry point
            $envIds = array($environmentId);
            $dir = new \DirectoryIterator($appPath.'/entry/');

            foreach($dir as $file) {
                if(!$file->isFile()) {
                    continue;
                }

                if(preg_match('/^([a-zA-Z0-9]+)\.development.php$/', $file->getFilename(), $matches)) {
                    $envIds[] = $matches[1];
                }
            }

            $envIds = array_unique($envIds);

            foreach($envIds as $entryEnvId) {
                foreach(['testing', 'production'] as $environmentMode) {
                    $entryPath = $appPath.'/entry/'.$entryEnvId.'.'.$environmentMode.'.php';
                    
                    $data = '<?php'."\n\n".
                            '/* This file is automatically generated by the DF package builder */'."\n".
                            'require_once \'phar://\'.dirname(__DIR__).\'/static/local/run/Df-'.$timestamp.'.phar\';'."\n".
                            'df\\Launchpad::runAs(\''.$entryEnvId.'\', \''.$environmentMode.'\', dirname(__DIR__));';
                    
                    file_put_contents($entryPath, $data);
                }
            }

            
            // Purge old builds
            if(static::PURGE_OLD_BUILDS) {
                $list = scandir(dirname($pharPath));
                $currentName = basename($pharPath);
                sort($list);
                
                // Take current and last one out
                array_pop($list);
                array_pop($list);
                
                foreach($list as $entry) {
                    if($entry == '.'
                    || $entry == '..') {
                        continue;
                    }
                    
                    unlink(dirname($pharPath).'/'.$entry);
                }
            }
        } catch(\Exception $e) {
            umask($umask);
            throw $e;
        }
        
        umask($umask);
    }
}
