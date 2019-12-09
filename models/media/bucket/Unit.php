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

class Unit extends axis\unit\Table
{
    const ORDERABLE_FIELDS = [
        'name', 'slug', 'creationDate'
    ];

    const DEFAULT_ORDER = 'name ASC';

    protected function createSchema($schema)
    {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addField('name', 'Text', 128);
        $schema->addField('slug', 'Slug');

        $schema->addField('creationDate', 'Timestamp');
        $schema->addField('files', 'OneToMany', 'file', 'bucket');
    }

    public function ensureSlugExists($slug, array $values=null)
    {
        if (!$values) {
            $value = [];
        }

        $slug = $this->context->format->slug($slug);
        $query = $this->fetch()->where('slug', '=', $slug);

        if (!$output = $query->toRow()) {
            $values['slug'] = $slug;

            if (!isset($values['name'])) {
                $values['name'] = $this->context->format->name($slug);
            }

            $output = $this->newRecord($values)->save();
        } else {
            if (isset($values['name']) && $output['name'] != $values['name']) {
                $output['name'] = $values['name'];
                $output->save();
            }
        }

        return $output;
    }
}
