<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\menu\source;

use df;
use df\core;
use df\arch;
    
class Directory extends Base {

    public function loadMenu(core\uri\Url $id) {
    	$parts = $id->path->toArray();
    	$name = ucfirst(array_pop($parts));

    	if(isset($parts[0]{0}) && $parts[0]{0} == arch\Request::AREA_MARKER) {
    		$area = ltrim(array_shift($parts), arch\Request::AREA_MARKER);
    	} else {
    		$area = arch\Request::DEFAULT_AREA;
    	}
    	
    	$classBase = 'df\\apex\\directory\\'.$area;
    	$sharedClassBase = 'df\\apex\\directory\\'.$area;
    	$baseId = 'Directory://';

    	if(!empty($parts)) {
    		$classBase .= '\\'.implode('\\', $parts);
    		$sharedClassBase .= '\\'.implode('\\', $parts);
    		$baseId .= implode('/', $parts);
    	}

    	$classBase .= '\\_menus\\'.$name;
    	$sharedClassBase .= '\\_menus\\'.$name;
    	$baseId .= '/'.$name;


    	$menus = array();

    	foreach(df\Launchpad::$loader->getPackages() as $package) {
    		$packageName = core\string\Manipulator::formatId($package->name);

    		if(class_exists($classBase.'_'.$packageName)) {
    			$class = $classBase.'_'.$packageName;
    		} else if(class_exists($sharedClassBase.'_'.$packageName)) {
    			$class = $sharedClassBase.'_'.$packageName;
    		} else {
    			continue;
    		}


    		$menus[$name.'_'.$packageName] = new $class($this->_context, $baseId.'_'.$packageName);
    	}

    	if(class_exists($classBase)) {
    		$output = new $classBase($this->_context, $baseId);
    	} else if(class_exists($sharedClassBase)) {
    		$output = new $sharedClassBase($this->_context, $baseId);
    	} else if(empty($menus)) {
    		throw new arch\menu\SourceNotFoundException(
    			'Directory menu '.$baseId.' could not be found'
			);
    	} else {
    		$output = new arch\menu\Base($this->_context, $baseId);
    	}

    	foreach($menus as $menu) {
    		$output->addDelegate($menu);
    	}

    	return $output;
    }

    public function loadAllMenus(array $whiteList=null) {
    	core\stub($whiteList);
    }
}