<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\composer\_nodes;

use df;
use df\core;
use df\apex;
use df\halo;
use df\arch;

class TaskInit extends arch\node\Task
{
    public function execute()
    {
        $this->ensureDfSource();

        $file = new core\fs\File($this->app->path.'/composer.json');

        if ($file->exists()) {
            if (!isset($this->request['no-update'])) {
                $this->runChild('composer/install');
            }

            return;
        }


        // Name
        $parts = explode('/', $this->app->path);
        $appName = array_pop($parts);
        $container = array_pop($parts);

        $name = $this->_askFor('Enter package name', function ($answer) {
            return $this->data->newValidator()
                ->addRequiredField('packageName', 'text');
        }, $container.'/'.$appName);


        // Description
        $description = $this->_askFor('Enter app name', function ($answer) {
            return $this->data->newValidator()
                ->addRequiredField('appName', 'text');
        }, $this->app->getName());


        // Author
        $authors = [];
        $primeUser = $this->data->user->client->select('fullName', 'email')
            ->where('id', '=', 1)
            ->toRow();


        $authorName = $this->_askFor('Enter your name', function ($answer) {
            return $this->data->newValidator()
                ->addField('name', 'text');
        }, $primeUser['fullName'] ?? null);


        if (!empty($authorName)) {
            $authorEmail = $this->_askFor('Enter your email address', function ($answer) {
                return $this->data->newValidator()
                    ->addRequiredField('email');
            }, $primeUser['email'] ?? null);

            $authors[] = [
                'name' => $authorName,
                'email' => $authorEmail
            ];
        }

        $content = [
            'name' => $name,
            'description' => $description,
            'license' => 'MIT',
            'authors' => $authors,
            'repositories' => [
                [
                    'type' => 'vcs',
                    'url' => 'git@github.com:decodelabs/df-r7-integrator.git'
                ]
            ],
            'require' => [
                'decodelabs/df-r7-integrator' => 'dev-develop'
            ],
            'minimum-stability' => 'dev',
            'prefer-stable' => true
        ];

        $json = json_encode($content, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $file->putContents($json);
        $this->io->writeLine('composer.json created');


        if (!isset($this->request['no-update'])) {
            $this->runChild('composer/install');
        }
    }
}
