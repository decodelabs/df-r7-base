<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\mailingList\member;

use df;
use df\core;
use df\apex;
use df\axis;

class Unit extends axis\unit\Table
{
    protected function createSchema($schema)
    {
        $schema->addPrimaryField('id', 'Guid');

        $schema->addField('user', 'One', 'user/client');
        $schema->addField('adapter', 'Text', 32);
        $schema->addField('listId', 'Text', 32);

        $schema->addField('data', 'Json');
        $schema->addField('date', 'Timestamp');

        $schema->addUniqueIndex('key', ['user', 'adapter', 'listId']);
    }

    public function set(string $adapter, string $listId, string $userId, array $data)
    {
        $this->replace([
                'user' => $userId,
                'adapter' => $adapter,
                'listId' => $listId,
                'data' => $data,
                'date' => 'now'
            ])
            ->execute();
    }

    public function get(string $adapter, string $listId, string $userId, ?callable $generator=null): ?array
    {
        $row = $this->select('data')
            ->where('user', '=', $userId)
            ->where('adapter', '=', $adapter)
            ->where('listId', '=', $listId)
            ->where('date', '>', '-2 weeks')
            ->toRow();

        if (!$row) {
            if ($generator) {
                if (null !== ($output = $generator())) {
                    $this->set($adapter, $listId, $userId, $output);
                }

                return $output;
            } else {
                return null;
            }
        }

        if (!isset($row['data'])) {
            return null;
        }

        return $row['data']->toArray();
    }

    public function remove(string $adapter, string $listId, string $userId)
    {
        $this->delete()
            ->where('user', '=', $userId)
            ->where('adapter', '=', $adapter)
            ->where('listId', '=', $listId)
            ->execute();
    }

    public function purge(string $adapter, string $userId)
    {
        $this->delete()
            ->where('user', '=', $userId)
            ->where('adapter', '=', $adapter)
            ->execute();
    }
}
