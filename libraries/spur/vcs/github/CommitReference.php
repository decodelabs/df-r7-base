<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\github;

use DecodeLabs\Glitch\Dumpable;

use df\core;

class CommitReference implements ICommitReference, Dumpable
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
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'property:*sha' => $this->_id;
        yield 'values' => $this->_urls;
    }
}
