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

class Commit extends CommitReference implements ICommit
{
    protected $_message;
    protected $_tree;
    protected $_parents = [];
    protected $_author;
    protected $_committer;

    protected function _importData(core\collection\ITree $data)
    {
        parent::_importData($data);

        $this->_message = $data->commit['message'];
        $this->_tree = new CommitReference($this->_mediator, $data->commit->tree);

        foreach ($data->parents as $parent) {
            $this->_parents[] = new CommitReference($this->_mediator, $parent);
        }

        $this->_author = new User($this->_mediator, $data->author);
        $this->_committer = new User($this->_mediator, $data->committer);
    }

    public function getMessage()
    {
        return $this->_message;
    }

    public function getTree()
    {
        return $this->_tree;
    }

    public function getParents()
    {
        return $this->_parents;
    }

    public function getAuthor()
    {
        return $this->_author;
    }

    public function getCommitter()
    {
        return $this->_committer;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setProperties([
                '*sha' => $inspector($this->_id),
                '*tree' => $inspector($this->_tree),
                '*parents' => $inspector($this->_parents),
                '*author' => $inspector($this->_author),
                '*committer' => $inspector($this->_committer),
                '*urls' => $inspector($this->_urls)
            ]);
    }
}
