<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch;

use DecodeLabs\Dictum;
use DecodeLabs\Glitch\Dumpable;
use DecodeLabs\R7\Legacy;
use df\arch;

use df\core;
use df\link;
use df\user;

class Request extends core\uri\Url implements IRequest, Dumpable
{
    use user\TAccessLock;

    protected $_scheme = 'directory';
    protected $_defaultAccess = null;
    protected $_accessSignifiers = null;

    public static function factory($url): static
    {
        if ($url instanceof IRequest) {
            /** @var static $url */
            return $url;
        }

        return new static($url);
    }

    public function import($url = '')
    {
        if ($url instanceof self) {
            $this->_scheme = $url->_scheme;

            if ($url->_path !== null) {
                $this->_path = clone $url->_path;
            }

            if ($url->_query !== null) {
                $this->_query = clone $url->_query;
            }

            $this->_fragment = $url->_fragment;

            return $this;
        }

        if (!is_array($url)) {
            if (!is_string($url)) {
                $url = (string)$url;
            }


            // Fragment
            $parts = explode('#', $url, 2);
            $url = (string)array_shift($parts);
            $this->setFragment(array_shift($parts));

            // Query
            $parts = explode('?', $url, 2);
            $url = (string)array_shift($parts);
            $this->setQuery(array_shift($parts));

            // Scheme
            $parts = explode('://', $url, 2);
            $url = array_pop($parts);
            $this->_scheme = 'directory';
        }

        $this->setPath($url);

        if (isset($this->_path)) {
            $pathCount = count($this->_path);
            $first = $this->_path->get(0);

            if ($first == '~') {
                if ($context = Legacy::getActiveContext()) {
                    $this->setArea($context->request->getArea());
                } else {
                    $this->setArea(static::DEFAULT_AREA);
                }
            } elseif ((isset($first[0]) && $first[0] == '~' && $pathCount == 1)) {
                $this->_path->shouldAddTrailingSlash(true);
            }
        }

        return $this;
    }


    // Area
    public function setArea(string $area)
    {
        $area = static::AREA_MARKER . trim($area, static::AREA_MARKER);
        $path = $this->getPath();

        if (substr($path[0], 0, 1) == static::AREA_MARKER) {
            if ($area == '~' . static::DEFAULT_AREA) {
                $path->remove(0);
            } else {
                $path->set(0, $area);
            }
        } elseif ($area != '~' . static::DEFAULT_AREA) {
            $path->put(0, $area);
        }

        return $this;
    }

    public function getArea()
    {
        if (!$this->_path) {
            return static::DEFAULT_AREA;
        }

        $area = (string)$this->_path->get(0);

        if (substr($area, 0, 1) != static::AREA_MARKER) {
            return static::DEFAULT_AREA;
        }

        return $this->formatArea($area);
    }

    public function isArea($area): bool
    {
        return $this->getArea() == $this->formatArea($area);
    }

    public static function getDefaultArea()
    {
        return static::DEFAULT_AREA;
    }

    public function isDefaultArea(): bool
    {
        return $this->getArea() == static::DEFAULT_AREA;
    }

    public static function formatArea($area)
    {
        return lcfirst(ltrim($area, static::AREA_MARKER));
    }


    // Controller
    public function setController($controller)
    {
        $path = $this->getPath();
        $parts = $this->_path->getRawCollection();
        $start = 0;
        $end = count($parts);

        // Strip area
        if (isset($parts[0]) && substr($parts[0], 0, 1) == static::AREA_MARKER) {
            $start++;
        }

        // Strip fileName
        if (!$this->_path->shouldAddTrailingSlash()) {
            $end--;
        }

        for ($i = $end - 1; $i >= $start; $i--) {
            $path->remove($i);
        }

        if (!empty($controller)) {
            if (is_array($controller)) {
                $controller = implode('/', $controller);
            }

            $path->put($start, trim($controller, '/'));
        }

        return $this;
    }

    public function getController()
    {
        return $this->formatController($this->getRawControllerParts());
    }

    public function getControllerParts()
    {
        return $this->formatControllerParts($this->getRawControllerParts());
    }

    public function getRawController()
    {
        return implode('/', $this->getRawControllerParts());
    }

    public function getRawControllerParts()
    {
        if (!$this->_path) {
            return [];
        }

        $parts = $this->_path->getRawCollection();

        // Strip area
        if (isset($parts[0]) && substr($parts[0], 0, 1) == static::AREA_MARKER) {
            array_shift($parts);
        }

        // Strip fileName
        if (!$this->_path->shouldAddTrailingSlash()) {
            array_pop($parts);
        }

        if (empty($parts)) {
            return [];
        }

        return $parts;
    }

    public function isController($controller): bool
    {
        return $this->getController() == $this->formatController($controller);
    }

    public static function formatController($controller)
    {
        if ($controller == '') {
            return $controller;
        }

        if (!is_array($controller)) {
            $controller = explode('/', $controller);
        }

        return implode('/', self::formatControllerParts($controller));
    }

    public static function formatControllerParts(array $parts)
    {
        foreach ($parts as $i => $part) {
            if ($part != '~') {
                $parts[$i] = lcfirst(
                    (string)str_replace(' ', '', ucwords(
                        (string)preg_replace('/[^a-zA-Z0-9_ ]/', '', str_replace(
                            ['-', '.', '+'],
                            ' ',
                            (string)$part
                        ))
                    ))
                );
            }
        }

        return $parts;
    }

    // Node
    public function setNode(string $node = null)
    {
        if (!strlen((string)$node)) {
            $node = static::DEFAULT_NODE;
        }

        $node = trim($node, '/');
        $node = Dictum::actionSlug($node);
        $this->getPath()->setFileName($node);
        return $this;
    }

    public function getNode()
    {
        if (
            !$this->_path ||
            $this->_path->shouldAddTrailingSlash() ||
            !strlen($fileName = $this->_path->getFileName())
        ) {
            return static::DEFAULT_NODE;
        }

        return $this->formatNode($fileName);
    }

    public function getRawNode()
    {
        if (
            !$this->_path ||
            $this->_path->shouldAddTrailingSlash() ||
            !strlen($fileName = $this->_path->getFileName())
        ) {
            return static::DEFAULT_NODE;
        }

        return $fileName;
    }

    public function isNode($node): bool
    {
        return $this->getNode() == $this->formatNode($node);
    }

    public static function getDefaultNode()
    {
        return static::DEFAULT_NODE;
    }

    public function isDefaultNode(): bool
    {
        return $this->getNode() == static::DEFAULT_NODE;
    }

    public static function formatNode($node)
    {
        if ($node == '~') {
            return $node;
        }

        return lcfirst(
            (string)str_replace(' ', '', ucwords(
                (string)preg_replace('/[^a-zA-Z0-9_ ]/', '', str_replace(
                    ['-', '.', '+'],
                    ' ',
                    (string)$node
                ))
            ))
        );
    }



    // Type
    public function setType(string $type = null)
    {
        if (empty($type)) {
            $type = null;
        }

        $path = $this->getPath();

        if ($path->shouldAddTrailingSlash()) {
            if ($type !== null) {
                $path->setFileName(static::DEFAULT_NODE . '.' . $type);
                return $this;
            }
        } else {
            $path->setExtension($type);
        }

        return $this;
    }

    public function getType()
    {
        if (
            !$this->_path ||
            !strlen((string)($extension = $this->_path->getExtension()))
        ) {
            $extension = static::DEFAULT_TYPE;
        }

        return $this->formatType($extension);
    }

    public function isType($type): bool
    {
        return $this->getType() == $this->formatType($type);
    }

    public static function getDefaultType()
    {
        return static::DEFAULT_TYPE;
    }

    public function isDefaultType(): bool
    {
        return $this->getType() == $this->getDefaultType();
    }

    public static function formatType($type)
    {
        return (string)str_replace(' ', '', ucwords(
            (string)preg_replace('/[^a-zA-Z0-9_ ]/', '', str_replace(
                ['-', '.', '+'],
                ' ',
                (string)$type
            ))
        ));
    }


    public function getComponents()
    {
        return [
            'node' => $this->getRawNode(),
            'extension' => $this->_path->getExtension(),
            'type' => $this->getType(),
            'area' => $this->getArea(),
            'controllerParts' => $parts = $this->getRawControllerParts(),
            'controller' => implode('/', $parts),
            'query' => $this->getQuery()
        ];
    }

    public function toSlug()
    {
        if ($this->_path) {
            $parts = $this->_path->getRawCollection();
        } else {
            $parts = [];
        }

        if (isset($parts[0]) && $parts[0] == '~front') {
            array_shift($parts);
        }

        if (empty($parts)) {
            return '/';
        }

        $node = array_pop($parts);

        if (false !== ($pos = strpos($node, '.'))) {
            $node = substr($node, 0, $pos);
        }

        if (strlen((string)$node)) {
            $parts[] = $node;
        }

        $output = implode('/', $parts);

        if (!strlen($output)) {
            return '/';
        }

        return $output;
    }



    // Query
    public function offsetSet(
        mixed $key,
        mixed $value
    ): void {
        $this->getQuery()->{$key} = $value;
    }

    public function offsetGet(mixed $key): mixed
    {
        return $this->getQuery()->offsetGet($key);
    }

    public function offsetExists(mixed $key): bool
    {
        return $this->getQuery()->__isset($key);
    }

    public function offsetUnset(mixed $key): void
    {
        unset($this->getQuery()->{$key});
    }


    // Match
    public function eq($request): bool
    {
        return $this->_eq($request, true);
    }

    public function pathEq($request): bool
    {
        return $this->_eq($request, false);
    }

    protected function _eq($request, $full): bool
    {
        $request = Request::factory($request);


        if ($this->_scheme != $request->_scheme) {
            return false;
        }

        $tpString = implode('/', $this->getLiteralPathArray(false, false));
        $rpString = implode('/', $request->getLiteralPathArray(false, false));

        if ($tpString != $rpString) {
            return false;
        }

        if ($full && $this->_query) {
            foreach ($this->_query as $key => $value) {
                if (!$request->_query) {
                    return false;
                }

                if (!isset($request->_query->{$key}) || $request->_query[$key] != $value->getValue()) {
                    return false;
                }
            }
        }

        return true;
    }




    public function matches($request): bool
    {
        return $this->_matches($request, true);
    }

    public function matchesPath($request): bool
    {
        return $this->_matches($request, false);
    }

    protected function _matches($request, $full): bool
    {
        $request = Request::factory($request);

        if ($this->_scheme != $request->_scheme) {
            return false;
        }

        $tpString = implode('/', $this->getLiteralPathArray(false, false));
        $rpString = implode('/', $request->getLiteralPathArray(false, false));

        if (!$full && $tpString == $rpString) {
            return true;
        }

        if (substr($rpString, -1) == '/') {
            if (($rpString == '~front/' && $rpString != $tpString)
            || 0 !== stripos($tpString, $rpString)
                //|| dirname($tpString.'-').'/' != $rpString
            ) {
                return false;
            }
        } elseif (0 !== stripos($tpString, $rpString)) {
            return false;
        }

        if ($full && $tpString == $rpString && $this->_query && $request->_query) {
            foreach ($request->_query as $key => $node) {
                if (!isset($this->_query->{$key}) || $this->_query[$key] != $node->getValue()) {
                    return false;
                }
            }
        }

        return true;
    }



    public function contains($request): bool
    {
        return $this->_contains($request, true);
    }

    public function containsPath($request): bool
    {
        return $this->_contains($request, false);
    }

    protected function _contains($request, $full): bool
    {
        $request = Request::factory($request);

        if ($this->_scheme != $request->_scheme) {
            return false;
        }

        $tpString = $this->getLiteralPathString();
        $rpString = $request->getLiteralPathString();
        $rpDirString = dirname($rpString) . '/';

        if (0 !== stripos($tpString, $rpDirString)) {
            return false;
        }

        if ($full && $rpString == $tpString && $this->_query) {
            foreach ($this->_query as $key => $value) {
                if (!$request->_query) {
                    return false;
                }

                if (!isset($request->_query->{$key}) || $request->_query[$key] != $value) {
                    return false;
                }
            }
        }

        return true;
    }



    public function isWithin($request): bool
    {
        return Request::factory($request)->contains($this);
    }

    public function isPathWithin($request): bool
    {
        return Request::factory($request)->containsPath($this);
    }




    // Literal path
    public function getLiteralPath()
    {
        return new core\uri\Path($this->getLiteralPathArray(), false);
    }

    public function getLiteralPathArray(bool $incType = true, bool $incNode = true)
    {
        if ($this->_path) {
            $parts = $this->_path->getRawCollection();

            if (empty($parts)) {
                $addTrailingSlash = true;
            } else {
                if (!$addTrailingSlash = $this->_path->shouldAddTrailingSlash()) {
                    $name = $this->_path->getFileName();
                    $ext = $this->_path->getExtension();
                    array_pop($parts);

                    if ($ext !== null && ($incType || $ext != static::DEFAULT_TYPE)) {
                        if (empty($name)) {
                            $name = self::DEFAULT_NODE;
                        }

                        $name .= '.' . $ext;
                    }

                    if ($incNode || $name != static::DEFAULT_NODE) {
                        $parts[] = $name;
                    } else {
                        $parts[] = '';
                    }
                }
            }
        } else {
            $parts = [];
            $addTrailingSlash = true;
        }

        if (!isset($parts[0]) || substr($parts[0], 0, 1) != '~') {
            array_unshift($parts, static::AREA_MARKER . static::DEFAULT_AREA);
        }

        if ($addTrailingSlash) {
            if ($incNode) {
                $name = static::DEFAULT_NODE;

                if ($incType) {
                    $name .= '.' . static::DEFAULT_TYPE;
                }

                $parts[] = $name;
            } else {
                $parts[] = '';
            }
        }

        array_map('strtolower', $parts);
        return $parts;
    }

    public function getLiteralPathString()
    {
        return implode('/', $this->getLiteralPathArray());
    }

    public function toString(): string
    {
        $output = 'directory://' . $this->_path;

        if ($this->_query && !$this->_query->isEmpty()) {
            $output .= '?' . $this->_query->toArrayDelimitedString();
        }

        if ($this->_fragment) {
            $output .= '#' . $this->_fragment;
        }

        return $output;
    }

    public function toReadableString()
    {
        $output = implode('/', $this->getLiteralPathArray());

        if ($this->_query && !$this->_query->isEmpty()) {
            $query = clone $this->_query;
            unset($query->{self::REDIRECT_FROM}, $query->{self::REDIRECT_TO});

            if (!$query->isEmpty()) {
                $output .= '?' . $this->_query->toArrayDelimitedString();
            }
        }

        if ($this->_fragment) {
            $output .= '#' . $this->_fragment;
        }

        return urldecode($output);
    }

    public function getDirectoryLocation()
    {
        $output = $this->getArea();

        if ($controller = $this->getController()) {
            $output .= '/' . $controller;
        }

        return $output;
    }

    public function getLibraryPath()
    {
        $output = 'apex/directory/' . $this->getArea() . '/';

        if ($controller = $this->getController()) {
            $output .= $controller;
        }

        return $output;
    }


    public function convertToHttpUrl($scheme, $domain, $port, array $basePath)
    {
        if ($this->isJustFragment()) {
            return new link\http\Url('#' . $this->_fragment);
        }

        $path = null;

        if ($this->_path) {
            $path = clone $this->_path;

            if ($path->get(0) == '~' . static::DEFAULT_AREA) {
                $path->shift();
            }

            if (!empty($basePath)) {
                $path->unshift($basePath);
            }
        } elseif (!empty($basePath)) {
            $path = new core\uri\Path($basePath);
            $path->shouldAddTrailingSlash(true);
        }

        $output = new link\http\Url();
        $output->_scheme = $scheme;
        $output->_domain = $domain;
        $output->_port = $port;

        if (!empty($path)) {
            $output->_path = $path;
        }

        if (!empty($this->_query)) {
            $output->_query = $this->_query;
        }

        if (!empty($this->_fragment)) {
            $output->_fragment = $this->_fragment;
        }

        return $output;
    }

    public function normalize()
    {
        if (!$this->_path) {
            return $this;
        }

        if (
            substr((string)$this->_path[0], 0, 1) == self::AREA_MARKER &&
            substr((string)$this->_path[0], 1) == self::DEFAULT_AREA
        ) {
            $this->_path->remove(0);
        }

        if (!$this->_path->shouldAddTrailingSlash()) {
            $isDefaultExtension = strtolower((string)$this->_path->getExtension()) == self::DEFAULT_TYPE;

            if ($this->_path->getFileName() == self::DEFAULT_NODE && $isDefaultExtension) {
                $this->_path->pop();
                $this->_path->shouldAddTrailingSlash(true);
            } elseif ($isDefaultExtension) {
                $this->_path->setExtension(null);
            }
        }

        return $this;
    }


    // Redirect
    public function encode()
    {
        return base64_encode(substr($this->toString(), 12));
    }

    public static function decode($str)
    {
        return new self(base64_decode($str));
    }


    public function setRedirect($from, $to = null)
    {
        $this->setRedirectFrom($from);
        $this->setRedirectTo($to);

        return $this;
    }

    public function hasRedirectFrom()
    {
        if (!$this->_query) {
            return false;
        }

        return $this->_query->__isset(self::REDIRECT_FROM);
    }

    public function hasRedirectTo()
    {
        if (!$this->_query) {
            return false;
        }

        return $this->_query->__isset(self::REDIRECT_TO);
    }

    public function setRedirectFrom($from)
    {
        if ($from === null) {
            if ($this->_query) {
                $this->_query->remove(self::REDIRECT_FROM);
            }

            return $this;
        }

        $from = Request::factory($from);
        $this->getQuery()->{self::REDIRECT_FROM} = $from->normalize()->encode();

        return $this;
    }

    public function getRedirectFrom()
    {
        if (!$this->hasRedirectFrom()) {
            return null;
        }

        return self::decode($this->_query[self::REDIRECT_FROM]);
    }

    public function setRedirectTo($to)
    {
        if ($to === null) {
            if ($this->_query) {
                $this->_query->remove(self::REDIRECT_TO);
            }

            return $this;
        }

        $to = Request::factory($to);
        $this->getQuery()->{self::REDIRECT_TO} = $to->normalize()->encode();

        return $this;
    }

    public function getRedirectTo()
    {
        if (!$this->hasRedirectTo()) {
            return null;
        }

        return self::decode($this->_query[self::REDIRECT_TO]);
    }


    // Parent
    public function getParent()
    {
        $output = clone $this;
        $output->_query = null;
        $output->_fragment = null;

        if ($output->_path) {
            $isDefaultNode = $output->isDefaultNode();

            if (!$output->_path->shouldAddTrailingSlash()) {
                $output->_path->pop();
                $output->_path->shouldAddTrailingSlash(true);
            }

            if ($isDefaultNode) {
                $output->_path->pop();
            }
        }

        return $output;
    }

    public function extractRelative($path)
    {
        $output = new self($path);
        $output->_path = $this->getPath()->extractRelative($output->getPath());
        return $output;
    }


    // Rewrite
    public function rewriteQueryToPath(...$keys)
    {
        $optional = [];
        $keys = $this->_normalizeKeys($keys, $optional);
        $path = $this->getPath();
        $query = $this->getQuery();

        foreach ($keys as $key) {
            if (null === ($value = $query->get($key))) {
                if (!in_array($key, $optional)) {
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

    public function rewritePathToQuery($rootCount, ...$keys)
    {
        $optional = [];
        $keys = $this->_normalizeKeys($keys, $optional);
        $path = $this->getPath();
        $query = $this->getQuery();
        $parts = $path->slice((int)$rootCount);

        foreach ($keys as $key) {
            if (empty($parts) && in_array($key, $optional)) {
                break;
            }

            $query->{$key} = array_shift($parts);
        }

        return $this;
    }

    protected function _normalizeKeys(array $keys, array &$optional)
    {
        $output = [];
        $optional = [];

        foreach ($keys as $i => $key) {
            if (is_array($key)) {
                foreach ($key as $innerKey) {
                    $output[] = $innerKey;
                }
            } else {
                $output[] = $key;
            }
        }

        $output = array_unique($output);

        foreach ($output as $i => $key) {
            if (substr($key, -1) == '?') {
                $optional[] = $output[$i] = $key = substr($key, 0, -1);
            } elseif (!empty($optional)) {
                $optional[] = $key;
            }
        }

        return $output;
    }


    // Access
    public function getAccessLockDomain()
    {
        return 'directory';
    }

    public function lookupAccessKey(array $keys, $lockAction = null)
    {
        $parts = $this->getLiteralPathArray(false, true);
        $node = array_pop($parts);
        $basePath = implode('/', $parts);

        if (isset($keys[$basePath . '/' . $node])) {
            return $keys[$basePath . '/' . $node];
        } elseif (isset($keys[$basePath . '/%'])) {
            return $keys[$basePath . '/%'];
        }

        do {
            $current = implode('/', $parts) . '/*';

            if (isset($keys[$current])) {
                return $keys[$current];
            }

            array_pop($parts);
        } while (!empty($parts));

        return null;
    }

    public function setDefaultAccess($access)
    {
        $this->_defaultAccess = $access;
        return $this;
    }

    public function getDefaultAccess($lockAction = null)
    {
        if ($this->_defaultAccess !== null) {
            return $this->_defaultAccess;
        }

        try {
            $context = arch\Context::factory($this);
            $node = arch\node\Base::factory($context);
            return $node->getDefaultAccess($lockAction);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function setAccessSignifiers(string ...$signifiers)
    {
        if (empty($signifiers)) {
            $signifiers = null;
        }

        $this->_accessSignifiers = $signifiers;
        return $this;
    }

    public function getAccessSignifiers(): array
    {
        if ($this->_accessSignifiers !== null) {
            return $this->_accessSignifiers;
        }

        try {
            $context = arch\Context::factory($this);
            $node = arch\node\Base::factory($context);
            return $node->getAccessSignifiers();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getAccessLockId()
    {
        return implode('/', $this->getLiteralPathArray(false, true));
    }

    /*
    protected function _getAccessLockParts() {
        if($this->_path) {
            $parts = $this->_path->getRawCollection();

            if(empty($parts)) {
                $addTrailingSlash = true;
            } else {
                if((!$addTrailingSlash = $this->_path->shouldAddTrailingSlash()) && $this->_path->hasExtension() && $this->_path->getExtension() == static::DEFAULT_TYPE) {
                    array_pop($parts);
                    $parts[] = $this->_path->getFilename();
                }
            }
        } else {
            $parts = [];
            $addTrailingSlash = true;
        }

        if(!isset($parts[0]) || substr($parts[0], 0, 1) != '~') {
            array_unshift($parts, static::AREA_MARKER.static::DEFAULT_AREA);
        }

        if($addTrailingSlash) {
            $parts[] = static::DEFAULT_NODE;
        }

        array_map('strtolower', $parts);
        return $parts;
    }
     */


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'text' => $this->toString();
        yield 'classMembers' => [];
        yield 'section:properties' => false;
    }
}
