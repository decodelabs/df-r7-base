<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\archive;

use df;
use df\core;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class LogicException extends \LogicException implements IException {}


// Interfaces
interface IArchive {
    public static function extract($file, $destination=null, $flattenRoot=false);

    public function getType();
    public function decompressFile($file, $destination=null, $flattenRoot=false);
    
    public function compressString($string);
    public function decompressString($string);
}

