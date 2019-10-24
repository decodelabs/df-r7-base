<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\git;

use df;
use df\core;
use df\spur;

use DecodeLabs\Glitch\Glitch;
use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Commit implements ICommit, Inspectable
{
    protected $_id;
    protected $_treeId;
    protected $_parentIds = [];
    protected $_author;
    protected $_creationTimestamp;
    protected $_committer;
    protected $_commitTimestamp;
    protected $_message = '';
    protected $_isFetched = false;
    protected $_repository;

    public static function factory(ILocalRepository $repo, $id)
    {
        $output = new self($repo, $id);
        $output->_fetchData();
        return $output;
    }

    public static function extractFromRevList(ILocalRepository $repo, array &$result)
    {
        $commitId = self::_extractLineValue('commit', array_shift($result));

        if (!$commitId) {
            return null;
        }

        $output = new self($repo, $commitId);
        $output->_importRevListData($result);

        array_shift($result);
        return $output;
    }

    protected static function _extractLineValue($key, $line)
    {
        $parts = explode(' ', $line, 2);

        if ($key != array_shift($parts)) {
            throw Glitch::EUnexpectedValue(
                'Commit description key \''.$key.'\' was not found on line: '.$line
            );
        }

        return array_shift($parts);
    }

    protected static function _parseUser($value)
    {
        if (preg_match('/^(.+) (\d+) .*$/', $value, $matches)) {
            return [$matches[1], $matches[2]];
        }

        return [null, null];
    }


    public function __construct(ILocalRepository $repo, string $commitId)
    {
        $this->_repository = $repo;
        $this->_id = $commitId;
    }

    public function getId(): string
    {
        return $this->_id;
    }

    public function getTreeId()
    {
        if (!$this->_isFetched) {
            $this->_fetchData();
        }

        return $this->_treeId;
    }

    public function getTree()
    {
        if (!$this->_isFetched) {
            $this->_fetchData();
        }

        return $this->_repository->getTree($this->_treeId);
    }

    public function getParentIds()
    {
        if (!$this->_isFetched) {
            $this->_fetchData();
        }

        return $this->_parentIds;
    }

    public function getParents()
    {
        if (!$this->_isFetched) {
            $this->_fetchData();
        }

        $output = [];

        foreach ($this->_parentIds as $id) {
            $output[$id] = $this->_repository->getCommit($id);
        }

        return $output;
    }

    public function getAuthor()
    {
        if (!$this->_isFetched) {
            $this->_fetchData();
        }

        return $this->_author;
    }

    public function getCreationTimestamp()
    {
        if (!$this->_isFetched) {
            $this->_fetchData();
        }

        return $this->_creationTimestamp;
    }

    public function getCreationDate()
    {
        if (!$this->_isFetched) {
            $this->_fetchData();
        }

        return new core\time\Date($this->_creationTimestamp);
    }

    public function getCommitter()
    {
        if (!$this->_isFetched) {
            $this->_fetchData();
        }

        return $this->_committer;
    }

    public function getCommitTimestamp()
    {
        if (!$this->_isFetched) {
            $this->_fetchData();
        }

        return $this->_commitTimestamp;
    }

    public function getCommitDate()
    {
        if (!$this->_isFetched) {
            $this->_fetchData();
        }

        return new core\time\Date($this->_commitTimestamp);
    }

    public function getMessage()
    {
        if (!$this->_isFetched) {
            $this->_fetchData();
        }

        return $this->_message;
    }

    public function getRepository()
    {
        return $this->_repository;
    }

    protected function _fetchData()
    {
        $result = $this->_repository->_runCommand('rev-list', [
            '--max-count' => 1,
            '--pretty' => 'raw',
            $this->_id,
            '--'
        ]);

        $result = explode("\n", $result);
        $commitId = self::_extractLineValue('commit', array_shift($result));

        if (!$commitId) {
            throw Glitch::ERuntime(
                'Could not find commit '.$this->_id.' in repository'
            );
        }

        $this->_importRevListData($result);
    }

    protected function _importRevListData(array &$result)
    {
        $this->_treeId = self::_extractLineValue('tree', array_shift($result));

        while (!empty($result) && 0 === strpos($result[0], 'parent')) {
            $this->_parentIds[] = self::_extractLineValue('parent', array_shift($result));
        }

        $author = self::_extractLineValue('author', array_shift($result));
        list($this->_author, $this->_creationTimestamp) = self::_parseUser($author);

        $committer = self::_extractLineValue('committer', array_shift($result));
        list($this->_committer, $this->_commitTimestamp) = self::_parseUser($committer);

        array_shift($result);

        while (!empty($result) && 0 === strpos($result[0], '   ')) {
            $this->_message .= trim(array_shift($result))."\n";
        }

        $this->_message = rtrim($this->_message);
        $this->_isFetched = true;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        if (!$this->_isFetched) {
            $entity->setDefinition($this->_id);
            return;
        }

        $entity
            ->setText($this->_message)
            ->setDefinition($this->_id)
            ->setProperties([
                '*tree' => $inspector($this->_treeId),
                '*parents' => $inspector($this->_parentIds),
                '*author' => $inspector($this->_author),
                '*created' => $inspector($this->getCreationDate()),
                '*committer' => $inspector($this->_committer),
                '*committed' => $inspector($this->getCommitDate())
            ]);
    }
}
