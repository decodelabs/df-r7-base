<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\procedure;

use df;
use df\core;
use df\axis;
use df\opal;

// Exceptions
interface IException {}
class LogicException extends \LogicException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface IProcedure extends core\IContextAware {
    public function getUnit();
    public function getModel();
    public function getName(): string;
    public function setValues($values);
    public function getValues();
    public function setDataMap(array $map);
    public function getDataMap();
    public function prepare();
    public function execute(...$args);
    public function isValid(): bool;
}

interface IRecordProcedure extends IProcedure {
    public function setRecord(opal\record\IRecord $record=null);
    public function getRecord();
}
