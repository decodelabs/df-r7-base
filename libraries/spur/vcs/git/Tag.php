<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\git;

use df;
use df\core;
use df\flex;
use df\spur;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Tag implements ITag, Inspectable
{
    protected $_name;
    protected $_version;
    protected $_commitId;
    protected $_repository;

    public function __construct(IRepository $repository, string $name, $commit)
    {
        $this->_name = $name;
        $this->_commitId = $commit;
        $this->_repository = $repository;
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
        return Commit::factory($this->_repository, $this->_commitId);
    }

    public function getCommitId()
    {
        return $this->_commitId;
    }

    public function getRepository()
    {
        return $this->_repository;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setProperties([
            '*name' => $inspector($this->_name),
            '*commit' => $inspector($this->_commitId)
        ]);
    }
}
