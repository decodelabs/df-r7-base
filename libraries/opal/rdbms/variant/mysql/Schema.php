<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\variant\mysql;

use df;
use df\core;
use df\opal;

class Schema extends opal\rdbms\schema\Base implements ISchema, core\IDumpable {
    
    protected $_options = [
        'name' => null,
        'comment' => null,
        'isTemporary' => false,
        
        'engine' => null,
        'autoIncrementPosition' => 0,
        'characterSet' => null,
        'collation' => null,
        'keyBlockSize' => null,
        
        'avgRowLength' => null,
        'checksum' => false,
        'federatedConnection' => null,
        'dataDirectory' => null,
        'indexDirectory' => null,
        'delayKeyWrite' => false,
        'maxRows' => null,
        'minRows' => null,
        'packKeys' => null,
        'rowFormat' => null,
        
        'insertMethod' => null,
        'mergeTables' => null
    ];
    
        
// Engine
    public function setEngine($engine) {
        return $this->setOption('engine', $engine);
    }
    
    public function getEngine() {
        return $this->getOption('engine');
    }
    
// Auto increment
    public function setAutoIncrementPosition($position) {
        return $this->setOption('autoIncrementPosition', $position);
    }
    
    public function getAutoIncrementPosition() {
        return $this->getOption('autoIncrementPosition');
    }
    
// Character set
    public function setCharacterSet($charset) {
        return $this->setOption('characterSet', $charset);
    }
    
    public function getCharacterSet() {
        return $this->getOption('characterSet');
    }
    
// Collation
    public function setCollation($collation) {
        return $this->setOption('collation', $collation);
    }
    
    public function getCollation() {
        return $this->getOption('collation');
    }
    
// Key block size
    public function setKeyBlockSize($size) {
        if($size !== null) {
            $size = (int)$size;
        }
        
        if($size <= 0) {
            $size = null;
        }
        
        return $this->setOption('keyBlockSize', $size);
    }
    
    public function getKeyBlockSize() {
        return $this->getOption('keyBlockSize');
    }
    
    
// Options
    public function setAvgRowLength($length) {
        if($length !== null) {
            $length = (int)$length;
        }
        
        if($length <= 0) {
            $length = null;
        }
        
        return $this->setOption('avgRowLength', $length);
    }
    
    public function getAvgRowLength() {
        return $this->getOption('avgRowLength');
    }
    
    public function shouldGenerateChecksum($flag=null) {
        if($flag !== null) {
            return $this->getOption('checksum', (bool)$flag);
        }
        
        return (bool)$this->getOption('checksum');
    }
    
    public function setFederatedConnection($connection) {
        return $this->setOption('federatedConnection', $connection);
    }
    
    public function getFederatedConnection() {
        return $this->getOption('federatedConnection');
    }
    
    public function setDataDirectory($dir) {
        return $this->setOption('dataDirectory', $dir);
    }
    
    public function getDataDirectory() {
        return $this->getOption('dataDirectory');
    }
    
    public function setIndexDirectory($dir) {
        return $this->setOption('indexDirectory', $dir);
    }
    
    public function getIndexDirectory() {
        return $this->getOption('indexDirectory');
    }
    
    public function shouldDelayKeyWrite($flag=null) {
        if($flag !== null) {
            return $this->setOption('delayKeyWrite', (bool)$flag);
        }
        
        return (bool)$this->getOption('delayKeyWrite');
    }
    
    public function setMaxRowHint($maxRows) {
        if($maxRows !== null) {
            $maxRows = (int)$maxRows;
        }
        
        if($maxRows <= 0) {
            $maxRows = null;
        }
        
        return $this->setOption('maxRows', $maxRows);
    }
    
    public function getMaxRowHint() {
        return $this->getOption('maxRows');
    }
    
    public function setMinRowHint($minRows) {
        if($minRows !== null) {
            $minRows = (int)$minRows;
        }
        
        if($minRows <= 0) {
            $minRows = null;
        }
        
        return $this->setOption('minRows', $minRows);
    }
    
    public function getMinRowHint() {
        return $this->getOption('minRows');
    }
    
    public function shouldPackKeys($flag=null) {
        if($flag !== null) {
            if(strtolower($flag) == 'default') {
                $flag = null;
            } else {
                $flag = (bool)$flag;
            }
            
            return $this->setOption('packKeys', $flag);
        }
        
        return $this->getOption('packKeys');
    }
    
    public function setRowFormat($format) {
        return $this->setOption('rowFormat', $format);
    }
    
    public function getRowFormat() {
        return $this->getOption('rowFormat');
    }
    
    
// Merge options
    public function setMergeInsertMethod($insert) {
        switch($insert = strtoupper($insert)) {
            case 'NO':
            case 'FIRST':
            case 'LAST':
                break;
                
            default:
                $insert = null;
                break;
        }
        
        return $this->setOption('insertMethod', $insert);
    }
    
    public function getMergeInsertMethod() {
        return $this->getOption('insertMethod');
    }
    
    public function setMergeTables(array $tables) {
        return $this->setOption('mergeTables', $tables);
    }
    
    public function getMergeTables() {
        return $this->getOption('mergeTables');
    }
    
    
    
// Constraints
    public function _createTrigger($name, $event, $timing, $statement) {
        return new Trigger($this, $name, $event, $timing, $statement);
    }
    
    
// Normalize
    public function normalize() {
        parent::normalize();
        
        
        // Engine
        $res = $this->_adapter->prepare('SHOW ENGINES')->executeRead();
        $availableEngines = [];
        $defaultEngine = null;
        
        foreach($res as $row) {
            if($row['Support'] == 'DEFAULT') {
                $defaultEngine = $row['Engine'];
            }
            
            $availableEngines[strtolower($row['Engine'])] = $row;
        }
        
        
        if($this->_options['engine'] !== null) {
            $compEngine = strtolower($this->_options['engine']);
            
            if(isset($availableEngines[$compEngine])) {
                $this->_options['engine'] = $availableEngines[$compEngine]['Engine'];
            } else {
                switch($compEngine) {
                    case 'archive':
                    case 'bdb':
                    case 'csv':
                    case 'example':
                    case 'federated':
                    case 'heap':
                    case 'isam':
                    case 'memory':
                    case 'merge':
                    case 'ndbcluster':
                        $this->_options['engine'] = strtoupper($engine);
                        break;
                        
                    case 'innodb':
                        $this->_options['engine'] = 'InnoDB';
                        break;
                        
                    case 'myisam':
                        $this->_options['engine'] = 'MyISAM';
                        break;
                }
                
                $compEngine = strtolower($this->_options['engine']);
            }
            
            
            if(!isset($availableEngines[$compEngine])
            || $availableEngines[$compEngine]['Engine'] != $this->_options['engine']) {
                throw new opal\rdbms\EngineSupportException(
                    'Mysql storage engine '.$this->_options['engine'].' does not appear to be available'
                );
            }
        } else {
            $this->_options['engine'] = $defaultEngine;
            $compEngine = strtolower($this->_options['engine']);
        }
        
        
        if($this->_options['engine'] !== 'MERGE') {
            if($this->_options['insertMethod']) {
                throw new opal\rdbms\FeatureSupportException(
                    'Mysql engine '.$this->_options['engine'].' does not support INSERT_METHOD table option'
                );
            }
            
            if(!empty($this->_options['mergeTables'])) {
                throw new opal\rdbms\FeatureSupportException(
                    'Mysql engine '.$this->_options['engine'].' does not support UNION table option'
                );
            }
        }
        
        
        
        
        // Primary key
        if($index = $this->getIndex('PRIMARY')) {
            if($this->_primaryIndex && $index !== $this->_primaryIndex) {
                throw new opal\rdbms\IndexConflictException(
                    'A primary index has been set, but another index has been defined with the name PRIMARY.'."\n".
                    'Mysql requires the primary index to be named PRIMARY'
                );
            }
            
            $this->setPrimaryIndex($index);
        }
        
        if($this->_primaryIndex) {
            if($this->_primaryIndex->getName() != 'PRIMARY') {
                throw new opal\rdbms\IndexConflictException(
                    'Mysql primary index must be named PRIMARY'
                );
            }
        }

        
        // Foreign keys
        if(!empty($this->_foreignKeys)) {
            switch($compEngine) {
                case 'archive':
                case 'bdb':
                case 'csv':
                case 'example':
                case 'federated':
                case 'heap':
                case 'isam':
                case 'memory':
                case 'merge':
                case 'ndbcluster':
                case 'myisam':
                    throw new opal\rdbms\ForeignKeySupportException(
                        'Foreign keys are not supported by Mysql for this storage engine: '.$this->_options['engine']
                    );
            }
        }
        
        
        // Triggers
        if(!empty($this->_triggers)) {
            if(!$this->_adapter->supports(opal\rdbms\adapter\Base::TRIGGERS)) {
                throw new opal\rdbms\TriggerSupportException(
                    'This version of Mysql does not support triggers'
                );
            }
        }
        
        
        // Row format
        if($this->_options['rowFormat'] != null) {
            switch($compEngine) {
                case 'innodb':
                    switch($this->_options['rowFormat'] = strtoupper($this->_options['rowFormat'])) {
                        case 'REDUNDANT':
                        case 'COMPACT':
                        case 'DEFAULT':
                            break;
                            
                        
                        default:
                            $this->_options['rowFormat'] = 'DEFAULT';
                            break;
                    }
                    
                    break;
                    
                case 'myisam':
                    switch($this->_options['rowFormat'] = strtoupper($this->_options['rowFormat'])) {
                        case 'FIXED':
                        case 'DYNAMIC':
                        case 'DEFAULT':
                            break;
                            
                        
                        default:
                            $this->_options['rowFormat'] = 'DEFAULT';
                            break;
                    }
                    
                    break;
                    
                default:
                    $this->_options['rowFormat'] = strtoupper($this->_options['rowFormat']);
                    break;
            }
        }
        
        
        return $this;
    }
}
