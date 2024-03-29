<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\loader;

use DecodeLabs\Genesis;

use df\core;

class Development extends Base
{
    public function getClassSearchPaths(string $class): ?array
    {
        $parts = explode('\\', $class);

        if (array_shift($parts) != 'df') {
            return null;
        }

        if (!$library = array_shift($parts)) {
            return null;
        }

        $output = [];

        if ($library == 'apex') {
            $section = array_shift($parts);
            $pathName = implode('/', $parts);

            switch ($section) {
                case 'packages':
                    $packageName = $pathPackageName = (string)array_shift($parts);
                    $pathName = implode('/', $parts);
                    $location = dirname(Genesis::$build->path);

                    if (basename(dirname($location)) === 'vendor') {
                        $pathPackageName = strtolower($packageName);
                    }

                    $output[] = $location . '/' . $pathPackageName . '/' . $pathName . '.php';
                    $output[] = $location . '/r7-' . $pathPackageName . '/' . $pathName . '.php';
                    return $output;

                default:
                    foreach ($this->_packages as $package) {
                        $output[] = $package->path . '/' . $section . '/' . $pathName . '.php';
                    }

                    array_unshift($parts, $section);
                    //return $output;
            }
        }

        $fileName = (string)array_pop($parts);
        $basePath = $library;

        if (!empty($parts)) {
            $basePath .= '/' . implode('/', $parts);
        }

        if (false !== ($pos = strpos($fileName, '_'))) {
            $fileName = substr($fileName, 0, $pos);
        }

        $paths = [
            $basePath . '/' . $fileName . '.php',
            $basePath . '/_manifest.php'
        ];

        foreach ($this->_packages as $package) {
            foreach ($paths as $path) {
                $output[] = $package->path . '/libraries/' . $path;
            }
        }

        return $output;
    }

    public function getFileSearchPaths(string $path): array
    {
        $path = core\uri\Path::normalizeLocal($path);

        $parts = explode('/', $path);
        $output = [];

        if (!$library = array_shift($parts)) {
            foreach ($this->_packages as $package) {
                $output[] = $package->path . '/libraries/';
            }

            return $output;
        }

        $pathName = implode('/', $parts);

        if ($library == 'apex') {
            foreach ($this->_packages as $package) {
                $output[] = $package->path . '/' . $pathName;
            }
        } else {
            foreach ($this->_packages as $package) {
                $output[] = $package->path . '/libraries/' . $library . '/' . $pathName;
            }
        }

        return $output;
    }
}
