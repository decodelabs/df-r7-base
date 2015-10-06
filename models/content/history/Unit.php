<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\content\history;

use df;
use df\core;
use df\apex;
use df\axis;
use df\opal;
use df\mesh;

class Unit extends axis\unit\table\Base {

    protected $_defaultOrderableFields = [
        'user', 'date'
    ];

    protected $_defaultOrder = 'date DESC';

    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addIndexedField('entity', 'EntityLocator');

        $schema->addField('user', 'One', 'user/client')
            ->isNullable(true);

        $schema->addField('action', 'Text', 32)
            ->setDefaultValue('update');
        $schema->addField('description', 'Text', 'large')
            ->isNullable(true);

        $schema->addField('metadata', 'DataObject')
            ->isNullable(true);
        $schema->addField('date', 'Timestamp');
    }


    public function createRecordEntry(opal\record\IRecord $record, opal\record\task\ITaskSet $taskSet, opal\record\task\IRecordTask $recordTask, $description, $action=null, $userId=null) {
        if($action === null) {
            if($recordTask instanceof opal\record\task\IUpdateRecordTask) {
                $action = 'update';
            } else if($recordTask instanceof opal\record\task\IReplaceRecordTask) {
                $action = 'replace';
            } else if($recordTask instanceof opal\record\task\IInsertRecordTask) {
                $action = 'insert';
            } else if($recordTask instanceof opal\record\task\IDeleteRecordTask) {
                $action = 'delete';
            } else {
                $action = 'update';
            }
        }

        $history = $this->newRecord([
            'user' => $userId ? $userId : $this->context->user->client->getId(),
            'description' => $description,
            'action' => $action
        ]);

        $historyTask = $taskSet->addGenericTask('history', $this, function() use($history, $taskSet, $record) {
            $history->entity = $record;
            $history->save($taskSet);
        });

        $historyTask->addDependency($recordTask);
        return $this;
    }


    public function fetchFor($entityLocator) {
        return $this->fetch()
            ->where('entity', '=', $this->_normalizeItemLocator($entityLocator));
    }


    public function countFor($entityLocator) {
        return $this->select()
            ->where('entity', '=', $this->_normalizeItemLocator($entityLocator))
            ->count();
    }

    public function deleteFor($entityLocator) {
        $this->delete()
            ->where('entity', '=', $this->_normalizeItemLocator($entityLocator))
            ->execute();

        return $this;
    }

    protected function _normalizeItemLocator($locator) {
        $locator = mesh\entity\Locator::factory($locator);

        if(!$locator->getId()) {
            throw new mesh\entity\InvalidArgumentException(
                'Locator does not have an id'
            );
        }

        return $locator;
    }
}