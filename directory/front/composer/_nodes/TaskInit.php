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

use DecodeLabs\Atlas;
use DecodeLabs\Genesis;
use DecodeLabs\Terminus as Cli;

class TaskInit extends arch\node\Task
{
    public function execute(): void
    {
        $this->ensureDfSource();

        $file = Atlas::file(Genesis::$hub->getApplicationPath().'/composer.json');

        if ($file->exists()) {
            if (!isset($this->request['no-update'])) {
                $this->runChild('composer/install');
            }

            return;
        }


        // Name
        $parts = explode('/', Genesis::$hub->getApplicationPath());
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
        }, Genesis::$hub->getApplicationName());


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
                    'url' => 'git@github.com:decodelabs/df-r7-base.git'
                ],
                [
                    'type' => 'vcs',
                    'url' => 'git@github.com:decodelabs/df-r7-webCore.git'
                ]
            ],
            'require' => [
                'df-r7/base' => 'dev-master',
                'df-r7/webcore' => 'dev-master'
            ],
            'minimum-stability' => 'dev',
            'prefer-stable' => true
        ];

        $json = json_encode($content, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $file->putContents($json);
        Cli::info('composer.json created');


        if (!isset($this->request['no-update'])) {
            $this->runChild('composer/install');
        }
    }
}
