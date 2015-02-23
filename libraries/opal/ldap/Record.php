<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\ldap;

use df;
use df\core;
use df\opal;

class Record extends opal\record\Base implements IRecord {
    
    use core\collection\TAttributeContainer;

    public function getPrimaryKeySet() {
        return new opal\record\PrimaryKeySet([
            $this->_getEntryDnAttribute() => $this->getEntryDn()
        ]);
    }

    public function getOriginalPrimaryKeySet() {
        return $this->getPrimaryKeySet();
    }

    public function populateWithPreparedData(array $row) {
        if(isset($row[':meta'])) {
            $this->_attributes = $row[':meta'];
            unset($row[':meta']);
        }

        return parent::populateWithPreparedData($row);
    }

    public function makeNew(array $newValues=null) {
        $dn = $this->getEntryDn();
        $this->_attributes = [];
        $this->inside($dn);
        return parent::makeNew($newValues);
    }

    public function getQueryLocation() {
        return $this->getEntryDn();
    }

    public function inside($location) {
        $dn = Dn::factory($location);
        $this->setAttribute($this->_getEntryDnAttribute(), (string)$dn);
        return $this;
    }

    public function getEntryDn() {
        $output = $this->getAttribute($this->_getEntryDnAttribute());

        if(!$output) {
            throw new UnexpectedValueException(
                'Entry DN has not been stored in record'
            );
        }

        return Dn::factory($output);
    }

    public function getGlobalId() {
        $output = $this->getAttribute($this->_getGlobalIdAttribute());

        if(!$output) {
            throw new UnexpectedValueException(
                'Entry global id has not been stored in record'
            );
        }

        return $output;
    }

    public function getObjectClasses() {
        $output = $this->getAttribute('objectClass');

        if(!$output) {
            throw new UnexpectedValueException(
                'Entry objectClass data has not been stored in record'
            );
        }

        return $output;
    }

    protected function _getEntryDnAttribute() {
        $adapter = $this->getRecordAdapter();
        return $adapter::ENTRY_DN_ATTRIBUTE;
    }

    protected function _getGlobalIdAttribute() {
        $adapter = $this->getRecordAdapter();
        return $adapter::GLOBAL_ID_ATTRIBUTE;
    }


    public function getDumpProperties() {
        $output = parent::getDumpProperties();
        array_unshift($output, new core\debug\dumper\Property('meta', $this->_attributes, 'private'));
        return $output;
    }
}