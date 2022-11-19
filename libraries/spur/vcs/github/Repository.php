<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\github;

use df\core;

class Repository implements IRepository
{
    use TApiObject;

    protected $_name;
    protected $_fullName;
    protected $_owner;
    protected $_description;
    protected $_language;
    protected $_isPrivate;
    protected $_isFork;
    protected $_defaultBranch;
    protected $_creationDate;
    protected $_updateDate;
    protected $_pushDate;
    protected $_size = 0;
    protected $_stargazers = 0;
    protected $_watchers = 0;
    protected $_forks = 0;
    protected $_openIssues = 0;
    protected $_hasIssues;
    protected $_hasDownloads;
    protected $_hasWiki;
    protected $_hasPages;


    protected function _importData(core\collection\ITree $data)
    {
        $this->_name = $data['name'];
        $this->_fullName = $data['full_name'];
        $this->_owner = new User($this->_mediator, $data->owner);
        $this->_description = $data['description'];
        $this->_language = $data['language'];
        $this->_isPrivate = (bool)$data['private'];
        $this->_isFork = (bool)$data['fork'];
        $this->_defaultBranch = $data['default_branch'];

        $this->_creationDate = core\time\Date::factory($data['created_at']);
        $this->_updateDate = core\time\Date::factory($data['updated_at'] ?? $this->_creationDate);
        $this->_pushDate = core\time\Date::normalize($data['pushed_at']);

        $this->_size = $data['size'];
        $this->_stargazers = $data['stargazers_count'];
        $this->_watchers = $data['watchers_count'];
        $this->_forks = $data['forks_count'];
        $this->_openIssues = $data['open_issues_count'];

        $this->_hasIssues = (bool)$data['has_issues'];
        $this->_hasDownloads = (bool)$data['has_downloads'];
        $this->_hasWiki = (bool)$data['has_wiki'];
        $this->_hasPages = (bool)$data['has_pages'];

        $this->_urls['homepage'] = $data['homepage'];
    }

    public function getName(): string
    {
        return $this->_name;
    }

    public function getFullName()
    {
        return $this->_fullName;
    }

    public function getOwner()
    {
        return $this->_owner;
    }

    public function getDescription()
    {
        return $this->_description;
    }

    public function getLanguage()
    {
        return $this->_language;
    }

    public function isPrivate(): bool
    {
        return $this->_isPrivate;
    }

    public function isFork()
    {
        return $this->_isFork;
    }

    public function getDefaultBranch()
    {
        return $this->_defaultBranch;
    }


    public function getCreationDate()
    {
        return $this->_creationDate;
    }

    public function getUpdateDate()
    {
        return $this->_updateDate;
    }

    public function getPushDate()
    {
        return $this->_pushDate;
    }


    public function getSize()
    {
        return $this->_size;
    }

    public function countWatchers()
    {
        return $this->_watchers;
    }

    public function countStargazers()
    {
        return $this->_stargazers;
    }

    public function countForks()
    {
        return $this->_forks;
    }

    public function countOpenIssues()
    {
        return $this->_openIssues;
    }


    public function hasIssues()
    {
        return $this->_hasIssues;
    }

    public function hasDownloads()
    {
        return $this->_hasDownloads;
    }

    public function hasWiki()
    {
        return $this->_hasWiki;
    }

    public function hasPages()
    {
        return $this->_hasPages;
    }


    // Ext
    public function getBranches()
    {
        return $this->_mediator->getRepositoryBranches($this->_fullName);
    }

    public function getBranch($name)
    {
        return $this->_mediator->getRepositoryBranch($this->_fullName, $name);
    }

    public function getTags()
    {
        return $this->_mediator->getRepositoryTags($this->_fullName);
    }

    public function getLabels()
    {
        return $this->_mediator->getRepositoryLabels($this->_fullName);
    }

    public function getReleases()
    {
        return $this->_mediator->getRepositoryReleases($this->_fullName);
    }

    public function getRelease($id)
    {
        return $this->_mediator->getRepositoryRelease($this->_fullName, $id);
    }

    public function getWatchers($name, $page = null)
    {
        return $this->_mediator->getRepositoryWatchers($name, $page);
    }

    public function getSubscribers($name, $page = null)
    {
        return $this->_mediator->getRepositorySubscribers($name, $page);
    }
}
