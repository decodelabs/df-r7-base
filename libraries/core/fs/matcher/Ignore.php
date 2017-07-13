<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\fs\matcher;

use df;
use df\core;

class Ignore implements core\fs\IMatcher {

    protected $_path;

    public function __construct(string $path) {
        $this->setPath($path);
    }

    public function setPath(string $path) {
        $this->_path = $path;
        return $this;
    }

    public function getPath(): string {
        return $this->_path;
    }


    public function match(array $patterns, array $blacklist=[]): iterable {
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->_path,
                \FilesystemIterator::KEY_AS_PATHNAME |
                \FilesystemIterator::CURRENT_AS_SELF |
                \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::SELF_FIRST |
            \RecursiveIteratorIterator::CATCH_GET_CHILD
        );

        foreach($it as $name => $entry) {
            $path = $entry->getSubPathname();
            $isDir = $entry->isDir();

            if(in_array($path, $blacklist)) {
                continue;
            }


            $match = false;

            foreach($patterns as $pattern) {
                $testPath = $path;

                $pattern = str_replace('**/**', '**', $pattern);

                if($pattern == '**') {
                    $pattern = '*/';
                }

                if(substr($pattern, 0, 1) == '/'
                || substr($testPath, 0, 1) == '.') {
                    $testPath = '/'.$testPath;
                }



                // Negation
                if($isNegated = (0 === strpos($pattern, '!'))) {
                    $pattern = substr($pattern, 1);
                }

                $pattern = str_replace('\\!', '!', $pattern);


                // Dir match
                if(substr($pattern, -1) == '/' && !$isDir) {
                    continue;
                }


                // Glob
                if($this->_patternMatch($testPath, $pattern)) {
                    if($isNegated) {
                        continue 2;
                    }

                    $match = true;
                    continue;
                }
            }

            if($match) {
                if($isDir) {
                    yield $path => new core\fs\Dir($entry->getPathname());
                } else {
                    yield $path => new core\fs\File($entry->getPathname());
                }
            }
        }
    }

    protected function _patternMatch(string $path, string $pattern): bool {
        $origPath = $path;


        // Any inner dir
        if(preg_match('|\*\*/|', $pattern)) {
            $sections = explode('**/', $pattern);
            $start = array_shift($sections);
            $prev = $last = null;

            if(!empty($start) && !fnmatch($start.'*', $path, \FNM_CASEFOLD)) {
                return false;
            }

            foreach($sections as $section) {
                if(!fnmatch('*/'.$section.'*', $path, \FNM_CASEFOLD)) {
                    return false;
                }

                $pathParts = explode('/', $path);
                $sectionParts = explode('/', rtrim($section, '/'));
                $next = array_shift($sectionParts);

                $last = null;

                while(!empty($pathParts)) {
                    $prev = $last;

                    if(fnmatch($next, $last = array_shift($pathParts), \FNM_CASEFOLD)) {
                        break;
                    }
                }

                while(!empty($sectionParts)) {
                    $sectionPart = array_shift($sectionParts);
                    $prev = $last;

                    if(!fnmatch($sectionPart, $last = array_shift($pathParts), \FNM_CASEFOLD)) {
                        return false;
                    }
                }

                if(empty($pathParts)) {
                    return true;
                }

                $path = implode('/', $pathParts);

                if($last !== null) {
                    $path = $last.'/'.$path;
                }
            }

            $pattern = $section;

            if($prev !== null) {
                $path = $prev.'/'.$path;
            }
        }

        // Any in
        if(preg_match('|/\*\*$|', $pattern)) {
            $pattern = substr($pattern, 0, -1);
            return fnmatch($pattern, $path, \FNM_CASEFOLD);
        }


        // Simple glob
        if(false === strpos($pattern, '/') && fnmatch($pattern, $path, \FNM_CASEFOLD)) {
            return true;
        }

        // Path glob
        if(fnmatch($pattern, $path, \FNM_CASEFOLD | \FNM_PATHNAME)) {
            return true;
        }

        return false;
    }
}
