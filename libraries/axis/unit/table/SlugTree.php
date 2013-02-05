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

    public function fetchParentFor($slug, $context=null, $shared=true) {
        if($slug instanceof axis\unit\table\record\SlugTreeRecord) {
            $slug = $slug['slug'];
        }

        if($context === null) {
            $context = 'shared';
        }

        $parts = explode('/', trim($slug, '/'));
        array_pop($parts);

        if(empty($parts)) {
            return null;
        }

        $slugs = array();

        do {
            $slugs[] = implode('/', $parts);
            array_pop($parts);
        } while(!empty($parts));

        $query = $this->fetch()
            ->where('slug', 'in', $slugs);

        $clause = $query->beginWhereClause()
                ->where('context', '=', $context);

        if($shared) {
            $clause->orWhere('isShared', '=', true);
        }
        
        return $clause->endClause()
            ->orderBy('slug DESC')
            ->toRow();
    }
}