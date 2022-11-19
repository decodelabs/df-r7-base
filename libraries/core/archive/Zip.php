<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\archive;

use DecodeLabs\Exceptional;

class Zip extends Base
{
    public function extractFile(string $file, string $destination = null, bool $flattenRoot = false): string
    {
        $destination = $this->_normalizeExtractDestination($file, $destination);

        $zip = new \ZipArchive();

        if (($res = $zip->open($file)) !== true) {
            throw Exceptional::NotFound(
                $this->_getErrorString($res)
            );
        }

        if (($res = $zip->extractTo($destination)) !== true) {
            throw Exceptional::Runtime(
                $this->_getErrorString($res)
            );
        }

        $zip->close();

        if ($flattenRoot) {
            $this->_flattenRoot($destination);
        }

        return $destination;
    }

    protected function _getErrorString($error): string
    {
        switch ($error) {
            case \ZipArchive::ER_MULTIDISK:
                return 'Multidisk zip archives are not supported';

            case \ZipArchive::ER_RENAME:
                return 'Failed renaming zip archive';

            case \ZipArchive::ER_CLOSE:
                return 'Failed closing zip archive';

            case \ZipArchive::ER_SEEK:
                return 'Failed seeking the zip archive';

            case \ZipArchive::ER_READ:
                return 'Failed reading the zip archive';

            case \ZipArchive::ER_WRITE:
                return 'Failed writing the zip archive';

            case \ZipArchive::ER_CRC:
                return 'Invalid CRC in zip archive';

            case \ZipArchive::ER_ZIPCLOSED:
                return 'Zip archive is already closed';

            case \ZipArchive::ER_NOENT:
                return 'File could not be found in zip archive';

            case \ZipArchive::ER_EXISTS:
                return 'Zip archive already exists';

            case \ZipArchive::ER_OPEN:
                return 'Can not open zip archive';

            case \ZipArchive::ER_TMPOPEN:
                return 'Failed creating temporary zip archive';

            case \ZipArchive::ER_ZLIB:
                return 'ZLib Problem';

            case \ZipArchive::ER_MEMORY:
                return 'Memory allocation problem while working on a zip archive';

            case \ZipArchive::ER_CHANGED:
                return 'Zip entry has been changed';

            case \ZipArchive::ER_COMPNOTSUPP:
                return 'Compression method not supported within ZLib';

            case \ZipArchive::ER_EOF:
                return 'Premature EOF within zip archive';

            case \ZipArchive::ER_INVAL:
                return 'Invalid argument for ZLib';

            case \ZipArchive::ER_NOZIP:
                return 'Given file is no zip archive';

            case \ZipArchive::ER_INTERNAL:
                return 'Internal error while working on a zip archive';

            case \ZipArchive::ER_INCONS:
                return 'Inconsistent zip archive';

            case \ZipArchive::ER_REMOVE:
                return 'Can not remove zip archive';

            case \ZipArchive::ER_DELETED:
                return 'Zip entry has been deleted';

            default:
                return 'Unknown error within zip archive';
        }
    }
}
