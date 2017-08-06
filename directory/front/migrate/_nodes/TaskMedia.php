<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\migrate\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\link;
use df\spur;

class TaskMedia extends arch\node\Task {

    protected $_migrator;

    public function extractCliArguments(core\cli\ICommand $command) {
        $i = 0;

        foreach($command->getArguments() as $arg) {
            if($arg->isOption()) {
                continue;
            }

            switch(++$i) {
                case 1:
                    $this->request->query->url = (string)$arg;
                    break;

                case 2:
                    $this->request->query->bucket = (string)$arg;
                    break;
            }
        }
    }

    public function execute() {
        if(!$this->data->media->isLocalDataMediaHandler()) {
            $this->io->writeLine('You can currently only migrate to locally stored media libraries');
            return;
        }

        $handler = $this->data->media->getMediaHandler();
        $this->_migrator = new spur\migrate\Handler($this->_getUrl());

        $bucket = $this->_getBucket();
        $limit = $this->_getLimit();

        $query = $this->data->media->version->fetch()
            ->where('purgeDate', '=', null);

        if($bucket) {
            $query
                ->whereCorrelation('file', 'in', 'id')
                    ->from('axis://media/File')
                    ->whereCorrelation('bucket', 'in', 'id')
                        ->from('axis://media/Bucket')
                        ->where('slug', '=', $bucket)
                        ->endCorrelation()
                    ->endCorrelation();
        }

        if($limit) {
            $query
                ->where('fileSize', '<', $limit);
        }


        foreach($query as $version) {
            $fileId = (string)$version['#file'];
            $versionId = (string)$version['id'];

            $path = $handler->getFilePath($fileId, $versionId);

            if(is_file($path)) {
                $this->io->writeLine('Skipping '.$versionId.' - '.$version['fileName']);
                continue;
            }


            $this->_migrator->callAsync($this->_migrator->createRequest(
                'get', '~devtools/migrate/media', [
                    'file' => $fileId,
                    'version' => $versionId
                ], $path
            ), function($response) use($versionId, $version, $path) {
                if(!$response->isOk()) {
                    if($response->isMissing()) {
                        try {
                            $content = $response->getJsonContent();
                            $message = $content['message'];
                        } catch(\Throwable $e) {
                            $message = null;
                        }

                        $this->io->writeLine($versionId.' - '.$version['fileName'].' **NOT FOUND'.($message ? ': '.$message : null).'**');
                    } else if($response->isForbidden()) {
                        throw core\Error::{'EArgument,EApi'}(
                            'Migration key is invalid - check application pass keys match'
                        );
                    } else if($response->isError()) {
                        throw core\Error::EApi('Migration failed!!!');
                    }
                } else {
                    $this->io->writeLine('Fetched '.$versionId.' - '.$version['fileName'].' - '.$this->format->fileSize(filesize($path)));
                }
            });
        }

        $this->_migrator->sync();
    }

    protected function _getUrl() {
        if(isset($this->request['url'])) {
            $url = $this->request['url'];
        } else {
            $this->io->write('>> Please enter the source root URL: ');
            $url = $this->io->readLine();
        }

        $validator = $this->data->newValidator()
            ->addRequiredField('url')
            ->validate(['url' => $url]);

        if(!$validator->isValid()) {
            throw core\Error::{'EArgument'}([
                'message' => 'Sorry, the URL you entered is invalid',
            ]);
        }

        return new link\http\Url($validator['url']);
    }

    protected function _getBucket(): ?string {
        if(isset($this->request['bucket'])) {
            $bucket = $this->request['bucket'];
        } else {
            $bucket = $this->_askFor('Bucket [default: all]', function($answer) {
                return $this->data->newValidator()
                    ->addField('bucket', 'slug')
                        ->setCustomValidator(function($node, $value) {
                            if(empty($value)) {
                                return;
                            }

                            $check = $this->data->media->bucket->select('slug')
                                ->where('slug', '=', $value)
                                ->count();

                            if(!$check) {
                                $node->addError('invalid', 'Bucket not found');
                            }
                        });
            });
        }

        return $bucket;
    }

    protected function _getLimit(): ?int {
        if(isset($this->request['limit'])) {
            $limit = $this->request['limit'];
        } else {
            $limit = $this->_askFor('Size limit [default: none]', function($answer) {
                return $this->data->newValidator()
                    ->addField('limit', 'text')
                        ->setSanitizer(function($value) {
                            if(!$value) {
                                return null;
                            }

                            return core\unit\FileSize::factory($value)->getBytes();
                        })

                        ->setCustomValidator(function($node, $value) {
                            if(!$value && $value !== null) {
                                $node->addError('invalid', 'Invalid file size');
                            }
                        });
            });
        }

        return $limit;
    }
}
