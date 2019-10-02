<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\github;

use df;
use df\core;
use df\flex;
use df\spur;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Tag implements ITag, Inspectable
{
    use TApiObject;

    protected $_name;
    protected $_version;
    protected $_commit;

    protected function _importData(core\collection\ITree $data)
    {
        $this->_id = $data->commit['sha'];
        $this->_name = $data['name'];
        $this->_urls['commit'] = $data->commit['url'];
        $this->_commit = new CommitReference($this->_mediator, $data->commit);
    }

    public function getName(): string
    {
        return $this->_name;
    }

    public function getVersion()
    {
        if ($this->_version === null) {
            $name = $this->_name;

            if (preg_match('/^[a-zA-Z][0-9]/', $name)) {
                $name = substr($name, 1);
            }

            try {
                $this->_version = flex\Version::factory($name);
            } catch (\Throwable $e) {
                $this->_version = false;
            }
        }

        return $this->_version;
    }

    public function getCommit()
    {
        return $this->_commit;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->_id,
            'name' => $this->_name,
            'version' => $this->_version ? (string)$this->_version : null,
            'commit' => $this->_commit->toArray(),
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
                '*id' => $inspector($this->_id),
                '*name' => $inspector($this->_name),
                '*version' => $inspector($this->getVersion()),
                '*commit' => $inspector($this->_commit),
                '*urls' => $inspector($this->_urls)
            ]);
    }
}
