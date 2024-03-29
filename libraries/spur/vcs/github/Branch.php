<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\github;

use DecodeLabs\Glitch\Dumpable;

use df\core;

class Branch implements IBranch, Dumpable
{
    use TApiObject;

    protected $_commit;

    protected function _importData(core\collection\ITree $data)
    {
        $this->_id = $data['name'];
        $this->_urls = $data->_links->toArray();
        $this->_commit = new Commit($this->_mediator, $data->commit);

        if ($self = $this->getUrl('self')) {
            $self = dirname(dirname($self));
            $this->_urls['zipball'] = $self . '/zipball/' . $this->_id;
            $this->_urls['tarball'] = $self . '/tarball/' . $this->_id;
        }
    }

    public function getName(): string
    {
        return $this->_id;
    }

    public function getCommit()
    {
        return $this->_commit;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '*name' => $this->_id,
            '*commit' => $this->_commit,
            '*urls' => $this->_urls
        ];
    }
}
