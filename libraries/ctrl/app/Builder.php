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
    const BUILD_PHAR = false;
    
    protected static $_appExport = [
        'libraries', 'daemons', 'directory', 'models', 'themes', 'tests'
    ];
    
    protected $_loader;
    
    public function __construct(core\ILoader $loader) {
        $this->_loader = $loader;
    }
    
    public function build() {
        if(self::BUILD_PHAR) {
            ini_set('phar.readonly', 0);
        }
        
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
            
            $targetPath = $appPath.'/data/local/run';
    
            if(self::BUILD_PHAR) {            
                $pharPath = $targetPath.'/Df-'.$timestamp.'.phar';
                $tempPath = $appPath.'/data/packageBuild';
            } else {
                $tempPath = $targetPath.'/df-'.$timestamp;
            }
            
            
            $umask = umask(0);
            core\io\Util::ensureDirExists($targetPath);
            
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
            $dfFile = str_replace('COMPILE_TIMESTAMP = null', 'COMPILE_TIMESTAMP = '.time(), $dfFile);

            if(self::BUILD_PHAR) {
                $dfFile = str_replace('IN_PHAR = false', 'IN_PHAR = true', $dfFile);
            }

            
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
            
            
            if(self::BUILD_PHAR) {
                $phar = new \Phar($pharPath);
                $phar->setDefaultStub('Df.php', 'Df.php');
                $phar->buildFromDirectory($tempPath);

                if(static::DELETE_PACKAGE_BUILD_DIR) {
                    core\io\Util::deleteDir($tempPath);
                }
            }
            
            
            
            // Entry point
            foreach(['testing', 'production'] as $environmentMode) {
                $entryPath = $appPath.'/entry/'.$environmentId.'.'.$environmentMode.'.php';#
                $data = '<?php'."\n\n".
                        '/* This file is automatically generated by the DF package builder */'."\n";
                
                if(self::BUILD_PHAR) {
                    $data .= 'require_once \'phar://\'.dirname(__DIR__).\'/data/local/run/Df-'.$timestamp.'.phar\';'."\n";
                } else {
                    $data .= 'require_once dirname(__DIR__).\'/data/local/run/df-'.$timestamp.'/Df.php\';'."\n";
                }

                $data .= 'df\\Launchpad::runAs(\''.$environmentId.'\', \''.$environmentMode.'\', dirname(__DIR__));';
                file_put_contents($entryPath, $data);
            }

            
            // Purge old builds
            if(static::PURGE_OLD_BUILDS) {
                $list = scandir($targetPath);
                sort($list);
                
                // Take current and last one out
                array_pop($list);
                array_pop($list);
                
                foreach($list as $entry) {
                    if($entry == '.'
                    || $entry == '..') {
                        continue;
                    }
                    
                    if(is_file($targetPath.'/'.$entry)) {
                        unlink($targetPath.'/'.$entry);
                    } else if(is_dir($targetPath.'/'.$entry)) {
                        core\io\Util::deleteDir($targetPath.'/'.$entry);
                    }
                }
            }
        } catch(\Exception $e) {
            umask($umask);
            throw $e;
        }
        
        umask($umask);
    }
}
