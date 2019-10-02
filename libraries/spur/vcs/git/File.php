<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\git;

use df;
use df\core;
use df\spur;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class File implements IFile, Inspectable
{
    protected $_id;
    protected $_name = null;
    protected $_mode = null;
    protected $_content = null;
    protected $_size = null;
    protected $_repository;

    public function __construct(ILocalRepository $repo, string $id, $name=null)
    {
        $this->_id = $id;
        $this->_repository = $repo;
        $this->_name = $name;
    }

    public function getId(): string
    {
        return $this->_id;
    }

    public function _setName($name)
    {
        $this->_name = $name;
        return $this;
    }

    public function getName(): string
    {
        if ($this->_name === null) {
            $this->_fetchData();
        }

        return $this->_name;
    }

    public function _setMode($mode)
    {
        $this->_mode = $mode;
        return $this;
    }

    public function getMode()
    {
        if ($this->_mode === null) {
            $this->_fetchData();
        }

        return $this->_mode;
    }

    public function getContent()
    {
        if ($this->_content === null) {
            $this->_content = trim($this->_repository->_runCommand('cat-file', [
                '-p',
                $this->_id
            ]));
        }

        return $this->_content;
    }

    public function _setSize($size)
    {
        $this->_size = $size;
        return $this;
    }

    public function getSize()
    {
        if ($this->_size === null) {
            $this->_size = trim($this->_repository->_runCommand('cat-file', [
                '-s',
                $this->_id
            ]));
        }

        return $this->_size;
    }

    protected function _fetchData()
    {
        $result = $this->_repository->_runCommand('ls-tree -r HEAD | grep ', [$this->_id]);

        list(
            $this->_mode,
            $type,
            $id,
            $this->_name
        ) = explode(' ', str_replace("\t", ' ', $result), 4);
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
        $entity->setProperty('*id', $inspector($this->_id));

        if ($this->_name !== null) {
            $entity->setProperty('*name', $inspector($this->_name));
        }

        if ($this->_mode !== null) {
            $entity->setProperty('*mode', $inspector($this->_mode));
        }

        if ($this->_size !== null) {
            $entity->setProperty('*size', $inspector($this->_size));
        }
    }
}
