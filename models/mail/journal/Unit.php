<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\mail\journal;

use df;
use df\core;
use df\apex;
use df\axis;
use df\opal;
use df\flow;

class Unit extends axis\unit\table\Base {
    
    protected function _onCreate(axis\schema\ISchema $schema) {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addField('date', 'Timestamp');

        $schema->addField('name', 'String', 128);
        $schema->addField('objectId1', 'String', 64)
            ->isNullable(true);
        $schema->addField('objectId2', 'String', 64)
            ->isNullable(true);

        $schema->addField('email', 'String', 255);
        $schema->addField('user', 'One', 'user/client')
            ->isNullable(true);

        $schema->addField('expireDate', 'DateTime')
            ->isNullable(true);
        $schema->addField('environmentMode', 'Enum', 'core/EnvironmentMode')
            ->setDefaultValue('production');
    }

    public function store(flow\mail\IMessage $message) {
        if(!$message->shouldJournal()) {
            return;
        }

        $expire = new core\time\Date();
        $expire->add($message->getJournalDuration());

        $baseData = [
            'name' => $message->getJournalName(),
            'objectId1' => $message->getJournalObjectId1(),
            'objectId2' => $message->getJournalObjectId2(),
            'expireDate' => $expire,
            'environmentMode' => $this->context->application->getEnvironmentMode()
        ];

        $emails = [];

        foreach($message->getToAddresses() as $address) {
            $emails[$address->getAddress()] = null;
        }

        $idList = $this->context->data->user->client->select('id', 'email')
            ->where('email', 'in', array_keys($emails))
            ->toArray();

        foreach($idList as $row) {
            $emails[$row['email']] = $row['id'];
        }

        $taskSet = $this->context->data->newRecordTaskSet();

        foreach($emails as $address => $id) {
            $journal = $this->newRecord($baseData);
            $journal->email = $address;

            if($id) {
                $journal->user = $id;
            }

            $journal->save($taskSet);
        }

        $taskSet->execute();
        return;
    }
}