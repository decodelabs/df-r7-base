<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\spur\vcs\git;

class Remote implements IRemote
{
    use TRepository;

    protected $_url;

    public function __construct($url)
    {
        $this->_url = $url;
    }

    public function getUrl()
    {
        return $this->_url;
    }



    public function getRefs()
    {
        $result = $this->_runCommand('ls-remote', [
            '--tags', '--heads', $this->_url
        ]);

        return $this->_splitRefList($result);
    }

    public function getTags()
    {
        $result = $this->_runCommand('ls-remote', [
            '--tags', $this->_url
        ]);

        return $this->_splitRefList($result, function ($name, $commit) {
            return new Tag($this, substr($name, 10), $commit);
        });
    }

    public function getHeads()
    {
        $result = $this->_runCommand('ls-remote', [
            '--heads', $this->_url
        ]);

        return $this->_splitRefList($result, function ($name) {
            return substr($name, 11);
        });
    }


    public function cloneTo($path)
    {
        return Repository::createClone($this->_url, $path);
    }

    protected function _splitRefList($result, $callback = null)
    {
        $result = str_replace(["\t", "\r\n"], [' ', "\n"], trim((string)$result));
        $lines = explode("\n", $result);
        $output = [];

        foreach ($lines as $line) {
            $parts = explode(' ', $line, 2);
            $commit = array_shift($parts);
            $name = array_shift($parts);

            if ($callback) {
                $name = $callback($name, $commit);
            }

            if (is_object($name)) {
                $output[] = $name;
            } else {
                $output[$name] = $commit;
            }
        }

        return $output;
    }


    // Commands
    public function _runCommand($command, array $arguments = null)
    {
        return self::_runCommandIn(null, $command, $arguments, $this->_session, $this->_gitUser);
    }
}
