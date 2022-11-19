<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\mailingList\clientManifest;

use df\axis;

class Unit extends axis\unit\Table
{
    protected function createSchema($schema)
    {
        $schema->addPrimaryField('id', 'Guid');

        $schema->addField('user', 'One', 'user/client');
        $schema->addField('source', 'Text', 64);

        $schema->addField('data', 'Json');
        $schema->addField('date', 'Timestamp');

        $schema->addUniqueIndex('key', ['user', 'source']);
    }

    public function set(string $source, string $userId, array $data)
    {
        $this->replace([
                'user' => $userId,
                'source' => $source,
                'data' => $data,
                'date' => 'now'
            ])
            ->execute();
    }

    public function get(string $source, string $userId, ?callable $generator = null): ?array
    {
        $row = $this->select('data')
            ->where('user', '=', $userId)
            ->where('source', '=', $source)
            ->where('date', '>', '-2 weeks')
            ->toRow();

        if (!$row) {
            if ($generator) {
                if (null !== ($output = $generator())) {
                    $this->set($source, $userId, $output);
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

    public function remove(string $source, string $userId)
    {
        $this->delete()
            ->where('user', '=', $userId)
            ->where('source', '=', $source)
            ->execute();
    }
}
