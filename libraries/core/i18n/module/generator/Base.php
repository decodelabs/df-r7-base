<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\i18n\module\generator;

use df\core;

class Base implements IGenerator {
    
    protected $_cldrPath;
    protected $_savePath;
    protected $_modules = [];
    
    public function __construct($cldrPath, $savePath=null) {
        if($cldrPath === null) {
            $cldrPath = dirname(__DIR__).'/cldr';
        }
        
        if($savePath === null) {
            $savePath = dirname(__DIR__).'/data';
        }
        
        $this->setCldrPath($cldrPath);
        $this->setSavePath($savePath);
    }
    
    public function setCldrPath($path) {
        if(!is_dir($path) || !is_dir($path.'/main')) {
            throw new RuntimeException(
                'Cldr repository could not be found at path: '.$path
            );
        }
        
        $this->_cldrPath = $path;
        return $this;
    }
    
    public function getCldrPath() {
        return $this->_cldrPath;
    }
    
    public function setSavePath($path) {
        if(!is_dir(dirname($path))) {
            throw new RuntimeException(
                'The save path does not exist!'
            );
        }
        
        $this->_savePath = $path;
        return $this;
    }
    
    public function getSavePath() {
        return $this->_savePath;
    }
    
    
    public function setModules($modules) {
        if(!is_array($modules)) {
            $modules = func_get_args();
        }
        
        $this->clearModules();
        
        foreach($modules as $module) {
            $this->addModule($module);
        }
        
        return $this;
    }
    
    public function addModules($modules) {
        if(!is_array($modules)) {
            $modules = func_get_args();
        }
        
        foreach($modules as $module) {
            $this->addModule($module);
        }
        
        return $this;
    }
    
    public function addDefinedModules() {
        return $this->addModules('Countries', 'Dates', 'Languages', 'Numbers', 'Scripts', 'Territories');
    }
    
    public function addModule($module) {
        $this->_modules[$this->_normalizeModuleName($module)] = true;
        return $this;
    }
    
    public function removeModule($module) {
        unset($this->_modules[$this->_normalizeModuleName($module)]);
        return $this;
    }
    
    public function getModules() {
        return $this->_modules;
    }
    
    public function clearModules() {
        $this->_modules = [];
        return $this;
    }
    
    protected function _normalizeModuleName($name) {
        return lcfirst(core\string\Manipulator::formatId($name));
    }
    
    
    public function generate() {
        core\io\Util::ensureDirExists($this->_savePath);
        
        $modules = [];
        
        foreach($this->_modules as $name => $t) {
            $module = core\i18n\module\Base::factory($name);
            
            if(!$module instanceof IModule) {
                throw new RuntimeException(
                    'Module '.$name.' is not capable of generating its data'
                );
            }
            
            $modules[$name] = $module;
            
            core\io\Util::ensureDirExists($this->_savePath.'/'.$name);
        }
        
        
        foreach(core\io\Util::fileMatch($this->_cldrPath.'/main/', '.*\.xml') as $file) {
            $name = substr(basename($file), 0, -4);
            $locale = new core\i18n\Locale($name);
            $doc = simplexml_load_file($file);
            
            foreach($modules as $modName => $module) {
                $data = $module->_convertCldr($locale, $doc);
                
                if(is_array($data) && !empty($data)) {
                    $this->_saveFile($modName, $name, $data);
                }
            }
        }
    }

    protected function _saveFile($moduleName, $localeName, array $data) {
        $path = $this->_savePath.'/'.$moduleName.'/'.$localeName.'.loc';
        $str = '<?php'."\n".'return '.var_export($data, true).';';
        file_put_contents($path, $str);
    }
}
