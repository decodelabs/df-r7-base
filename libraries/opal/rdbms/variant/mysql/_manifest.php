<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\variant\mysql;

use df;
use df\core;
use df\opal;


// Interfaces
interface ISchema extends
    opal\rdbms\schema\ISchema,
    opal\rdbms\schema\IMultiEngineSchema,
    opal\rdbms\schema\IAutoIncrementableSchema,
    opal\rdbms\schema\ICharacterSetAwareSchema,
    opal\rdbms\schema\ICollationAwareSchema,
    opal\rdbms\schema\IKeyBlockSizeAwareSchema
{
    public function setAvgRowLength($length);
    public function getAvgRowLength();
    public function shouldGenerateChecksum(bool $flag=null);
    public function setFederatedConnection($connection);
    public function getFederatedConnection();
    public function setDataDirectory($dir);
    public function getDataDirectory();
    public function setIndexDirectory($dir);
    public function getIndexDirectory();
    public function shouldDelayKeyWrite(bool $flag=null);
    public function setMaxRowHint($maxRows);
    public function getMaxRowHint();
    public function setMinRowHint($minRows);
    public function getMinRowHint();
    public function shouldPackKeys(bool $flag=null);
    public function setRowFormat($format);
    public function getRowFormat();

    public function setMergeInsertMethod($method);
    public function getMergeInsertMethod();
    public function setMergeTables(array $tables);
    public function getMergeTables();
}
