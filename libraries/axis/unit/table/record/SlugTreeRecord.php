<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\table\record;

use df;
use df\core;
use df\axis;
use df\arch;
use df\opal;
    
class SlugTreeRecord extends opal\record\Base {

    protected function _onPreSave() {
        if(!$this->getRawId('parent')) {
            $this->parent = $this->getRecordAdapter()->fetchParentFor($this['slug']);
        }
    }

    protected function _onSave($taskSet) {
        // Set this as parent for any relevant descendants
        $taskSet->addRawQuery(
            'setParents:'.$this['id'],
            $this->getRecordAdapter()->update([
                    'parent' => $this
                ])
                ->beginWhereClause()
                    ->where('slug', 'begins', $this['slug'])
                    ->where('parent', '=', null)
                    ->endClause()
                ->orWhere('slug_location', '=', $this['slug'])
        );
    }

    protected function _onPreDelete($taskSet) {
        // Set alternative parent for descendants
        $adapter = $this->getRecordAdapter();
        $id = $this['id'];
        $slug = $this['slug'];

        $taskSet->addGenericTask(
            $adapter,
            'resetParents:'.$id,
            function($adapter, $transaction) use ($id, $slug) {
                $transaction->update([
                        'parent' => $adapter->fetchParentFor($slug.'/x')
                    ])
                    ->in($adapter)
                    ->where('parent', '=', $id);
            }
        );
    }

    protected function _onPreUpdate($taskSet) {
        if($this->hasChanged('slug')) {
            $adapter = $this->getRecordAdapter();
            $origLocation = $this->_getSlugLocation($this->getOriginal('slug'));
            $newLocation = $this->_getSlugLocation($this->get('slug'));

            // Update sub descendants
            $taskSet->addGenericTask($adapter, 'updateSubDescendantSlugs', function($adapter, $transaction) use ($origLocation, $newLocation) {
                $list = $adapter->selectDistinct('slug_location')
                    ->where('slug_location', 'begins', $origLocation)
                    ->orderBy('slug_location ASC');

                $length = strlen($origLocation);

                foreach($list as $label) {
                    $newSubLocation = $newLocation.substr($label['slug_location'], $length);

                    $transaction->update([
                            'slug_location' => $newSubLocation,
                            'parent' => $adapter->fetchParentFor($newSubLocation.'/x')
                        ])
                        ->in($adapter)
                        ->where('slug_location', '=', $label['slug_location'])
                        ->execute();
                }
            });
        }
    }

    public function getSlugName() {
        return basename($this['slug']);
    }

    public function getSlugLocation() {
        return $this->_getSlugLocation($this['slug']);
    }

    protected function _getSlugLocation($slug) {
        $output = dirname($slug);

        if($output == '.') {
            $output = '';
        }

        return $output;
    }


    public function moveTo($slug) {
        $this->slug = $slug;
        $this->save();

        return $this;
    }

    public function extractTo($slug) {
        $adapter = $this->getRecordAdapter();
        $transaction = $adapter->begin();

        $transaction->update([
                'slug' => $slug,
                'parent' => $parent = $adapter->fetchParentFor($slug)
            ])
            ->where('id', '=', $this['id'])
            ->execute();

        $transaction->update([
                'parent' => $adapter->fetchParentFor($this['slug'].'/x')
            ])
            ->where('parent', '=', $this)
            ->execute();

        $this->forceSet('slug', $slug);
        $this->forceSet('parent', $parent);

        $transaction->commit();

        return $this;
    }

    public function fetchNodeList($context=null) {
        $adapter = $this->getRecordAdapter();
        $slug = $this['slug'];

        if(empty($slug)) {
            // Root
            $query = $adapter->fetch()
                ->where('parent', '=', null);
        } else if($this->isNew()) {
            // Virtual
            $query = $adapter->fetch()
                ->where('slug_location', 'begins', $slug);
        } else {
            // Actual
            $query = $adapter->fetch()
                ->beginWhereClause()
                    ->where('parent', '=', $this['id'])
                    ->orWhere('slug_location', '=', $slug)
                    ->endClause();
        }

        $query->correlate('COUNT(child.id) as hasChildren')
            ->from($adapter, 'child')
            ->on('child.parent', '=', 'label.id')
            ->endCorrelation();

        if($context !== null) {
            $query->beginWhereClause()
                ->where('context', '=', $context)
                ->orWhere('isShared', '=', true)
                ->endClause();
        }

        $output = array();
        $length = strlen($slug);

        foreach($query as $label) {
            $labelLocation = $label->getSlugLocation();

            if($labelLocation != $slug) {
                if($length) {
                    $labelLocation = substr($labelLocation, $length + 1);
                }

                if(false !== ($pos = strpos($labelLocation, '/'))) {
                    $labelLocation = substr($labelLocation, 0, $pos);
                }

                if(isset($output[$labelLocation])) {
                    continue;
                }

                $label = $adapter->createVirtualNode($slug.'/'.$labelLocation);
                $label->forceSet('hasChildren', true);
            } else {
                $label->forceSet('hasChildren', (bool)$label->get('hasChildren'));
            }

            $output[$label->getSlugName()] = $label;
        }

        return $output;
    }

    public function fetchParentPath() {
        $slug = $this['slug'];
        $adapter = $this->getRecordAdapter();

        if(empty($slug)) {
            return array();
        }

        $output = [$adapter->createVirtualNode('')];
        $parts = explode('/', $slug);
        array_pop($parts);

        if(empty($parts)) {
            return $output;
        }

        $slugs = array();

        do {
            $slugs[] = implode('/', $parts);
            array_pop($parts);
        } while(!empty($parts));

        $list = $adapter->fetch()
            ->where('slug', 'in', $slugs)
            ->toKeyArray('slug');

        while($slug = array_pop($slugs)) {
            if(isset($list[$slug])) {
                $output[] = $list[$slug];
            } else {
                $output[] = $adapter->createVirtualNode($slug);
            }
        }

        return $output;
    }
}