<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flex\code\probe;

use df;
use df\core;
use df\flex;
use df\halo;

use DecodeLabs\Exceptional;

class Counter implements flex\code\IProbe, core\lang\IAcceptTypeProcessor, \ArrayAccess
{
    use flex\code\TProbe;
    use core\lang\TAcceptTypeProcessor;

    public const TEXT_TYPES = [
        'as', 'atom', 'cgi', 'css', 'cs', 'dtd', 'htaccess', 'htc', 'htm', 'html', 'js', 'json',
        'less', 'mathml', 'php', 'rdf', 'sass', 'scss', 'smd', 'sh', 'style', 'svg', 'sql',
        'template', 'txt', 'xhtml', 'xht', 'xsd', 'xsl', 'xslt', 'xml', 'yml'
    ];

    public const BLACKLIST = ['gitignore', 'loc'];

    protected $_types = [];
    protected $_totals;

    public function __construct(array $acceptTypes=null)
    {
        if ($acceptTypes !== null) {
            $this->setAcceptTypes(...$acceptTypes);
        }
    }

    public function __clone()
    {
        foreach ($this->_types as $ext => $type) {
            $this->_types[$ext] = clone $type;
        }
    }

    public function probe(flex\code\Location $location, $localPath)
    {
        if (substr($localPath, -1) == '~') {
            return;
        }

        $path = core\uri\Path::factory($location->path.'/'.$localPath);
        $basename = $path->getBasename();
        $ext = $path->getExtension();

        if ($ext === null
        || in_array($ext, self::BLACKLIST)
        || (!empty($this->_acceptTypes) && !$this->isTypeAccepted($ext))) {
            return;
        }

        $lines = 0;

        if (in_array($ext, self::TEXT_TYPES)) {
            if (false === ($fp = fopen($path, 'r'))) {
                throw Exceptional::Runtime(
                    'Unable to open probe target', null, $path
                );
            }

            while (fgets($fp)) {
                $lines++;
            }

            fclose($fp);
        }

        $bytes = filesize($path);

        if (!isset($this->_types[$ext])) {
            $this->_types[$ext] = $type = new Counter_Type($ext);
        } else {
            $type = $this->_types[$ext];
        }

        $type->addFile($lines, $bytes);
    }

    public function exportTo(flex\code\IProbe $probe)
    {
        if (!$probe instanceof self) {
            return;
        }

        $probeTypes = $probe->getTypes();

        foreach ($this->_types as $type) {
            if (!isset($probeTypes[$type->extension])) {
                $probeTypes[$type->extension] = $type;
            } else {
                $probeTypes[$type->extension]->import($type);
            }
        }

        $probe->setTypes($probeTypes);
    }

    public function setTypes(array $types)
    {
        $this->_types = $types;
        return $this;
    }

    public function getTypes()
    {
        return $this->_types;
    }

    public function getType($type)
    {
        if (isset($this->_types[$type])) {
            return $this->_types[$type];
        }
    }

    public function hasType($type)
    {
        return isset($this->_types[$type]);
    }

    public function countTypes()
    {
        return count($this->_types);
    }

    public function getTotals()
    {
        if (!$this->_totals) {
            $this->_totals = new Counter_Type('TOTAL');

            foreach ($this->_types as $type) {
                $this->_totals->import($type);
            }
        }

        return $this->_totals;
    }

    public function sortByLines()
    {
        uasort($this->_types, function ($a, $b) {
            return $b->lines <=> $a->lines;
        });

        return $this;
    }

    public function offsetSet(
        mixed $key,
        mixed $value
    ): void {
        throw Exceptional::Logic(
            'Counter probe is read only'
        );
    }

    public function offsetGet(mixed $key): mixed
    {
        return $this->getType($key);
    }

    public function offsetExists(mixed $key): bool
    {
        return $this->hasType($key);
    }

    public function offsetUnset(mixed $key): void
    {
        throw Exceptional::Logic(
            'Counter probe is read only'
        );
    }
}

class Counter_Type
{
    public $extension;
    public $files = 0;
    public $lines = 0;
    public $bytes = 0;

    public function __construct($extension)
    {
        $this->extension = $extension;
    }

    public function getExtension()
    {
        return $this->extension;
    }

    public function addFile($lines, $bytes)
    {
        $this->files++;
        $this->lines += $lines;
        $this->bytes += $bytes;

        return $this;
    }

    public function import(self $type)
    {
        $this->files += $type->files;
        $this->lines += $type->lines;
        $this->bytes += $type->bytes;

        return $this;
    }

    public function countFiles()
    {
        return $this->files;
    }

    public function countLines()
    {
        return $this->lines;
    }

    public function countBytes()
    {
        return $this->bytes;
    }
}
