<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\mesh\entity;

use DecodeLabs\Exceptional;

use DecodeLabs\Glitch\Dumpable;
use df\core;

class Locator implements ILocator, Dumpable
{
    use core\TStringProvider;

    protected $_scheme;
    protected $_nodes = [];

    protected $_string;
    protected $_domainString;

    public static function factory($locator)
    {
        if ($locator instanceof ILocator) {
            return $locator;
        }

        if ($locator instanceof ILocatorProvider) {
            return $locator->getEntityLocator();
        }

        return new self($locator);
    }

    public static function domainFactory($domain, $id = null)
    {
        $output = self::factory($domain);

        if ($id !== null) {
            $output->setId($id);
        }

        return $output;
    }

    public function __construct($locator)
    {
        $this->_clearCache();

        if ($locator instanceof core\uri\IGenericUrl) {
            $this->_scheme = $locator->getScheme();
            $path = $locator->getPath()->toString();
        } else {
            $parts = explode('://', $locator, 2);
            $this->_scheme = array_shift($parts);
            $path = array_shift($parts);
        }

        $this->_nodes = $this->_parseString((string)$path);
    }

    // Format:
    // handler://[path/to/]Entity[:id][/[path/to/]SubEntity[:id]]]
    private function _parseString($path)
    {
        $path = trim((string)$path, '/') . '/';
        $length = strlen($path);
        $mode = 0;
        $part = '';

        $output = [];
        $node = [
            'location' => [],
            'type' => null,
            'id' => null
        ];

        for ($i = 0; $i < $length; $i++) {
            $char = $path[$i];

            switch ($mode) {
                // Location
                case 0:
                    if (!isset($part[0]) && ctype_upper($char)) {
                        $part .= $char;
                        $mode = 1; // Type
                    } elseif ($char == '/') {
                        $node['location'][] = $part;
                        $part = '';
                    } elseif (ctype_alnum($char)) {
                        $part .= $char;
                    } else {
                        throw Exceptional::InvalidArgument(
                            'Unexpected char: ' . $char . ' in locator: ' . $path . ' at char: ' . $i
                        );
                    }

                    break;

                    // Entity type name
                case 1:
                    if ($char == ':') {
                        $node['type'] = ucfirst($part);
                        $part = '';

                        $mode = 2; // Id
                    } elseif ($char == '/') {
                        $node['type'] = ucfirst($part);
                        $part = '';

                        $output[] = $node;
                        $node = [
                            'location' => [],
                            'type' => null,
                            'id' => null
                        ];

                        $mode = 0; // Location
                    } elseif (preg_match('/[a-zA-Z0-9-_]/', $char)) {
                        $part .= $char;
                    } else {
                        throw Exceptional::InvalidArgument(
                            'Unexpected char: ' . $char . ' in locator: ' . $path . ' at char: ' . $i
                        );
                    }

                    break;

                    // Entity id
                case 2:
                    if ($char == '"') {
                        $mode = 3;
                    } elseif ($char == '/') {
                        $mode = 0; // Location
                        $node['id'] = $part;
                        $part = '';

                        $output[] = $node;
                        $node = [
                            'location' => [],
                            'type' => null,
                            'id' => null
                        ];
                    } elseif (ctype_alnum($char) || $char == '-') {
                        $part .= $char;
                    } else {
                        throw Exceptional::InvalidArgument(
                            'Unexpected char: ' . $char . ' in locator: ' . $path . ' at char: ' . $i
                        );
                    }

                    break;

                    // Entity id quote
                case 3:
                    if ($char == '\\') {
                        $mode = 4; // Escape
                    } elseif ($char == '"') {
                        $mode = 5; // End quote
                    } else {
                        $part .= $char;
                    }

                    break;

                    // Entity id escape
                case 4:
                    $part .= $char;
                    $mode = 3; // Quote
                    break;

                    // Entity id end quote
                case 5:
                    if ($char != '/') {
                        throw Exceptional::InvalidArgument(
                            'Unexpected char: ' . $char . ' in locator: ' . $path . ' at char: ' . $i
                        );
                    }

                    $mode = 0; // Location
                    $node['id'] = $part;
                    $part = '';

                    $output[] = $node;
                    $node = [
                        'location' => [],
                        'type' => null,
                        'id' => null
                    ];

                    break;
            }
        }

        if (empty($output)) {
            throw Exceptional::InvalidArgument(
                'No entity type definition detected in: ' . $path
            );
        } elseif ($mode != 0) {
            throw Exceptional::InvalidArgument(
                'Unexpected end of locator: ' . $path
            );
        }

        return $output;
    }

    public function getEntityLocator()
    {
        return $this;
    }

    // Scheme
    public function setScheme($scheme)
    {
        $this->_clearCache();
        $this->_scheme = $scheme;
        return $this;
    }

    public function getScheme()
    {
        return $this->_scheme;
    }

    // Nodes
    public function setNodes(array $nodes)
    {
        $this->_nodes = [];
        return $this->addNodes($nodes);
    }

    public function addNodes(array $nodes)
    {
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                $this->addNodes($this->_parseString((string)$node));
                continue;
            }

            $this->importNode($node);
        }

        return $this;
    }

    public function addNode($location, $type, $id = null)
    {
        return $this->importNode([
            'location' => $location,
            'type' => $type,
            'id' => $id
        ]);
    }

    public function importNode(array $node)
    {
        $this->_nodes[] = $this->_prepareNode($node);
        $this->_clearCache();

        return $this;
    }

    public function setNode($index, $location, $type, $id = null)
    {
        return $this->setNodeArray($index, [
            'location' => $location,
            'type' => $type,
            'id' => $id
        ]);
    }

    public function setNodeArray($index, array $node)
    {
        $index = (int)$index;

        if ($index < 0) {
            $index += count($this->_nodes);

            if ($index < 0) {
                throw Exceptional::InvalidArgument(
                    'Index is out of bounds'
                );
            }
        }

        $this->_nodes[$index] = $this->_prepareNode($node);
        $this->_nodes = array_values($this->_nodes);
        $this->_clearCache();

        return $this;
    }

    public function hasNode($index)
    {
        return null !== $this->_normalizeNodeIndex($index);
    }

    public function getNode($index)
    {
        if (null === ($index = $this->_normalizeNodeIndex($index))) {
            return null;
        }

        return $this->_nodes[$index];
    }

    public function getNodeString($index)
    {
        if (!$node = $this->getNode($index)) {
            return null;
        }

        return $this->_nodeToString($node);
    }

    public function setNodeLocation($index, $location)
    {
        if (null === ($index = $this->_normalizeNodeIndex($index))) {
            return null;
        }

        if (!is_array($location)) {
            if (empty($location)) {
                $location = [];
            } else {
                $location = explode('/', trim((string)$location, '/'));
            }
        }

        $this->_nodes[$index]['location'] = $location;
        $this->_clearCache();

        return $this;
    }

    public function appendNodeLocation($index, $location)
    {
        if (null === ($index = $this->_normalizeNodeIndex($index))) {
            return null;
        }

        if (!is_array($location)) {
            if (empty($location)) {
                return $this;
            } else {
                $location = explode('/', trim((string)$location, '/'));
            }
        }

        foreach ($location as $part) {
            $this->_nodes[$index]['location'][] = $part;
        }

        $this->_clearCache();

        return $this;
    }

    public function getNodeLocation($index)
    {
        if (!$node = $this->getNode($index)) {
            return null;
        }

        return implode('/', $node['location']);
    }

    public function setNodeType($index, $type)
    {
        if (null === ($index = $this->_normalizeNodeIndex($index))) {
            return null;
        }

        $this->_nodes[$index]['type'] = ucfirst($type);
        $this->_clearCache();

        return $this;
    }

    public function getNodeType($index)
    {
        if (!$node = $this->getNode($index)) {
            return null;
        }

        return $node['type'];
    }

    public function setNodeId($index, $id)
    {
        if (null === ($index = $this->_normalizeNodeIndex($index))) {
            return null;
        }

        if (!strlen((string)$id)) {
            $id = null;
        } else {
            $id = (string)$id;
        }

        $this->_nodes[$index]['id'] = $id;
        $this->_clearCache();

        return $this;
    }

    public function getNodeId($index)
    {
        if (!$node = $this->getNode($index)) {
            return null;
        }

        return $node['id'];
    }

    public function removeNode($index)
    {
        if (null === ($index = $this->_normalizeNodeIndex($index))) {
            return null;
        }

        unset($this->_nodes[$index]);
        $this->_nodes = array_values($this->_nodes);
        $this->_clearCache();

        return $this;
    }

    public function getNodes()
    {
        return $this->_nodes;
    }

    public function setFirstNode($location, $type, $id = null)
    {
        return $this->setNode(0, $location, $type, $id);
    }

    public function getFirstNode()
    {
        if (!isset($this->_nodes[0])) {
            return null;
        }

        return $this->_nodes[0];
    }

    public function setFirstNodeLocation($location)
    {
        return $this->setNodeLocation(0, $location);
    }

    public function getFirstNodeLocation()
    {
        if (!isset($this->_nodes[0])) {
            return null;
        }

        return implode('/', $this->_nodes[0]['location']);
    }

    public function setFirstNodeType($type)
    {
        return $this->setNodeType(0, $type);
    }

    public function getFirstNodeType()
    {
        if (!isset($this->_nodes[0])) {
            return null;
        }

        return $this->_nodes[0]['type'];
    }

    public function setFirstNodeId($id)
    {
        return $this->setNodeId(0, $id);
    }

    public function getFirstNodeId()
    {
        if (!isset($this->_nodes[0])) {
            return null;
        }

        return $this->_nodes[0]['id'];
    }

    public function setLastNode($location, $type, $id = null)
    {
        return $this->setNode(-1, $location, $type, $id);
    }

    public function getLastNode()
    {
        $i = count($this->_nodes) - 1;

        if (!isset($this->_nodes[$i])) {
            return null;
        }

        return $this->_nodes[$i];
    }

    public function setLastNodeLocation($location)
    {
        return $this->setNodeLocation(-1, $location);
    }

    public function getLastNodeLocation()
    {
        if (!$node = $this->getLastNode()) {
            return null;
        }

        return implode('/', $node['location']);
    }

    public function setLastNodeType($type)
    {
        return $this->setNodeType(-1, $type);
    }

    public function getLastNodeType()
    {
        if (!$node = $this->getLastNode()) {
            return null;
        }

        return $node['type'];
    }

    public function setLastNodeId($id)
    {
        return $this->setNodeId(-1, $id);
    }

    public function getLastNodeId()
    {
        if (!$node = $this->getLastNode()) {
            return null;
        }

        return $node['id'];
    }

    protected function _normalizeNodeIndex($index)
    {
        $index = (int)$index;

        if ($index < 0) {
            $index += count($this->_nodes);

            if ($index < 0) {
                return null;
            }
        }

        if (!isset($this->_nodes[$index])) {
            return null;
        }

        return $index;
    }

    protected function _prepareNode(array $node)
    {
        // Location
        if (!isset($node['location'])) {
            $node['location'] = null;
        }

        if (!is_array($node['location'])) {
            if (empty($node['location'])) {
                $node['location'] = [];
            } else {
                $node['location'] = explode('/', trim((string)$node['location'], '/'));
            }
        }

        // Type
        if (!isset($node['type'])) {
            throw Exceptional::InvalidArgument(
                'Node has no type definition'
            );
        }

        $node['type'] = ucfirst($node['type']);

        // Id
        if (!isset($node['id'])) {
            $node['id'] = null;
        }

        return $node;
    }

    protected function _nodeToString(array $node)
    {
        $output = $node['location'];
        $type = $node['type'];

        if ($node['id'] !== null) {
            if (strpbrk($node['id'], '" :/\'\\')) {
                $type .= ':"' . addslashes($node['id']) . '"';
            } else {
                $type .= ':' . $node['id'];
            }
        }

        $output[] = $type;
        return implode('/', $output);
    }


    // Strings
    public function getDomain()
    {
        if ($this->_domainString === null) {
            $nodes = $this->_nodes;
            $last = array_pop($nodes);

            foreach ($nodes as $i => $node) {
                $nodes[$i] = $this->_nodeToString($node);
            }

            $last['id'] = null;
            $nodes[] = $this->_nodeToString($last);

            $this->_domainString = $this->_scheme . '://' . implode('/', $nodes);
        }

        return $this->_domainString;
    }

    public function setId(?string $id)
    {
        return $this->setNodeId(-1, $id);
    }

    public function getId(): ?string
    {
        return $this->getLastNodeId();
    }

    public function toString(): string
    {
        if ($this->_string === null) {
            $nodes = [];

            foreach ($this->_nodes as $node) {
                $nodes[] = $this->_nodeToString($node);
            }

            $this->_string = $this->_scheme . '://' . implode('/', $nodes);
        }

        return $this->_string;
    }

    public function toStringUpTo($type)
    {
        if (is_array($type)) {
            if (isset($type['type'])) {
                $type = $type['type'];
            } else {
                $type = null;
            }
        }

        $nodes = [];

        foreach ($this->_nodes as $node) {
            $nodes[] = $this->_nodeToString($node);

            if ($node['type'] == $type) {
                break;
            }
        }

        return $this->_scheme . '://' . implode('/', $nodes);
    }

    protected function _clearCache()
    {
        $this->_string = $this->_domainString = null;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'text' => $this->toString();
    }
}
