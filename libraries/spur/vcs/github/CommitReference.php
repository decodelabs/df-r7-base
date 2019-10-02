<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\github;

use df;
use df\core;
use df\spur;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class CommitReference implements ICommitReference, Inspectable
{
    use TApiObject;

    protected function _importData(core\collection\ITree $data)
    {
        $this->_id = $data['sha'];
    }

    public function getSha()
    {
        return $this->_id;
    }

    public function toArray(): array
    {
        return [
            'sha' => $this->_id,
            'urls' => $this->_urls
        ];
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setProperties([
                '*sha' => $inspector($this->_id)
            ])
            ->setValues($inspector->inspectList($this->_urls));
    }
}
