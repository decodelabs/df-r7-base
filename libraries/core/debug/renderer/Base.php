<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\renderer;

use df;
use df\core;

abstract class Base implements core\debug\IRenderer {

    protected $_context;
    protected $_stats = [];

    public function __construct(core\debug\IContext $context) {
        $this->_context = $context;

        $this->_stats['Date'] = gmdate('d M Y H:i:s');
        $this->_stats['Time'] = $this->_formatRunningTime($context->runningTime);

        if($app = df\Launchpad::$application) {
            $this->_stats['Mode'] = $app->getRunMode();
        }

        if(function_exists('memory_get_peak_usage')) {
            $this->_stats['Memory'] = round((memory_get_peak_usage() / (1024 * 1024)), 2).'mb / '.
                round((memory_get_usage() / (1024 * 1024)), 2).'mb';
        }

        $this->_stats['Includes'] = count(get_included_files());

        if(class_exists('df\\core\\Loader', false)) {
            $this->_stats['Includes'] .= ' / '.core\loader\Base::getTotalIncludeMisses();
        }

        if(class_exists('df\\opal\\rdbms\\adapter\\statement\\Base', false)) {
            $this->_stats['Queries'] = \df\opal\rdbms\adapter\statement\Base::getQueryCount();
        }

        $caches = [];

        if(extension_loaded('apcu')) {
            $caches[] = 'apcu';
        }

        if(extension_loaded('memcache')) {
            $caches[] = 'memcache';
        }

        if(!empty($caches)) {
            $this->_stats['Caches'] = implode(', ', $caches);
        }
    }

    public function getStats() {
        return $this->_stats;
    }

    protected function _formatRunningTime(float $seconds): string {
        if($seconds > 60) {
            return number_format($seconds / 60, 0).':'.number_format($seconds % 60);
        } else if($seconds > 1) {
            return number_format($seconds, 3).' s';
        } else if($seconds > 0.0005) {
            return number_format($seconds * 1000, 3).' ms';
        } else {
            return number_format($seconds * 1000, 5).' ms';
        }
    }

    protected function _getNormalizedIncludeList() {
        $output = [];

        foreach(get_included_files() as $file) {
            $output[] = core\fs\Dir::stripPathLocation($file);
        }

        return $output;
    }

    protected function _getNodeLocation(core\log\INode $node) {
        return $this->_normalizeLocation($node->getFile(), $node->getLine());
    }

    protected function _normalizeLocation($file, $line) {
        return core\fs\Dir::stripPathLocation($file).' : '.$line;
    }

    protected function _normalizeFilePath($path) {
        return core\fs\Dir::stripPathLocation($path);
    }

    protected function _getObjectInheritance($object) {
        if(!is_object($object)) {
            return [];
        }

        $reflection = new \ReflectionClass($object);
        $list = [];

        while($reflection = $reflection->getParentClass()) {
            $list[] = $this->_normalizeObjectClass($reflection->getName());
        }

        return $list;
    }

    protected function _getObjectClass($object) {
        return $this->_normalizeObjectClass(get_class($object));
    }

    protected function _normalizeObjectClass(string $class): string {
        $name = [];
        $parts = explode(':', $class);

        while(!empty($parts)) {
            $part = trim(array_shift($parts));

            if(preg_match('/^class@anonymous(.+)(\(([0-9]+)\))/', $part, $matches)) {
                $name[] = $this->_normalizeLocation($matches[1], $matches[3] ?? null);
            } else if(preg_match('/^eval\(\)\'d/', $part)) {
                $name = ['eval[ '.implode(' : ', $name).' ]'];
            } else {
                $name[] = $part;
            }
        }

        return implode(' : ', $name);
    }

    protected function _normalizeExceptionTypes(\Throwable $exception): array {
        $reflection = new \ReflectionClass($exception);
        $output = [];

        foreach($reflection->getInterfaces() as $name => $interface) {
            $parts = explode('\\', $name);
            $topName = array_pop($parts);

            if(!preg_match('/^E[A-Z][a-zA-Z0-9_]+$/', $topName) && ($topName !== 'IError' || $name === 'df\\core\\IError')) {
                continue;
            }

            if(implode('\\', $parts) == 'df\\core') {
                array_unshift($output, $topName);
            } else {
                $output[] = $name;
            }
        }

        return $output;
    }
}
