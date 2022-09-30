<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\models\mail\journal;

use df\core;
use df\axis;
use df\flow;

use DecodeLabs\Genesis;

class Unit extends axis\unit\Table
{
    public const BROADCAST_HOOK_EVENTS = false;

    public const SEARCH_FIELDS = [
        'name' => 3,
        'email' => 1
    ];

    protected function createSchema($schema)
    {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addField('date', 'Timestamp');

        $schema->addField('name', 'Text', 128);
        $schema->addField('key1', 'Text', 64)
            ->isNullable(true);
        $schema->addField('key2', 'Text', 64)
            ->isNullable(true);

        $schema->addField('email', 'Text', 255);
        $schema->addField('user', 'One', 'user/client')
            ->isNullable(true);

        $schema->addField('expireDate', 'Date:Time')
            ->isNullable(true);
        $schema->addField('environmentMode', 'Enum', 'core/environment/Mode')
            ->setDefaultValue('production');
    }

    public function store(flow\mail\IMessage $message)
    {
        if (!$message->shouldJournal()) {
            return;
        }

        $expire = new core\time\Date();
        $expire->add($message->getJournalDuration());

        $baseData = [
            'name' => $message->getJournalName(),
            'key1' => $message->getJournalKey1(),
            'key2' => $message->getJournalKey2(),
            'expireDate' => $expire,
            'environmentMode' => Genesis::$environment->getMode()
        ];

        $emails = [];

        foreach ($message->getToAddresses() as $address) {
            $emails[$address->getAddress()] = null;
        }

        $idList = $this->context->data->user->client->select('id', 'email')
            ->where('email', 'in', array_keys($emails))
            ->toArray();

        foreach ($idList as $row) {
            $emails[$row['email']] = $row['id'];
        }

        $queue = $this->context->data->newJobQueue();

        foreach ($emails as $address => $id) {
            $journal = $this->newRecord($baseData);
            $journal->email = $address;

            if ($id) {
                $journal->user = $id;
            }

            $journal->save($queue);
        }

        $queue->execute();
        return;
    }
}
