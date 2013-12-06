<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch;

use df;
use df\core;
use df\arch;
use df\user;

class Request extends core\uri\Url implements IRequest, core\IDumpable {
    
    use user\TAccessLock;
    
    const AREA_MARKER = '~';
    const DEFAULT_AREA = 'front';
    const DEFAULT_CONTROLLER = '';
    const DEFAULT_ACTION = 'index';
    const DEFAULT_TYPE = 'html';
    
    const REDIRECT_FROM = 'rf';
    const REDIRECT_TO = 'rt';
    
    protected $_scheme = 'directory';
    protected $_defaultAccess = null;
    
    public static function factory($url) {
        if($url instanceof IRequest) {
            return $url;
        }

        $class = get_called_class();
        return new $class($url);
    }
    
    public function import($url='') {
        if($url !== null) {
            $this->reset();
        }
        
        if($url === '' || $url === null) {
            return $this;
        }
        
        if($url instanceof self) {
            $this->_scheme = $url->_scheme;
            
            if($url->_path !== null) {
                $this->_path = clone $url->_path;
            }
            
            if($url->_query !== null) {
                $this->_query = clone $url->_query;
            }
            
            $this->_fragment = $url->_fragment;
            
            return $this;
        }

        if(!is_string($url)) {
            $url = (string)$url;
            //core\dump($url);
        }
        
        
        // Fragment
        $parts = explode('#', $url, 2);
        $url = array_shift($parts);
        $this->setFragment(array_shift($parts));
        
        // Query
        $parts = explode('?', $url, 2);
        $url = array_shift($parts);
        $this->setQuery(array_shift($parts));
        
        // Scheme
        $parts = explode('://', $url, 2);
        $url = array_pop($parts);
        $this->_scheme = 'directory';
        
        if(!empty($url)) {
            $this->setPath($url);
        }
        
        if($this->_path && $this->_path->get(0) == '~') {
            if($context = arch\Context::getCurrent()) {
                $this->setArea($context->request->getArea());
            } else {
                $this->setArea(static::DEFAULT_AREA);
            }
        }
        
        return $this;
    }


// Area
    public function setArea($area) {
        $area = static::AREA_MARKER.trim($area, static::AREA_MARKER);
        $path = $this->getPath();
        
        if(substr($path[0], 0, 1) == static::AREA_MARKER) {
            if($area == '~'.static::DEFAULT_AREA) {
                $path->remove(0);
            } else {
                $path->set(0, $area);
            }
        } else if($area != '~'.static::DEFAULT_AREA) {
            $path->put(0, $area);
        }
        
        return $this;
    }
    
    public function getArea() {
        if(!$this->_path) {
            return static::DEFAULT_AREA;
        }
        
        $area = $this->_path->get(0);
        
        if(substr($area, 0, 1) != static::AREA_MARKER) {
            return static::DEFAULT_AREA;
        }
        
        return $this->formatArea($area);
    }
    
    public function isArea($area) {
        return $this->getArea() == $this->formatArea($area);
    }
    
    public static function getDefaultArea() {
        return static::DEFAULT_AREA;
    }
    
    public function isDefaultArea() {
        return $this->getArea() == static::DEFAULT_AREA;
    }
    
    public static function formatArea($area) {
        return lcfirst(ltrim($area, static::AREA_MARKER));
    }
    
    
// Controller
    public function setController($controller) {
        $path = $this->getPath();
        
        if(empty($controller)) {
            $controller = static::DEFAULT_CONTROLLER;
        }
        
        $parts = $this->_path->toArray();
        $start = 0;
        $end = count($parts);
        
        // Strip area
        if(isset($parts[0]) && substr($parts[0], 0, 1) == static::AREA_MARKER) {
            $start++;
        }
        
        // Strip fileName
        if(!$this->_path->shouldAddTrailingSlash()) {
            $end--;
        }
        
        for($i = $end - 1; $i >= $start; $i--) {
            $path->remove($i);
        }
        
        if(!empty($controller)) {
            $path->put($start, trim($controller, '/'));
        }
        
        return $this;
    }
    
    public function getController() {
        return $this->formatController($this->_getControllerParts());
    }

    public function getRawController() {
        return implode('/', $this->_getControllerParts());
    }

    protected function _getControllerParts() {
        if(!$this->_path) {
            return [static::DEFAULT_CONTROLLER];
        }
        
        $parts = $this->_path->toArray();
        
        // Strip area
        if(isset($parts[0]) && substr($parts[0], 0, 1) == static::AREA_MARKER) {
            array_shift($parts);
        }
        
        // Strip fileName
        if(!$this->_path->shouldAddTrailingSlash()) {
            array_pop($parts);
        }
        
        if(empty($parts)) {
            return [static::DEFAULT_CONTROLLER];
        }

        return $parts;
    }
    
    public function isController($controller) {
        return $this->getController() == $this->formatController($controller);
    }
    
    public static function getDefaultController() {
        return static::DEFAULT_CONTROLLER;
    }
    
    public function isDefaultController() {
        return $this->getController() == static::DEFAULT_CONTROLLER;
    }
    
    public static function formatController($controller) {
        if($controller == '') {
            return $controller;
        }
        
        if(!is_array($controller)) {
            $controller = explode('/', $controller);
        }
        
        foreach($controller as $i => $part) {
            if($part != '~') {
                $controller[$i] = lcfirst(
                    str_replace(' ', '', ucwords(
                        preg_replace('/[^a-zA-Z0-9_ ]/', '', str_replace(
                            array('-', '.', '+'), ' ', $part
                        ))
                    ))
                );
            }
        }
        
        return implode('/', $controller);
    }

// Action
    public function setAction($action) {
        if(!strlen($action)) {
            $action = static::DEFAULT_ACTION;
        }
        
        $this->getPath()->setFileName(trim($action, '/'));
        return $this;
    }
    
    public function getAction() {
        if(!$this->_path || $this->_path->shouldAddTrailingSlash() || !strlen($fileName = $this->_path->getFileName())) {
            return static::DEFAULT_ACTION;
        }
        
        return $this->formatAction($fileName);
    }

    public function getRawAction() {
        if(!$this->_path || $this->_path->shouldAddTrailingSlash() || !strlen($fileName = $this->_path->getFileName())) {
            return static::DEFAULT_ACTION;
        }
        
        return $fileName;
    }
    
    public function isAction($action) {
        return $this->getAction() == $this->formatAction($action);
    }
    
    public static function getDefaultAction() {
        return static::DEFAULT_ACTION;
    }
    
    public function isDefaultAction() {
        return $this->getAction() == static::DEFAULT_ACTION;
    }
    
    public static function formatAction($action) {
        if($action == '~') {
            return $action;
        }
        
        return lcfirst(
            str_replace(' ', '', ucwords(
                preg_replace('/[^a-zA-Z0-9_ ]/', '', str_replace(
                    array('-', '.', '+'), ' ', $action
                ))
            ))
        );
    }
    
    
    
// Type
    public function setType($type) {
        if(empty($type)) {
            $type = null;
        }
        
        $path = $this->getPath();
        
        if($path->shouldAddTrailingSlash()) {
            if($type !== null) {
                $path->setFileName(static::DEFAULT_ACTION.'.'.$type);
                return $this;
            }
        } else {
            $path->setExtension($type);
        }
        
        return $this;
    }
    
    public function getType() {
        if(!$this->_path || !strlen($extension = $this->_path->getExtension())) {
            $extension = static::DEFAULT_TYPE;   
        }

        return $this->formatType($extension);
    }
    
    public function isType($type) {
        return $this->getType() == $this->formatType($type);
    }
    
    public static function getDefaultType() {
        return static::DEFAULT_TYPE;
    }
    
    public function isDefaultType() {
        return $this->getType() == $this->getDefaultType();
    }
    
    public static function formatType($type) {
        return str_replace(' ', '', ucwords(
            preg_replace('/[^a-zA-Z0-9_ ]/', '', str_replace(
                array('-', '.', '+'), ' ', $type
            ))
        ));
    }


    public function getComponents() {
        $literal = $this->getLiteralPathArray();

        return [
            'action' => array_pop($literal),
            'area' => array_shift($literal),
            'controller' => implode('/', $literal),
            'controllerParts' => $literal,
            'query' => $this->getQuery()
        ];
    }

    public function toSlug() {
        if($this->_path) {
            $parts = $this->_path->toArray();
        } else {
            $parts = array();
        }
        
        if(isset($parts[0]) && $parts[0] == '~front') {
            array_shift($parts);
        }

        if(empty($parts)) {
            return '/';
        }

        $action = array_pop($parts);

        if(false !== ($pos = strpos($action, '.'))) {
            $action = substr($action, 0, $pos);
        }

        if(strlen($action)) {
            $parts[] = $action;
        }

        $output = implode('/', $parts);

        if(!strlen($output)) {
            return '/';
        }

        return $output;
    }
    
    
// Match
    public function eq($request) {
        $request = self::factory($request);

        if($this->_scheme != $request->_scheme) {
            return false;
        }

        if($this->getLiteralPathString() != $request->getLiteralPathString()) {
            return false;
        }

        if($this->_query) {
            $rQuery = $request->getQuery();

            foreach($this->_query as $key => $value) {
                if(!isset($rQuery->{$key}) || $rQuery[$key] != $value) {
                    return false;
                }
            }
        }

        return true;
    }

    public function matches($request) {
        $request = self::factory($request);

        if($this->_scheme != $request->_scheme) {
            return false;
        }

        if($this->getLiteralPathString() != $request->getLiteralPathString()) {
            return false;
        }

        if($this->_query) {
            foreach($this->_query as $key => $value) {
                if(!$request->_query) {
                    return false;
                }

                if(!isset($request->_query->{$key}) || $request->_query[$key] != $value) {
                    return false;
                }
            }
        }

        return true;
    }
    
    public function containsPath($request) {
        $request = self::factory($request);

        if($this->_scheme != $request->_scheme) {
            return false;
        }

        $rpString = $request->getLiteralPathString();
        $tpString = $this->getLiteralPathString();

        if(0 !== stripos($rpString, $tpString)) {
            return false;
        }

        return true;
    }

    public function contains($request) {
        $request = self::factory($request);

        if($this->_scheme != $request->_scheme) {
            return false;
        }
    
        $rpString = $request->getLiteralPathString();
        $tpString = $this->getLiteralPathString();

        if(0 !== stripos($rpString, $tpString)) {
            return false;
        }

        if($rpString == $tpString && $this->_query) {
            foreach($this->_query as $key => $value) {
                if(!$request->_query) {
                    return false;
                }

                if(!isset($request->_query->{$key}) || $request->_query[$key] != $value) {
                    return false;
                }
            }
        }

        return true;
    }

    public function isPathWithin($request) {
        return self::factory($request)->containsPath($this);
    }

    public function isWithin($request) {
        return self::factory($request)->contains($this);
    }

    public function getLiteralPath() {
        return new core\uri\Path($this->getLiteralPathArray(), false);
    }
    
    public function getLiteralPathArray() {
        if($this->_path) {
            $parts = $this->_path->toArray();
            $addTrailingSlash = $this->_path->shouldAddTrailingSlash();
        } else {
            $parts = array();
            $addTrailingSlash = true;
        }
        
        if(!isset($parts[0]) || substr($parts[0], 0, 1) != '~') {
            array_unshift($parts, static::AREA_MARKER.static::DEFAULT_AREA);
        }
        
        if($addTrailingSlash) {
            $parts[] = static::DEFAULT_ACTION;//.'.'.static::DEFAULT_TYPE;
        }
        
        return $parts;
    }

    public function getLiteralPathString() {
        return implode('/', $this->getLiteralPathArray());
    }
    
    public function toString() {
        $output = 'directory://'.implode('/', $this->getLiteralPathArray());
        
        if($this->_query) {
            $output .= '?'.$this->_query->toArrayDelimitedString();
        }
        
        if($this->_fragment) {
            $output .= '#'.$this->_fragment;
        }
        
        return $output;
    }
    
    public function getDirectoryLocation() {
        $output = $this->getArea();
        
        if($controller = $this->getController()) {
            $output .= '/'.$controller;
        }

        return $output;
    }
    
    
    public function convertToHttpUrl($scheme, $domain, $port, array $basePath) {
        if($this->_isJustFragment) {
            return new halo\protocol\http\Url('#'.$this->_fragment);
        }
        
        $path = null;
        
        if($this->_path) {
            $path = clone $this->_path;
            
            if($path->get(0) == '~'.static::DEFAULT_AREA) {
                $path->shift();
            }
            
            if(!empty($basePath)) {
                $path->unshift($basePath);
            }
        } else if(!empty($basePath)) {
            $path = new core\uri\Path($basePath);
            $path->shouldAddTrailingSlash(true);
        }
        
        $output = new halo\protocol\http\Url();
        $output->_scheme = $scheme;
        $output->_domain = $domain;
        $output->_port = $port;
        
        if(!empty($path)) {
            $output->_path = $path;
        }
        
        if(!empty($this->_query)) {
            $output->_query = $this->_query;
        }
        
        if(!empty($this->_fragment)) {
            $output->_fragment = $this->_fragment;
        }
        
        return $output;
    }


// Redirect
    public function encode() {
        return base64_encode(substr($this->toString(), 12));
    }
    
    public static function decode($str) {
        return new self(base64_decode($str));
    }
    
    
    public function setRedirect($from, $to=null) {
        $this->setRedirectFrom($from);
        $this->setRedirectTo($to);
        
        return $this;
    }
    
    public function hasRedirectFrom() {
        if(!$this->_query) {
            return false;
        }
        
        return $this->_query->__isset(self::REDIRECT_FROM);
    }
    
    public function hasRedirectTo() {
        if(!$this->_query) {
            return false;
        }
        
        return $this->_query->__isset(self::REDIRECT_TO);
    }
    
    public function setRedirectFrom($from) {
        if($from === null) {
            if($this->_query) {
                $this->_query->remove(self::REDIRECT_FROM);
            }
            
            return $this;
        }
        
        $from = self::factory($from);
        $this->getQuery()->{self::REDIRECT_FROM} = $from->encode();
        
        return $this;
    }
    
    public function getRedirectFrom() {
        if(!$this->hasRedirectFrom()) {
            return null;
        }
        
        return self::decode($this->_query[self::REDIRECT_FROM]);
    }
    
    public function setRedirectTo($to) {
        if($to === null) {
            if($this->_query) {
                $this->_query->remove(self::REDIRECT_TO);
            }
            
            return $this;
        }
        
        $to = self::factory($to);
        $this->getQuery()->{self::REDIRECT_TO} = $to->encode();
        
        return $this;
    }
    
    public function getRedirectTo() {
        if(!$this->hasRedirectTo()) {
            return null;
        }
        
        return self::decode($this->_query[self::REDIRECT_TO]);
    }
    
    
// Parent
    public function getParent() {
        $output = clone $this;
        $output->_query = null;
        $output->_fragment = null;
        
        $isDefaultAction = $output->isDefaultAction();
        
        if(!$output->_path->shouldAddTrailingSlash()) {
            $output->_path->pop();
            $output->_path->shouldAddTrailingSlash(true);
        }
        
        if($isDefaultAction) {
            $output->_path->pop();
        }
        
        return $output;
    }
    

// Rewrite
    public function rewriteQueryToPath($keys) {
        $optional = array();
        $keys = $this->_normalizeKeys(func_get_args(), $optional);
        $path = $this->getPath();
        $query = $this->getQuery();

        foreach($keys as $key) {
            if(null === ($value = $query->get($key))) {
                if(!in_array($key, $optional)) {
                    return $this;
                } else {
                    continue;
                }
            }

            $path->push($value);
            $query->remove($key);
        }

        return $this;
    }

    public function rewritePathToQuery($rootCount, $keys) {
        $optional = array();
        $keys = $this->_normalizeKeys(array_slice(func_get_args(), 1), $optional);
        $path = $this->getPath();
        $query = $this->getQuery();
        $parts = $path->slice((int)$rootCount);
        
        foreach($keys as $key) {
            if(empty($parts) && in_array($key, $optional)) {
                break;
            }

            $query->{$key} = array_shift($parts);
        }

        return $this;
    }

    protected function _normalizeKeys($keys, array &$optional) {
        $output = array();
        $optional = array();

        foreach($keys as $i => $key) {
            if(is_array($key)) {
                foreach($key as $innerKey) {
                    $output[] = $innerKey;
                }
            } else {
                $output[] = $key;
            }
        }

        $output = array_unique($output);

        foreach($output as $i => $key) {
            if(substr($key, -1) == '?') {
                $optional[] = $output[$i] = $key = substr($key, 0, -1);
            } else if(!empty($optional)) {
                $optional[] = $key;
            }
        }

        return $output;
    }
    
    
// Access
    public function getAccessLockDomain() {
        return 'directory';
    }
    
    public function lookupAccessKey(array $keys, $lockAction=null) {
        $parts = $this->getLiteralPathArray();
        $action = array_pop($parts);
        $basePath = implode('/', $parts);
        
        if(isset($keys[$basePath.'/'.$action])) {
            return $keys[$basePath.'/'.$action];
        } else if(isset($keys[$basePath.'/%'])) {
            return $keys[$basePath.'/%'];
        }
        
        do {
            $current = implode('/', $parts).'/*';
            
            if(isset($keys[$current])) {
                return $keys[$current];
            }
            
            array_pop($parts);
        } while(!empty($parts));
        
        return null;
    }

    public function setDefaultAccess($access) {
        $this->_defaultAccess = $access;
        return $this;
    }
    
    public function getDefaultAccess($lockAction=null) {
        if($this->_defaultAccess !== null) {
            return $this->_defaultAccess;
        }

        try {
            $context = arch\Context::factory(
                df\Launchpad::$application,
                $this
            );
            
            $action = arch\Action::factory($context);
            return $action->getDefaultAccess($lockAction);
        } catch(\Exception $e) {
            return false;
        }
    }

    public function getAccessLockId() {
        return implode('/', $this->getLiteralPathArray());
    }
    
    
// Dump
    public function getDumpProperties() {
        return $this->toString();
    }
}
