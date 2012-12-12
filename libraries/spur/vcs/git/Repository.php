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
    
class Repository implements IRepository {

    protected $_path;
    protected $_isBare = false;

    protected $_branches = null;
    protected $_activeBranch = null;

    public static function createNew($path, $isBare=false) {
        if(!is_dir($path)) {
            throw new RuntimeException(
                'Prospective repository directory could not be found'
            );
        }

        if(!is_writable($path)) {
            throw new RuntimeException(
                'Cannot write to repository destination'
            );
        }

        if(!$isBare || version_compare('1.5.6.6', self::getGitVersion(), '<=')) {
            self::_runCommandIn($path, 'init', [
                '--bare' => (bool)$isBare,
                '-q'
            ]);
        } else {
            self::_runCommandIn($path, '--bare init -q');
        }

        return new self($path);
    }

    public static function createClone($repoUrl, $path, $isBare=false) {
        core\io\Util::ensureDirExists(dirname($path));

        if(!is_writable(dirname($path))) {
            throw new RuntimeException(
                'Cannot write to repository clone destination'
            );
        }

        self::_runCommandIn(dirname($path), 'clone', [
            '--bare' => (bool)$isBare,
            '-q',
            $repoUrl,
            $path
        ]);

        return new self($path);
    }

    public static function getGitVersion() {
        return substr($this->_runCommandIn(null, '--version'), 12);
    }

    public function __construct($path) {
        if(!is_dir($path)) {
            throw new RuntimeException(
                'The git repository could not be found'
            );
        }

        if(basename($path) == '.git') {
            $path = dirname($path);
            $this->_isBare = false;
        } else if(is_dir($path.'/.git')) {
            $this->_isBare = false;
        } else if(is_file($path.'/HEAD')) {
            $this->_isBare = true;
        } else {
            throw new RuntimeException(
                'Directory does not appear to be a git repository'
            );
        }

        $this->_path = $path;
    }

    public function getRepositoryPath() {
        return $this->_path;
    }

    public function isBare() {
        return $this->_isBare;
    }



// Branches
    public function getBranchNames() {
        $this->_fillBranchCache();
        return $this->_branches;
    }

    public function getBranches() {
        $this->_fillBranchCache();
        $output = array();

        foreach($this->_branches as $name) {
            $isActive = $name == $this->_activeBranch;
            $output[] = new Branch($this, $name, $isActive);
        }

        return $output;
    }

    public function getBranch($name) {
        $this->_fillBranchCache();

        if(!in_array($name, $this->_branches)) {
            throw new RuntimeException(
                'Branch '.$name.' could not be found'
            );  
        }

        return new Branch($this, $name);
    }

    public function getActiveBranchName() {
        $this->_fillBranchCache();
        return $this->_activeBranch;
    }

    public function getActiveBranch() {
        return new Branch($this, $this->getActiveBranchName(), true);
    }

    public function deleteBranch($branch) {
        if($branch instanceof IBranch) {
            $branch = $branch->getName();
        }

        $this->_runCommand('branch', [
            '-D',
            $branch
        ]);

        $this->_clearBranchCache();
        return $this;
    }

    protected function _fillBranchCache() {
        if($this->_branches === null) {
            $result = $this->_runCommand('branch --list');
            $this->_branches = array();

            foreach(explode("\n", $result) as $line) {
                $line = trim($line);

                if(empty($line)) {
                    continue;
                }

                if(substr($line, 0, 2) == '* ') {
                    $line = substr($line, 2);
                    $this->_activeBranch = $line;
                }

                $this->_branches[] = $line;
            }
        }
    }

    protected function _clearBranchCache() {
        $this->_branches = null;
        $this->_activeBranch = null;
    }


// Tags
    public function getTags() {
        $result = $this->_runCommand('for-each-ref', [
            '--format' => '%(refname),%(objectname)',
            'refs/tags'
        ]);

        $output = array();

        if(!empty($result)) {
            foreach(explode("\n", $result) as $line) {
                $parts = explode(',', $line, 2);
                $output[substr($parts[0], 10)] = array_pop($parts);
            }
        }
        
        return $output;
    }


// Commits
    public function getCommitIds($target, $limit=null, $offset=null) {
        $result = $this->_runCommand('rev-list', [
            '--max-count' => $limit,
            '--skip' => $offset,
            $target,
            '--'
        ]);

        return explode("\n", $result);
    }

    public function getCommits($target, $limit=null, $offset=null) {
        $result = $this->_runCommand('rev-list', [
            '--max-count' => $limit,
            '--skip' => $offset,
            '--pretty' => 'raw',
            $target,
            '--'
        ]);

        $output = array();
        $lines = explode("\n", $result);

        while(!empty($lines)) {
            if(!$commit = Commit::extractFromRevList($this, $lines)) {
                continue;
            }

            $output[] = $commit;
        }
        
        return $output;
    }

    public function getHeadCommitIds() {
        $result = $this->_runCommand('for-each-ref', [
            '--format' => '%(refname),%(objectname)',
            'refs/heads'
        ]);

        $output = array();

        if(!empty($result)) {
            foreach(explode("\n", $result) as $line) {
                $parts = explode(',', $line, 2);
                $output[substr($parts[0], 11)] = array_pop($parts);
            }
        }
        
        return $output;
    }

    public function getHeadCommits() {
        $output = $this->getHeadCommitIds();

        foreach($output as $key => $id) {
            $output[$key] = $this->getCommit($id);
        }

        return $output;
    }

    public function getCommit($id) {
        return Commit::factory($this, $id);
    }


// Tree / blob
    public function getTree($id) {
        return new Tree($this, $id);
    }

    public function getBlob($id) {
        return new Blob($this, $id);
    }


// Commands
    public function _runCommand($command, array $arguments=null) {
        return self::_runCommandIn($this->_path, $command, $arguments);
    }

    protected static function _runCommandIn($path, $command, array $arguments=null) {
        $argString = $command;

        if(!empty($arguments)) {
            foreach($arguments as $key => $value) {
                if($value === null || $value === false) {
                    continue;
                }

                if(is_int($key) && is_string($value)) {
                    $key = $value;
                    $value = true;
                }

                if(!is_bool($value) && substr($key, 0, 1) != '-') {
                    $key = '-'.$key;

                    if(strlen($key) > 2) {
                        $key = '-'.$key;
                    }
                }

                $argString .= ' '.$key;

                if(!is_bool($value)) {
                    if(substr($key, 0, 2) == '--') {
                        $argString .= '=';
                    }

                    $argString .= escapeshellarg($value);
                }
                
            }
        }

        $result = halo\process\launcher\Base::factory('git', $argString)
            ->setWorkingDirectory($path)
            ->launch();

        if($result->hasError()) {
            throw new RuntimeException(
                trim($result->getError())
            );
        }

        return trim($result->getOutput());
    }
}