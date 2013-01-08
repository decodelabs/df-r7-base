<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\git;

use df;
use df\core;
use df\spur;
use df\halo;
    

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}



// Interfaces
interface IRepository {
    public static function createNew($path, $isBare=false);
    public static function createClone($repoUrl, $path, $isBare=false);
    public static function getGitVersion();

    public function getRepositoryPath();
    public function isBare();
    public function setGitUser($user);
    public function getGitUser();
    public static function setGitPath($path);
    public static function getGitPath();

    public function getBranchNames();
    public function getBranches();
    public function getBranch($name);
    public function getActiveBranchName();
    public function getActiveBranch();
    public function deleteBranch($branch);

    public function getTags();

    public function getCommitStatus();
    public function getCommitIds($target, $limit=null, $offset=null);
    public function getCommits($target, $limit=null, $offset=null);
    public function getHeadCommitIds();
    public function getHeadCommits();
    public function getCommit($id);

    public function countUnpushedCommits($remoteBranch=null);
    public function getUnpushedCommitIds($remoteBranch=null);
    public function getUnpushedCommits($remoteBranch=null);

    public function countUnpulledCommits($remoteBranch=null);
    public function getUnpulledCommitIds($remoteBranch=null);
    public function getUnpulledCommits($remoteBranch=null);

    public function getTree($id);
    public function getBlob($id);

    public function updateRemote($remote=null);
    public function pull($remoteBranch=null);
}

interface IBranch {
    public function getName();
    public function exists();
    public function isActive();
    public function delete();

    public function setDescription($description);
    public function getDescription();

    public function getTree();

    public function getRepository();
}


interface ICommit {
    public function getId();
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

interface IFile {
    public function getId();
    public function _setName($name);
    public function getName();
    public function _setMode($mode);
    public function getMode();
    public function getContent();
    public function _setSize($size);
    public function getSize();
    public function getRepository();
}

interface ITree {
    public function getId();
    public function _setName($name);
    public function getName();
    public function _setMode($mode);
    public function getMode();

    public function getObjects();
    public function getFiles();
    public function getTrees();

    public function getRepository();
}


interface IStatus extends \Countable {
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

    public function countUnpushedCommits($remoteBranch=null);
    public function getUnpushedCommitIds($remoteBranch=null);
    public function getUnpushedCommits($remoteBranch=null);

    public function countUnpulledCommits($remoteBranch=null);
    public function getUnpulledCommitIds($remoteBranch=null);
    public function getUnpulledCommits($remoteBranch=null);
}