<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\github;

use df\core;
use df\spur;

interface IMediator extends spur\IGuzzleMediator
{
    // Users
    public function getUser($username);
    public function getUserOrganizations($username);
    public function getFollowersOf($username);
    public function getFollowedBy($username);
    public function getUserRepositories($username);
    public function getUserOwnedRepositories($username);
    public function getUserWatchedRespositories($username);
    public function getUserGists($username);
    public function getUserKeys($username);

    // Organizations
    public function getOrganization($name);
    public function getOrganizationRepositories($name);

    // Repositories
    public function getRepository($name);
    public function getRepositoryBranches($name);
    public function getRepositoryBranch($name, $branchName);
    public function getRepositoryTags($name);
    public function getRepositoryLabels($name);
    public function getRepositoryReleases($name);
    public function getRepositoryRelease($name, $id);
    public function getRepositoryWatchers($name, $page = null);
    public function getRepositorySubscribers($name, $page = null);
}


interface IApiObject
{
    public function getId(): ?string;
    public function getUrl($key = null);
}

trait TApiObject
{
    protected $_id;
    protected $_urls = [];
    protected $_mediator;

    public function __construct(IMediator $mediator, core\collection\ITree $data)
    {
        $this->_mediator = $mediator;
        $this->_id = $data['id'];

        if (isset($data['url'])) {
            $this->_urls['api'] = $data['url'];
        }

        foreach ($data as $key => $node) {
            if (substr($key, -4) == '_url') {
                $this->_urls[substr($key, 0, -4)] = $node->getValue();
            }
        }

        if (isset($data->urls)) {
            $this->_urls = array_merge($this->_urls, $data->urls->toArray());
        }

        $this->_importData($data);
    }

    abstract protected function _importData(core\collection\ITree $data);

    public function getId(): ?string
    {
        return $this->_id;
    }

    public function getUrl($key = null)
    {
        if ($key === null) {
            $key = 'api';
        }

        if (isset($this->_urls[$key])) {
            return $this->_urls[$key];
        }
    }
}

interface IUser extends IApiObject
{
    public function getUsername();
    public function isSiteAdmin();

    public function getOrganizations();
    public function getFollowers();
    public function getFollowing();
    public function getWatchedRepositories();
    public function getRepositories();
    public function getOwnedRepositories();
    public function getGists();
}

interface IProfile extends IUser
{
    public function getName(): string;
    public function getEmail();
    public function getCompany();
    public function getLocation();
    public function isHireable();
    public function getBio();
    public function countPublicRepos();
    public function countPublicGists();
    public function countFollowers();
    public function countFollowing();
    public function getCreationDate();
    public function getUpdateDate();
}

interface IOrganization extends IApiObject
{
    public function getName(): string;
    public function getDescription();

    public function getRepositories();
}

interface IRepository extends IApiObject
{
    public function getName(): string;
    public function getFullName();
    public function getOwner();
    public function getDescription();
    public function getLanguage();
    public function isPrivate(): bool;
    public function isFork();
    public function getDefaultBranch();

    public function getCreationDate();
    public function getUpdateDate();
    public function getPushDate();

    public function getSize();
    public function countWatchers();
    public function countStargazers();
    public function countForks();
    public function countOpenIssues();

    public function hasIssues();
    public function hasDownloads();
    public function hasWiki();
    public function hasPages();

    public function getBranches();
    public function getBranch($name);
    public function getTags();
    public function getLabels();
    public function getReleases();
    public function getRelease($id);
    public function getWatchers($name, $page = null);
    public function getSubscribers($name, $page = null);
}

interface IBranch
{
    public function getName(): string;
    public function getCommit();
}

interface IGist extends IApiObject
{
    public function getName(): string;
    public function getOwner();
    public function isPublic(): bool;
    public function getCreationDate();
    public function getUpdateDate();
    public function countComments();
    public function getFiles();
}

interface IFile
{
    public function getFilename();
    public function getType();
    public function getLanguage();
    public function getUrl();
    public function getSize();
}

interface ITag extends IApiObject, core\IArrayProvider
{
    public function getName(): string;
    public function getVersion();
    public function getCommit();
}

interface ILabel extends IApiObject
{
    public function getName(): string;
    public function getColor();
}

interface ICommitReference extends IApiObject, core\IArrayProvider
{
    public function getSha();
}

interface ICommit extends ICommitReference
{
    public function getMessage();
    public function getTree();
    public function getParents();

    public function getAuthor();
    public function getCommitter();
}

interface IRelease extends IApiObject
{
    public function getName(): string;
    public function getTagName();
    public function getCreationDate();
    public function getPublishDate();
    public function isDraft();
    public function isPrerelease();
}
