<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\archive;

use df;
use df\core;

use DecodeLabs\Glitch;

class Rar extends Base
{
    public function __construct()
    {
        if (!extension_loaded('rar')) {
            throw Glitch::EUnsupported(
                'The rar extension is not loaded'
            );
        }
    }

    public function extractFile(string $file, string $destination=null, bool $flattenRoot=false): string
    {
        $destination = $this->_normalizeExtractDestination($file, $destination);

        // TODO: add password support

        if (false === ($archive = rar_open($file))) {
            throw Glitch::ENotFound(
                'Unable to open rar archive: '.$file
            );
        }

        if (false === ($files = rar_list($archive))) {
            throw Glitch::ERuntime(
                'Unable to read file list from rar archive: '.$file
            );
        }

        foreach ($files as $file) {
            $file->extract($destination);
        }

        rar_close($archive);

        if ($flattenRoot) {
            $this->_flattenRoot($destination);
        }

        return $destination;
    }
}
