<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\media\bucket;

use df;
use df\core;
use df\apex;
use df\axis;

class Unit extends axis\unit\table\Base {
    
    protected $_defaultOrderableFields = [
        'name', 'slug', 'creationDate'
    ];

    protected $_defaultOrder = 'name ASC';

    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addField('name', 'String', 128);
        $schema->addField('slug', 'Slug');

        $schema->addField('creationDate', 'Timestamp');
        $schema->addField('context1', 'EntityLocator')
            ->isNullable(true);
        $schema->addField('context2', 'EntityLocator')
            ->isNullable(true);

        $schema->addField('files', 'OneToMany', 'file', 'bucket');

        $schema->addUniqueIndex('slug', ['slug', 'context1', 'context2']);
    }

    public function ensureSlugExists($slug, array $values=null) {
        if(!$values) {
            $value = [];
        }

        $slug = $this->context->format->slug($slug);
        $query = $this->fetch()->where('slug', '=', $slug);

        if(isset($values['context1'])) {
            $query->where('context1', '=', $values['context1']);
        }

        if(isset($values['context2'])) {
            $query->where('context2', '=', $values['context2']);
        }

        if(!$output = $query->toRow()) {
            $values['slug'] = $slug;

            if(!isset($values['name'])) {
                $values['name'] = $this->context->format->name($slug);
            }

            $output = $this->newRecord($values)->save();
        } else {
            if(isset($values['name']) && $output['name'] != $values['name']) {
                $output['name'] = $values['name'];
                $output->save();
            }
        }

        return $output;
    }
}