<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\table;

use df;
use df\core;
use df\axis;
use df\opal;
    
abstract class SlugTree extends Base {

    protected static $_defaultRecordClass = 'df\\axis\\unit\\table\\record\\SlugTreeRecord';

    public function buildInitialSchema() {
        $schema = new axis\schema\Base($this, $this->getUnitName());

        $schema->addPrimaryField('id', 'AutoId');

        $schema->addUniqueField('slug', 'PathSlug');

        $schema->addField('parent', 'One', $this->getUnitId())
            ->isNullable(true);

        return $schema;
    }

    public function fetchBySlug($slug) {
        return $this->fetch()
            ->where('slug', '=', $slug)
            ->toRow();
    }

    public function fetchNodeBySlug($slug) {
        if(!$node = $this->fetchBySlug($slug)) {
            $node = $this->createVirtualNode($slug);
        }

        return $node;
    }

    public function fetchSlugList() {
        return $this->selectDistinct('slug')
            ->orderBy('slug ASC')
            ->toList('slug');
    }

    abstract public function createVirtualNode($slug);

    public function fetchParentFor($slug) {
        if($slug instanceof axis\unit\table\record\SlugTreeRecord) {
            $slug = $slug['slug'];
        }

        $parts = explode('/', trim($slug, '/'));
        array_pop($parts);

        if(empty($parts)) {
            return null;
        }

        $slugs = [];

        do {
            $slugs[] = implode('/', $parts);
            array_pop($parts);
        } while(!empty($parts));

        $query = $this->fetch()
            ->where('slug', 'in', $slugs)
            ->orderBy('slug DESC');

        return $query->toRow();
    }



    public function normalizeParents() {
        $transaction = $this->begin();
        $list = $transaction->selectDistinct('slug_location')
            ->orderBy('slug_location ASC');

        foreach($list as $label) {
            $transaction->update([
                    'parent' => $this->fetchParentFor($label['slug_location'].'/x')
                ])
                ->where('slug_location', '=', $label['slug_location'])
                ->execute();
        }

        $transaction->commit();
        return $this;
    }
}