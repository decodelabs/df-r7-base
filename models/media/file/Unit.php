<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\media\file;

use df;
use df\core;
use df\apex;
use df\axis;
use df\opal;

class Unit extends axis\unit\table\Base {
    
    const NAME_FIELD = 'fileName';

    protected $_defaultSearchFields = [
        'slug' => 5,
        'fileName' => 4,
        'id' => 10
    ];

    protected $_defaultOrderableFields = [
        'slug', 'creationDate', 'fileName'
    ];

    protected $_defaultOrder = 'slug ASC';

    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Guid');

        $schema->addField('slug', 'Slug')
            ->allowPathFormat(true);

        $schema->addField('bucket', 'ManyToOne', 'bucket', 'files');

        $schema->addUniqueIndex('slug', ['slug', 'bucket']);

        $schema->addField('creationDate', 'Timestamp');
        $schema->addField('owner', 'One', 'user/client')
            ->isNullable(true);

        $schema->addField('fileName', 'String', 1024);

        $schema->addField('activeVersion', 'One', 'version');
        $schema->addField('versions', 'OneToMany', 'version', 'file');
    }

    public function ensureSlugUnique($slug, $bucketId=null, $fileId=null) {
        $output = $slug;
        $counter = 0;

        while($this->slugExists($output, $bucketId, $fileId)) {
            $output = $this->context->format->slug($slug.'-'.(++$counter));
        }

        return $output;
    }

    public function slugExists($slug, $bucketId=null, $fileId=null) {
        return (bool)$this->select('slug')
            ->where('slug', '=', $slug)
            ->chainIf($bucketId !== null, function($query) use($bucketId) {
                $query->where('bucket', '=', $bucketId);
            })
            ->chainIf($fileId !== null, function($query) use($fileId) {
                $query->where('id', '!=', $fileId);
            })
            ->count();
    }

    public function fetchBySlug($slug) {
        return $this->fetch()
            ->where('slug', '=', $slug)
            ->toRow();
    }

    public function selectActive() {
        return $this->select(func_get_args())
            ->leftJoin(
                    'id as versionId', 'fileName', 'fileSize', 'number as versionNumber', 'contentType'
                )
                ->from('axis://media/Version', 'version')
                ->on('version.@primary', '=', 'file.activeVersion')
                ->endJoin();
    }


// Query blocks
    public function applyActiveVersionQueryBlock(opal\query\IReadQuery $query) {
        if($query instanceof opal\query\ISelectQuery) {
            $query->joinRelation('activeVersion', 'id as versionId', 'number as version', 'fileSize', 'contentType', 'notes');
        } else {
            $query->attachRelation('activeVersion', 'id as versionId', 'number as version', 'fileSize', 'fileName', 'contentType', 'notes');
        }
    }



    public function applyListDetailsRelationQueryBlock(opal\query\IReadQuery $query, opal\query\IField $relationField, $combine=true) {
        $rName = $relationField->getName();

        if($query instanceof opal\query\ISelectQuery) {
            if($combine) {
                $query->leftJoinRelation($relationField, 'id as '.$rName.'|id', 'fileName as '.$rName.'|fileName')
                    ->leftJoinRelation($rName.'.activeVersion as '.$rName.'_activeVersion', 'number as '.$rName.'|version')
                    ->countRelation($rName.'.versions', $rName.'|versions')
                    ->combine($rName.'|id as id', $rName.'|fileName as fileName', $rName.'|version as version', $rName.'|versions as versions')
                        ->nullOn('id')
                        ->asOne($relationField);
            } else {
                $query->leftJoinRelation($relationField, 'id as file', 'fileName')
                    ->leftJoinRelation($rName.'.activeVersion as '.$rName.'_activeVersion', 'number as version');
            }
        } else {
            $query->attachRelation($rName, 'id as file')
                ->leftJoinRelation('activeVersion', 'number as version', 'fileName');
        }
    }

    public function applyDetailsRelationQueryBlock(opal\query\IReadQuery $query, opal\query\IField $relationField) {
        $rName = $relationField->getName();

        $query->selectAttachRelation($relationField)
            ->countRelation('versions')
            ->joinRelation('activeVersion as '.$rName.'_activeVersion', 'id as versionId', 'number as version', 'fileSize', 'contentType', 'notes')
            ->asOne($relationField);
    }

    public function applyLinkDetailsRelationQueryBlock(opal\query\IReadQuery $query, opal\query\IField $relationField) {
        $rName = $relationField->getName();

        $query->selectAttachRelation($relationField, 'id')
            ->joinRelation('activeVersion as '.$rName.'_activeVersion', 'id as versionId', 'number as version', 'fileName', 'fileSize')
            ->asOne($relationField);
    }
}