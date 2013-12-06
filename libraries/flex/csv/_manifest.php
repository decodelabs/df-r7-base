<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\csv;

use df;
use df\core;
use df\flex;


// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface IBuilder extends core\io\IChunkSender {
    public function setGenerator(Callable $generator=null);
    public function getGenerator();

    public function setFields(array $fields);
    public function getFields();
    public function shouldWriteFields($flag=null);

    public function addRow(array $row);
    public function getRows();
}