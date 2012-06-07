<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\search;

use df;
use df\core;
use df\opal;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface IClient {
    public static function factory($settings);
    public function getIndex($name);
    public function getIndexList();
}

interface IIndex extends \Countable {
    public function getName();
    public function getClient();
    
    public function newDocument($id=null, array $values=null);
    public function storeDocument(IDocument $document);
    public function deleteDocument($id);
    public function hasDocument($id);
}

interface IDocument {
    public function setId($id);
    public function getId();
    public function setValues(array $values);
    public function setValue($key, $value, $boost=null);
    public function getValue($key);
    public function setBoost($key, $boost);
    public function getBoost($key);
}

interface IResult extends opal\query\record\IRecord {
    
}
