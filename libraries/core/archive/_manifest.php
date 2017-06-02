<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\archive;

use df;
use df\core;

interface IArchive {
    public static function extract($file, $destDir=null, $flattenRoot=false);
    public static function factory(string $type): IArchive;

    public function getType(): string;

    public function extractFile(string $file, string $destDir=null, bool $flattenRoot=false): string;
    public function decompressFile(string $file, string $destFile=null): string;

    public function compressString(string $string): string;
    public function decompressString(string $string): string;
}
