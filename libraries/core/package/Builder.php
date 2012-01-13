<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\package;

use df;
use df\core;

class Builder {
    
    private static $_appExport = [
        'share', 'store'
    ];
    
    
    protected $_loader;
    
    public function __construct(core\ILoader $loader) {
        $this->_loader = $loader;
    }
    
    public function buildTestingInstallation($path=null) {
        ini_set('phar.readonly', 0);
        
        if(df\Launchpad::IN_PHAR) {
            throw new \Exception(
                'Cannot build testing installation from a phar build'
            );
        }
        
        if($path === null) {
            $path = df\Launchpad::$applicationPath.'-testing';
        }
        
        $pharPath = $path.'/Df.phar';
        
        if(is_dir($path)) {
            $isEmpty = core\io\Util::isDirEmpty($path);
            
            if(!$isEmpty) {
                if(!is_file($pharPath)) {
                    throw new \Exception(
                        'The testing installation build path appears to contain something that shouldn\'t be deleted'
                    );
                }
                
                if(!core\io\Util::emptyDir($path)) {
                    throw new \Exception(
                        'Unable to empty existing testing installation build path'
                    );
                }
            }
        }
        
        umask(0);
        
        if(!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        
        
        $tempPath = df\Launchpad::$applicationPath.'/packageBuild';
        
        if(is_dir($tempPath)) {
            if(!core\io\Util::isDirEmpty($tempPath)) {
                throw new \Exception(
                    'Testing installation temp path already exists'
                );
            }
        } else {
            mkdir($tempPath, 0777);
        }
        
        mkdir($tempPath.'/apex/', 0777, true);
        
        copy(df\Launchpad::ROOT_PATH.'/Df.php', $tempPath.'/Df.php');
        
        foreach($this->_loader->getPackages() as $package) {
            if($package->name == 'app') {
                foreach(scandir($package->path) as $entry) {
                    if($entry == '.' 
                    || $entry == '..' 
                    || $entry == 'entry' 
                    || $entry == 'packageBuild'
                    || in_array($entry, self::$_appExport)) {
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
            }
        }
        
        
        $phar = new \Phar($pharPath);
        $phar->setDefaultStub('Df.php', 'Df.php');
        $phar->buildFromDirectory($tempPath);
        
        core\io\Util::deleteDir($tempPath);
        
        
        
        // Entry point
        mkdir($path.'/entry', 0777);
        
        $entryDir = new \DirectoryIterator(df\Launchpad::$applicationPath.'/entry');
        
        foreach($entryDir as $entry) {
            if($entry->isDot() || $entry->isDir()) {
                continue;
            }
            
            $envId = substr($entry->getFilename(), 0, -4);
            
            $data = '<?php'."\n".
                'require_once \'phar://\'.dirname(__DIR__).\'/Df.phar\';'."\n".
                'df\\Launchpad::runAs(\''.$envId.'\', dirname(__DIR__));';
                
            file_put_contents($path.'/entry/'.$envId.'.php', $data);
        }
        
        unset($entryDir);
        
        
        // Storage
        foreach(self::$_appExport as $name) {
            if(is_dir(df\Launchpad::$applicationPath.'/'.$name)) {
                core\io\Util::copyDir(df\Launchpad::$applicationPath.'/'.$name, $path.'/'.$name);
            }
        }
        
        // TODO: clear baseHttpPath from config
    }
}
