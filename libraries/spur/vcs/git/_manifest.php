<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\spur\vcs\git;

use DecodeLabs\Exceptional;
use DecodeLabs\Systemic;
use DecodeLabs\Terminus\Session;

interface IRepository
{
    public function setGitUser($user);
    public function getGitUser();

    public static function setGitPath($path);
    public static function getGitPath();

    public function getRefs();
    public function getHeads();
    public function getTags();

    public function cloneTo($path);
}

interface ILocalRepository extends IRepository
{
    public static function createNew($path, $isBare = false);
    public static function createClone($repoUrl, $path, $isBare = false);
    public static function getGitVersion();

    public function getRepositoryPath();
    public function isBare();

    public function setConfig($key, $value);
    public function getConfig($key);

    public function getBranchNames();
    public function getBranches();
    public function getBranch($name);
    public function getActiveBranchName();
    public function getActiveBranch();
    public function deleteBranch($branch);
    public function setUpstream($remote, $remoteBranch = 'master', $localBranch = 'master');

    public function countRemoteBranches();

    public function getRemotes();
    public function countRemotes();
    public function addRemote($name, $url);

    public function getCommitStatus();
    public function getCommitIds($target = null, $limit = null, $offset = null);
    public function getCommits($target = null, $limit = null, $offset = null);
    public function countCommits($target = null);
    public function getHeadCommitIds();
    public function getHeadCommits();
    public function getCommit($id);

    public function countUnpushedCommits($remoteBranch = null);
    public function getUnpushedCommitIds($remoteBranch = null);
    public function getUnpushedCommits($remoteBranch = null);

    public function countUnpulledCommits($remoteBranch = null);
    public function getUnpulledCommitIds($remoteBranch = null);
    public function getUnpulledCommits($remoteBranch = null);

    public function commitAllChanges($message);

    public function getTree($id);

    public function updateRemote($remote = null);
    public function pull($remoteBranch = null);
    public function push($remoteBranch = null);

    public function checkoutCommit($commitId);
}

interface IRemote extends IRepository
{
    public function getUrl();
}

trait TRepository
{
    protected static $_gitPath = '/usr/bin/git';
    protected $_gitUser;
    protected $_session;

    public function setGitUser($user)
    {
        if (empty($user)) {
            $user = null;
        }

        $this->_gitUser = $user;
        return $this;
    }

    public function getGitUser()
    {
        return $this->_gitUser;
    }

    public static function setGitPath($path)
    {
        self::$_gitPath = $path;
    }

    public static function getGitPath()
    {
        return self::$_gitPath;
    }

    public function setCliSession(?Session $session)
    {
        $this->_session = $session;
        return $this;
    }

    public function getCliSession(): ?Session
    {
        return $this->_session;
    }

    protected static function _runCommandIn($path, $command, array $arguments = null, ?Session $session = null, string $user = null)
    {
        $args = [$command];

        if (!empty($arguments)) {
            foreach ($arguments as $key => $value) {
                if ($value === null || $value === false) {
                    continue;
                }

                if (is_int($key) && is_string($value)) {
                    $key = $value;
                    $value = true;
                }

                if (!is_bool($value) && substr($key, 0, 1) != '-') {
                    $key = '-' . $key;

                    if (strlen($key) > 2) {
                        $key = '-' . $key;
                    }
                }

                $arg = $key;

                if (!is_bool($value)) {
                    if (substr($key, 0, 2) == '--') {
                        $arg .= '=';
                    }

                    $arg .= escapeshellarg($value);
                }

                $args[] = $arg;
            }
        }

        $result = Systemic::{$session ? 'liveCapture' : 'capture'}(
            [self::$_gitPath, ...$args],
            $path
        );

        $output = ltrim($result->getOutput(), "\r\n");
        $output = rtrim($output);

        if ($result->hasError()) {
            $error = trim($result->getError());

            if (empty($output)) {
                throw Exceptional::Runtime($error);
            }

            if (
                strtolower(substr($error, 0, 5)) == 'error:' ||
                stristr($error, 'aborting')
            ) {
                throw Exceptional::Runtime($error);
            }

            $output .= "\n" . $error;
        }

        return $output;
    }
}

interface IBranch
{
    public function getName(): string;
    public function exists();
    public function isActive();
    public function delete();

    public function setDescription($description);
    public function getDescription();

    public function getTree();

    public function getRepository();
}


interface ICommit
{
    public function getId(): string;
    public function getTreeId();
    public function getTree();
    public function getParentIds();
    public function getParents();
    public function getAuthor();
    public function getCreationTimestamp();
    public function getCreationDate();
    public function getCommitter();
    public function getCommitTimestamp();
    public function getCommitDate();
    public function getMessage();
    public function getRepository();
}

interface IFile
{
    public function getId(): string;
    public function _setName($name);
    public function getName(): string;
    public function _setMode($mode);
    public function getMode();
    public function getContent();
    public function _setSize($size);
    public function getSize();
    public function getRepository();
}

interface ITree
{
    public function getId(): string;
    public function _setName($name);
    public function getName(): string;
    public function _setMode($mode);
    public function getMode();

    public function getObjects();
    public function getFiles();
    public function getTrees();

    public function getRepository();
}


interface IStatus extends \Countable
{
    public function refresh();
    public function getTracked();
    public function hasTracked();
    public function countTracked();
    public function getUntracked();
    public function hasUntracked();
    public function countUntracked();

    public function hasFile($path);
    public function getFileState($path);
    public function isTracked($path);
    public function isUntracked($path);

    public function countUnpushedCommits($remoteBranch = null);
    public function getUnpushedCommitIds($remoteBranch = null);
    public function getUnpushedCommits($remoteBranch = null);

    public function countUnpulledCommits($remoteBranch = null);
    public function getUnpulledCommitIds($remoteBranch = null);
    public function getUnpulledCommits($remoteBranch = null);
}

interface ITag
{
    public function getName(): string;
    public function getVersion();
    public function getCommit();
    public function getCommitId();
    public function getRepository();
}
