<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\package;

use df;
use df\core;

class Builder {
    
    protected static $_appExport = [
        'libraries', 'directory', 'models', 'themes', 'tests'
    ];
    
    protected $_loader;
    
    public function __construct(core\ILoader $loader) {
        $this->_loader = $loader;
    }
    
    public function buildTestingInstallation($environmentMode='testing') {
        ini_set('phar.readonly', 0);
        
        if(df\Launchpad::IS_COMPILED) {
            throw new \Exception(
                'Compiled installations can only be built from source development instances'
            );
        }
        
        
        try {
            $timestamp = time();
            
            $appPath = df\Launchpad::$applicationPath;
            $environmentId = df\Launchpad::$environmentId;
            
            $pharPath = $appPath.'/data/run/Df-'.$timestamp.'.phar';
            $tempPath = $appPath.'/data/packageBuild';
            
            
            
            $umask = umask(0);
            
            if(!is_dir(dirname($pharPath))) {
                mkdir(dirname($pharPath), 0777, true);
            }
            
            if(is_dir($tempPath)) {
                if(!core\io\Util::emptyDir($tempPath)) {
                    throw new \Exception(
                        'Unable to empty existing temp build path'
                    );
                }
            } else {
                mkdir($tempPath, 0777);
            }
            
            mkdir($tempPath.'/apex/', 0777, true);
            
            
            // Generate Df.php
            $dfFile = file_get_contents(df\Launchpad::ROOT_PATH.'/Df.php');
            
            $dfFile = str_replace('IS_COMPILED = false', 'IS_COMPILED = true', $dfFile);
            $dfFile = str_replace('IN_PHAR = false', 'IN_PHAR = true', $dfFile);
            
            file_put_contents($tempPath.'/Df.php', $dfFile);
            
            
            // Copy packages
            foreach(array_reverse($this->_loader->getPackages()) as $package) {
                if($package->name == 'app') {
                    foreach(scandir($package->path) as $entry) {
                        if(!in_array($entry, self::$_appExport)) {
                            continue;
                        }
                        
                        if($entry == 'libraries') {
                            core\io\Util::copyDirInto($package->path.'/'.$entry, $tempPath);
                            continue;
                        }
                        
                        if(is_dir($package->path.'/'.$entry)) {
                            core\io\Util::copyDir($package->path.'/'.$entry, $tempPath.'/apex/'.$entry);
                        }
                    }
                } else {
                    if(is_dir($package->path.'/libraries')) {
                        core\io\Util::copyDirInto($package->path.'/libraries', $tempPath);
                    }
                    
                    if(file_exists($package->path.'/Package.php')) {
                        if(!is_dir($tempPath.'/apex/packages/'.$package->name)) {
                            mkdir($tempPath.'/apex/packages/'.$package->name, 0777, true);
                        }
                        
                        copy($package->path.'/Package.php', $tempPath.'/apex/packages/'.$package->name.'/Package.php');
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
            
            //core\io\Util::deleteDir($tempPath);
            
            
            
            // Entry point
            $entryPath = $appPath.'/entry/'.$environmentId.'.'.$environmentMode.'.php';
            
            if(is_file($entryPath)) {
                throw new \Exception('Entry file already exists');
            }
            
            $data = '<?php'."\n".
                    'require_once \'phar://\'.dirname(__DIR__).\'/data/run/Df-'.$timestamp.'.phar\';'."\n".
                    'df\\Launchpad::runAs(\''.$environmentId.'\', \''.$environmentMode.'\', dirname(__DIR__));';
            
            
            file_put_contents($entryPath, $data);
        } catch(\Exception $e) {
            umask($umask);
            throw $e;
        }
        
        umask($umask);
    }
}
