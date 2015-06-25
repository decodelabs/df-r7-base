<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\github;

use df;
use df\core;
use df\spur;
use df\link;

class Mediator implements IMediator, core\IDumpable {
    
    use spur\THttpMediator;

    const BASE_URL = 'https://api.github.com/';
    const API_VERSION = 'v3';


// Users
    public function getUser($username) {
        $data = $this->callServer('get', 'users/'.rawurlencode($username));
        return new Profile($this, $data);
    }

    public function getUserOrganizations($username) {
        $data = $this->callServer('get', 'users/'.rawurlencode($username).'/orgs');
        $output = [];

        foreach($data as $org) {
            $output[] = new Organization($this, $org);
        }

        return $output;
    }

    public function getFollowersOf($username) {
        $data = $this->callServer('get', 'users/'.rawurlencode($username).'/followers');
        $output = [];

        foreach($data as $user) {
            $output[] = new User($this, $user);
        }

        return $output;
    }

    public function getFollowedBy($username) {
        $data = $this->callServer('get', 'users/'.rawurlencode($username).'/following');
        $output = [];

        foreach($data as $user) {
            $output[] = new User($this, $user);
        }

        return $output;
    }

    public function getUserRepositories($username) {
        $data = $this->callServer('get', 'users/'.rawurlencode($username).'/repos', [
            'type' => 'all',
            'sort' => 'full_name',
            'direction' => 'asc'
        ]);
        $output = [];

        foreach($data as $repo) {
            $output[] = new Repository($this, $repo);
        }

        return $output;
    }

    public function getUserOwnedRepositories($username) {
        $data = $this->callServer('get', 'users/'.rawurlencode($username).'/repos', [
            'type' => 'owner',
            'sort' => 'full_name',
            'direction' => 'asc'
        ]);
        $output = [];

        foreach($data as $repo) {
            $output[] = new Repository($this, $repo);
        }

        return $output;
    }

    public function getUserWatchedRespositories($username) {
        $data = $this->callServer('get', 'users/'.rawurlencode($username).'/watched');
        $output = [];

        foreach($data as $repo) {
            $output[] = new Repository($this, $repo);
        }

        return $output;
    }

    public function getUserGists($username) {
        $data = $this->callServer('get', 'users/'.rawurlencode($username).'/gists');
        $output = [];

        foreach($data as $gist) {
            $output[] = new Gist($this, $gist);
        }

        return $output;
    }

    public function getUserKeys($username) {
        $data = $this->callServer('get', 'users/'.rawurlencode($username).'/keys');
        $output = [];

        foreach($data as $key) {
            $output[$key['id']] = $key['key'];
        }

        return $output;
    }


// Organizations
    public function getOrganization($name) {
        $data = $this->callServer('get', 'orgs/'.rawurlencode($name));
        return new Organization($this, $data);
    }

    public function getOrganizationRepositories($name) {
        $data = $this->callServer('get', 'orgs/'.rawurlencode($name).'/repos', [
            'type' => 'all'
        ]);
        $output = [];

        foreach($data as $repo) {
            $output[] = new Repository($this, $repo);
        }

        return $output;
    }


// Repositories
    public function getRepository($name) {
        list($username, $name) = explode('/', $name, 2);
        $data = $this->callServer('get', 'repos/'.rawurlencode($username).'/'.rawurlencode($name));
        return new Repository($this, $data);
    }

    public function getRepositoryBranches($name) {
        list($username, $name) = explode('/', $name, 2);
        $data = $this->callServer('get', 'repos/'.rawurlencode($username).'/'.rawurlencode($name).'/branches');
        $output = [];

        foreach($data as $branch) {
            $output[$branch['name']] = new CommitReference($this, $branch->commit);
        }

        return $output;
    }

    public function getRepositoryBranch($name, $branchName) {
        list($username, $name) = explode('/', $name, 2);
        $data = $this->callServer('get', 'repos/'.rawurlencode($username).'/'.rawurlencode($name).'/branches/'.rawurlencode($branchName));
        return new Branch($this, $data);
    }

    public function getRepositoryTags($name) {
        list($username, $name) = explode('/', $name, 2);

        $data = $this->_getPagedData('repos/'.rawurlencode($username).'/'.rawurlencode($name).'/tags');
        $output = [];

        foreach($data as $tag) {
            $output[] = new Tag($this, $tag);
        }

        return $output;
    }

    public function getRepositoryLabels($name) {
        list($username, $name) = explode('/', $name, 2);
        $data = $this->callServer('get', 'repos/'.rawurlencode($username).'/'.rawurlencode($name).'/labels');
        $output = [];

        foreach($data as $label) {
            $output[] = new Label($this, $label);
        }

        return $output;
    }

    public function getRepositoryReleases($name) {
        list($username, $name) = explode('/', $name, 2);
        $data = $this->callServer('get', 'repos/'.rawurlencode($username).'/'.rawurlencode($name).'/releases');
        $output = [];

        foreach($data as $release) {
            $output[] = new Release($this, $release);
        }

        return $output;
    }

    public function getRepositoryRelease($name, $id) {
        list($username, $name) = explode('/', $name, 2);
        $data = $this->callServer('get', 'repos/'.rawurlencode($username).'/'.rawurlencode($name).'/releases/'.rawurlencode($id));
        return new Release($this, $data);
    }

    public function getRepositoryWatchers($name, $page=null) {
        list($username, $name) = explode('/', $name, 2);

        if($page === null) {
            $page = 1;
        }

        $data = $this->callServer('get', 'repos/'.rawurlencode($username).'/'.rawurlencode($name).'/watchers', [
            'page' => $page
        ]);
        $output = [];

        foreach($data as $user) {
            $output[] = new User($this, $user);
        }

        return $output;
    }

    public function getRepositorySubscribers($name, $page=null) {
        list($username, $name) = explode('/', $name, 2);

        if($page === null) {
            $page = 1;
        }

        $data = $this->callServer('get', 'repos/'.rawurlencode($username).'/'.rawurlencode($name).'/subscribers', [
            'page' => $page
        ]);
        $output = [];

        foreach($data as $user) {
            $output[] = new User($this, $user);
        }

        return $output;
    }


// Server
    protected function _createUrl($path) {
        return self::BASE_URL.ltrim($path, '/');
    }

    protected function _prepareRequest(link\http\IRequest $request) {
        $request->headers->set('accept', 'application/vnd.github.'.self::API_VERSION.'+json');
    }

    protected function _getPagedData($path) {
        $output = [];
        $page = 0;

        while(true) {
            $response = $this->callServer('get', $path, [
                'per_page' => 100,
                'page' => ++$page
            ], true);  

            $data = $response->getJsonContent();

            if($data->isEmpty()) {
                break;
            }

            $output = array_merge($output, $data->getChildren());

            $pagination = [];

            foreach(explode(',', $response->getHeaders()->get('Link')) as $link) {
                if(preg_match('/<(.*)>; rel="(.*)"/i', trim($link, ','), $matches)) {
                    $pagination[$matches[2]] = $matches[1];
                }
            }

            if(!isset($pagination['next'])) {
                break;
            }
        }

        return $output;
    }

// Dump
    public function getDumpProperties() {
        return [];
    }
}