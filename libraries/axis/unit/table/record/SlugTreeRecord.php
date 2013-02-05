<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\table\record;

use df;
use df\core;
use df\axis;
use df\opal;
    
class SlugTreeRecord extends opal\query\record\Base {

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
                'parent' => $adapter->fetchParentFor($slug)
            ])
            ->where('id', '=', $this['id'])
            ->execute();

        $transaction->update([
                'parent' => $adapter->fetchParentFor($this['slug'].'/x')
            ])
            ->where('parent', '=', $this)
            ->execute();

        $this->forceSet('slug', $slug);
        $transaction->commit();

        return $this;
    }
}