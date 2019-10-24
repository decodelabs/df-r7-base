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

use DecodeLabs\Glitch;

class Unit extends axis\unit\Table
{
    const ORDERABLE_FIELDS = [
        'user', 'date'
    ];

    const DEFAULT_ORDER = 'date DESC';

    protected function createSchema($schema)
    {
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


    public function createRecordEntry(opal\record\IRecord $record, mesh\job\IQueue $queue, opal\record\IJob $recordJob, $description, $action=null, $userId=null)
    {
        if ($action === null) {
            $action = lcfirst($recordJob->getRecordJobName());
        }

        $history = $this->newRecord([
            'user' => $userId ?? $this->context->user->client->getId(),
            'description' => $description,
            'action' => $action
        ]);

        $queue->after($recordJob, 'history', $this, function () use ($history, $queue, $record) {
            $history->entity = $record;
            $history->save($queue);
        });

        return $this;
    }


    public function fetchFor($entityLocator)
    {
        return $this->fetch()
            ->where('entity', '=', $this->_normalizeItemLocator($entityLocator));
    }


    public function countFor($entityLocator)
    {
        return $this->select()
            ->where('entity', '=', $this->_normalizeItemLocator($entityLocator))
            ->count();
    }

    public function deleteFor($entityLocator)
    {
        $this->delete()
            ->where('entity', '=', $this->_normalizeItemLocator($entityLocator))
            ->execute();

        return $this;
    }

    protected function _normalizeItemLocator($locator)
    {
        if (is_array($locator)) {
            foreach ($locator as $i => $value) {
                $locator[$i] = $this->_normalizeItemLocator($value);
            }

            return $locator;
        }

        $locator = mesh\entity\Locator::factory($locator);

        if (!$locator->getId()) {
            throw Glitch::{'df/mesh/entity/EInvalidArgument'}(
                'Locator does not have an id'
            );
        }

        return $locator;
    }
}
