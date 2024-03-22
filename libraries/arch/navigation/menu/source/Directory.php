<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\navigation\menu\source;

use DecodeLabs\Exceptional;
use DecodeLabs\R7\Legacy;
use df\arch;
use df\arch\scaffold\Loader as ScaffoldLoader;
use df\core;

class Directory extends Base implements arch\navigation\menu\IListableSource
{
    public function loadMenu(core\uri\IUrl $id)
    {
        $parts = $id->path->getRawCollection();
        $name = ucfirst(array_pop($parts));

        $nameParts = explode('_', $name, 2);
        $parentName = array_shift($nameParts);
        $subId = array_shift($nameParts);

        if (isset($parts[0][0]) && $parts[0][0] == arch\Request::AREA_MARKER) {
            $area = ltrim(array_shift($parts), arch\Request::AREA_MARKER);
        } else {
            $area = arch\Request::DEFAULT_AREA;
        }

        $classBase = 'df\\apex\\directory\\' . $area;
        $sharedClassBase = 'df\\apex\\directory\\' . $area;
        $baseId = 'Directory://' . arch\Request::AREA_MARKER . $area;

        if (!empty($parts)) {
            $classBase .= '\\' . implode('\\', $parts);
            $sharedClassBase .= '\\' . implode('\\', $parts);
            $baseId .= '/' . implode('/', $parts);
        }

        $classBase .= '\\_menus\\' . $name;
        $sharedClassBase .= '\\_menus\\' . $name;
        $baseId .= '/' . $name;


        $menus = [];

        foreach (Legacy::getLoader()->getPackages() as $package) {
            $packageName = ucfirst($package->name);

            if (class_exists($classBase . '_' . $packageName)) {
                $class = $classBase . '_' . $packageName;
            } elseif (class_exists($sharedClassBase . '_' . $packageName)) {
                $class = $sharedClassBase . '_' . $packageName;
            } else {
                continue;
            }

            $menus[$name . '_' . $packageName] = (new $class($this->context, $baseId . '_' . $packageName))
                ->setSubId($packageName);
        }

        try {
            $contextRequest = arch\Request::AREA_MARKER . $area . '/';

            if (!empty($parts)) {
                $contextRequest .= implode('/', $parts) . '/';
            }

            $contextRequest .= lcfirst($name);
            $context = new arch\Context(new arch\Request($contextRequest));

            $scaffold = ScaffoldLoader::fromContext($context);
            $scaffoldId = $baseId . '__scaffold';
            $menus[$scaffoldId] = $scaffold->loadMenu($name, $scaffoldId);
        } catch (arch\scaffold\Exception $e) {
        }


        if (class_exists($classBase)) {
            $output = new $classBase($this->context, $baseId);
        } elseif (class_exists($sharedClassBase)) {
            $output = new $sharedClassBase($this->context, $baseId);
        } elseif (empty($menus)) {
            throw Exceptional::NotFound(
                'Directory menu ' . $baseId . ' could not be found'
            );
        } else {
            $output = new arch\navigation\menu\Base($this->context, $baseId);
        }


        $output->setSubId($subId);

        foreach ($menus as $menu) {
            $output->addDelegate($menu);
        }

        return $output;
    }

    public function loadIds(array $ids, array $whiteList = null)
    {
        $output = [];

        foreach ($ids as $id) {
            if ($whiteList !== null
            && !in_array($id, $whiteList)) {
                continue;
            }

            $parts = explode('/', ltrim($id, arch\Request::AREA_MARKER));
            $name = (string)array_pop($parts);

            $classBase = 'df\\apex\\directory\\' . implode('\\', $parts) . '\\_menus';
            $class = $classBase . '\\' . $name;

            if (!class_exists($class)) {
                continue;
            }

            $idObj = arch\navigation\menu\Base::normalizeId($id);
            $idString = (string)$idObj;
            $output[$idString] = $menu = new $class($this->context, $idObj);


            // Load in base menus
            if (false !== strpos($name, '_')) {
                $parts = explode('_', $idString);
                $packageName = array_pop($parts);

                $menu->setSubId($packageName);

                $idString = implode('_', $parts);

                if (!isset($output[$idString])) {
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
}
