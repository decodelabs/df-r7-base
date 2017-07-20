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

class Unit extends axis\unit\Table {

    const NAME_FIELD = 'fileName';

    const SEARCH_FIELDS = [
        'fileName' => 4,
        'id' => 10
    ];

    const ORDERABLE_FIELDS = [
        'creationDate', 'fileName'
    ];

    const DEFAULT_ORDER = 'fileName ASC';

    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Guid');

        $schema->addField('bucket', 'ManyToOne', 'bucket', 'files');

        $schema->addField('creationDate', 'Timestamp');
        $schema->addField('owner', 'One', 'user/client')
            ->isNullable(true);

        $schema->addField('fileName', 'Text', 1024);

        $schema->addField('activeVersion', 'One', 'version');
        $schema->addField('versions', 'OneToMany', 'version', 'file');
    }

    public function selectActive(...$fields) {
        return $this->select(...$fields)
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
                    ->leftJoinRelation($rName.'.activeVersion as '.$rName.'_activeVersion', 'number as '.$rName.'|version', 'fileSize as '.$rName.'|fileSize')
                    ->countRelation($rName.'.versions', $rName.'|versions')
                    ->combine($rName.'|id as id', $rName.'|fileName as fileName', $rName.'|version as version', $rName.'|versions as versions', $rName.'|fileSize as fileSize')
                        ->nullOn('id')
                        ->asOne($relationField);
            } else {
                $query->leftJoinRelation($relationField, 'id as file', 'fileName')
                    ->leftJoinRelation($rName.'.activeVersion as '.$rName.'_activeVersion', 'number as version', 'fileSize');
            }
        } else {
            $query->attachRelation($rName, 'id as file')
                ->leftJoinRelation('activeVersion', 'number as version', 'fileName', 'fileSize');
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
