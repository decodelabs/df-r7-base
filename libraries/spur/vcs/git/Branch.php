<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\git;

class Branch implements IBranch
{
    protected $_name;
    protected $_isActive = null;
    protected $_repository;

    public function __construct(ILocalRepository $repo, string $name, $isActive = null)
    {
        $this->_name = $name;

        if ($isActive !== null) {
            $isActive = (bool)$isActive;
        }

        $this->_isActive = $isActive;
        $this->_repository = $repo;
    }

    public function getName(): string
    {
        return $this->_name;
    }

    public function exists()
    {
        return in_array($this->_name, $this->_repository->getBranchNames());
    }

    public function isActive()
    {
        if ($this->_isActive === null) {
            $this->_isActive = $this->_name == $this->_repository->getActiveBranchName();
        }

        return $this->_isActive;
    }

    public function delete()
    {
        $this->_repository->deleteBranch($this);
        return $this;
    }


    public function setDescription($description)
    {
        $this->_repository->_runCommand('config', [
            'branch.' . $this->_name . '.description' => $description
        ]);

        return $this;
    }

    public function getDescription()
    {
        return trim($this->_repository->_runCommand('config', [
            'branch.' . $this->_name . '.description'
        ]));
    }

    public function getTree()
    {
        return $this->_repository->getTree($this->_name);
    }

    public function getRepository()
    {
        return $this->_repository;
    }
}
