<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\form;

use df;
use df\core;
use df\arch;
use df\aura;
use df\axis;
use df\mesh;

trait TMediaBucketAwareSelector {

    protected $_bucket = 'shared';
    protected $_bucketData;
    protected $_bucketHandler;

    public function setBucket($bucket, array $values=null) {
        $this->_bucket = $bucket;
        $this->_bucketData = $values;

        return $this;
    }

    public function getBucket() {
        return $this->_bucket;
    }

    public function getBucketData() {
        return $this->_bucketData;
    }


    protected function _setupBucket() {
        if(isset($this->_bucketData['context1']) 
        && $this->_bucketData['context1'] instanceof arch\form\ISelectorDelegate) {
            if(!$this->_bucketData['context1']->hasSelection()) {
                $this->_bucket = null;
                $this->addDependency(
                    $this->_bucketData['context1'],
                    $this->_('Please make a selection')
                );
            } else {
                $id = $this->_bucketData['context1']->getSelected();
                $locator = clone $this->_bucketData['context1']->getSourceEntityLocator();
                $locator->setId($id);
                $this->_bucketData['context1'] = $locator;
            }
        }

        if(isset($this->_bucketData['context2']) 
        && $this->_bucketData['context2'] instanceof arch\form\ISelectorDelegate) {
            if(!$this->_bucketData['context2']->hasSelection()) {
                $this->_bucket = null;
                $this->addDependency(
                    $this->_bucketData['context1'],
                    $this->_('Please make a selection')
                );
            } else {
                $id = $this->_bucketData['context2']->getSelected();
                $locator = clone $this->_bucketData['context2']->getSourceEntityLocator();
                $locator->setId($id);
                $this->_bucketData['context2'] = $locator;
            }
        }

        if($this->_bucket) {
            if(!$this->_bucket instanceof opal\record\IRecord) {
                $this->_bucket = $this->data->media->bucket->ensureSlugExists(
                    $this->_bucket, $this->_bucketData
                );
            }
            
            $this->_bucketHandler = $this->_bucket->getHandler();
        }
    }
}