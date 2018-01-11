<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\navigation\menu\source;

use df;
use df\core;
use df\arch;
use df\flex;

class Directory extends Base implements arch\navigation\menu\IListableSource {

    public function loadMenu(core\uri\Url $id) {
        $parts = $id->path->getRawCollection();
        $name = ucfirst(array_pop($parts));

        $nameParts = explode('_', $name, 2);
        $parentName = array_shift($nameParts);
        $subId = array_shift($nameParts);

        if(isset($parts[0]{0}) && $parts[0]{0} == arch\Request::AREA_MARKER) {
            $area = ltrim(array_shift($parts), arch\Request::AREA_MARKER);
        } else {
            $area = arch\Request::DEFAULT_AREA;
        }

        $classBase = 'df\\apex\\directory\\'.$area;
        $sharedClassBase = 'df\\apex\\directory\\'.$area;
        $baseId = 'Directory://'.arch\Request::AREA_MARKER.$area;

        if(!empty($parts)) {
            $classBase .= '\\'.implode('\\', $parts);
            $sharedClassBase .= '\\'.implode('\\', $parts);
            $baseId .= '/'.implode('/', $parts);
        }

        $classBase .= '\\_menus\\'.$name;
        $sharedClassBase .= '\\_menus\\'.$name;
        $baseId .= '/'.$name;


        $menus = [];

        foreach(df\Launchpad::$loader->getPackages() as $package) {
            $packageName = ucfirst($package->name);

            if(class_exists($classBase.'_'.$packageName)) {
                $class = $classBase.'_'.$packageName;
            } else if(class_exists($sharedClassBase.'_'.$packageName)) {
                $class = $sharedClassBase.'_'.$packageName;
            } else {
                continue;
            }

            $menus[$name.'_'.$packageName] = (new $class($this->context, $baseId.'_'.$packageName))
                ->setSubId($packageName);
        }

        try {
            $contextRequest = arch\Request::AREA_MARKER.$area.'/';

            if(!empty($parts)) {
                $contextRequest .= implode('/', $parts).'/';
            }

            $contextRequest .= lcfirst($name);
            $context = new arch\Context(new arch\Request($contextRequest));

            $scaffold = arch\scaffold\Base::factory($context);
            $scaffoldId = $baseId.'__scaffold';
            $menus[$scaffoldId] = $scaffold->loadMenu($name, $scaffoldId);
        } catch(arch\scaffold\IError $e) {}


        if(class_exists($classBase)) {
            $output = new $classBase($this->context, $baseId);
        } else if(class_exists($sharedClassBase)) {
            $output = new $sharedClassBase($this->context, $baseId);
        } else if(empty($menus)) {
            throw core\Error::{'arch/navigation/ESourceNotFound,ENotFound'}(
                'Directory menu '.$baseId.' could not be found'
            );
        } else {
            $output = new arch\navigation\menu\Base($this->context, $baseId);
        }


        $output->setSubId($subId);

        foreach($menus as $menu) {
            $output->addDelegate($menu);
        }

        return $output;
    }

    public function loadAllMenus(array $whiteList=null) {
        return $this->loadIds($this->getMenuIds(), $whiteList);
    }

    public function loadIds(array $ids, array $whiteList=null) {
        $output = [];

        foreach($ids as $id) {
            if($whiteList !== null
            && !in_array($id, $whiteList)) {
                continue;
            }

            $parts = explode('/', ltrim($id, arch\Request::AREA_MARKER));
            $name = array_pop($parts);

            $classBase = 'df\\apex\\directory\\'.implode('\\', $parts).'\\_menus';
            $class = $classBase.'\\'.$name;

            if(!class_exists($class)) {
                continue;
            }

            $idObj = arch\navigation\menu\Base::normalizeId($id);
            $idString = (string)$idObj;
            $output[$idString] = $menu = new $class($this->context, $idObj);


            // Load in base menus
            if(false !== strpos($name, '_')) {
                $parts = explode('_', $idString);
                $packageName = array_pop($parts);

                $menu->setSubId($packageName);

                $idString = implode('_', $parts);

                if(!isset($output[$idString])) {
                    $idObj = clone $idObj;

                    $output[$idString] = $menu = new arch\navigation\menu\Base(
                        $this->context,
                        arch\navigation\menu\Base::normalizeId($idString)
                    );
                }
            }
        }

        ksort($output);

        return $output;
    }

    public function loadListedMenus($areas=null) {
        return $this->loadIds($this->getMenuIds($areas));
    }

    public function loadNestedMenus($areas=null) {
        $flatList = $this->loadIds($this->getMenuIds($areas));
        $index = $output = [];

        foreach($flatList as $id => $menu) {
            $packageName = $menu->getSubId();

            if($packageName === null) {
                $packageName = '__default';
            } else {
                $parts = explode('_', $id);
                array_pop($parts);
                $id = implode('_', $parts);
            }

            $index[$id][$packageName] = $menu;
        }

        foreach($index as $id => $set) {
            if(isset($set['__default'])) {
                $top = $set['__default'];
                unset($set['__default']);
            } else {
                $top = new arch\navigation\menu\Base(
                    $this->context,
                    arch\navigation\menu\Base::normalizeId($id)
                );
            }

            foreach($set as $delegate) {
                $top->addDelegate($delegate);
            }

            $output[(string)$top->getId()] = $top;
        }

        return $output;
    }

    public function getMenuIds($areas=null) {
        $cache = arch\navigation\menu\Cache::getInstance();
        $cacheId = 'Directory://__ID_LIST__';

        if(!$cache->has($cacheId)
        || null === ($list = $cache->get($cacheId))) {
            $list = [];
            $paths = df\Launchpad::$loader->getFileSearchPaths('apex/directory');

            foreach($paths as $path) {
                if(!is_dir($path)) {
                    continue;
                }

                $dir = new \RecursiveDirectoryIterator($path);
                $it = new \RecursiveIteratorIterator($dir);
                $regex = new \RegexIterator($it, '/^'.preg_quote($path, '/').'\/(.+)\/_menus\/(.+)\.php$/i', \RecursiveRegexIterator::GET_MATCH);

                foreach($regex as $item) {
                    $list[] = arch\Request::AREA_MARKER.$item[1].'/'.$item[2];
                }
            }

            $cache->set($cacheId, $list);
        }

        if($areas !== null) {
            if(!is_array($areas)) {
                $areas = [$areas];
            }

            $temp = $list;
            $list = [];

            foreach($temp as $id) {
                $parts = explode('/', $id);
                $area = trim($parts[0], arch\Request::AREA_MARKER);

                if(!in_array($area, $areas)) {
                    continue;
                }

                $list[] = $id;
            }
        }

        return $list;
    }

    public function getAreaOptionList() {
        $ids = $this->getMenuIds();
        $output = [];

        foreach($ids as $id) {
            $parts = explode('/', $id);
            $area = ltrim(array_shift($parts), arch\Request::AREA_MARKER);

            if(!isset($output[$area])) {
                $output[$area] = flex\Text::formatName($area);
            }
        }

        return $output;
    }
}
