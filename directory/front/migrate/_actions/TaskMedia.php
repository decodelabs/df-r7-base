<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\migrate\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
use df\link;
use df\spur;

class TaskMedia extends arch\task\Action {

    protected $_migrator;

    public function extractCliArguments(core\cli\ICommand $command) {
        foreach($command->getArguments() as $arg) {
            if(!$arg->isOption()) {
                $this->request->query->url = (string)$arg;
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

        $query = $this->data->media->version->fetch()
            ->where('purgeDate', '=', null);

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
                        } catch(\Exception $e) {
                            $message = null;
                        }

                        $this->io->writeLine($versionId.' - '.$version['fileName'].' **NOT FOUND'.($message ? ': '.$message : null).'**');
                    } else if($response->isForbidden()) {
                        $this->throwError(500, 'Migration key is invalid - check application pass keys match');
                    } else if($response->isError()) {
                        $this->throwError(500, 'Migration failed!!!');
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
            $this->throwError(403, 'Sorry, the URL you entered is invalid');
            return;
        }

        return new link\http\Url($validator['url']);
    }
}