<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\media\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\neon;
use df\link;

use DecodeLabs\Terminus\Cli;
use DecodeLabs\Glitch;
use DecodeLabs\Atlas;

class TaskTransfer extends arch\node\Task
{
    public function execute()
    {
        $handlerList = neon\mediaHandler\Base::getEnabledHandlerList();
        $from = neon\mediaHandler\Base::getInstance();
        unset($handlerList[$from->getName()]);

        $to = ucfirst($this->request['to']);

        if ($to == $from->getName()) {
            Cli::info('Target media handler \''.$to.'\' is already the default');
            return;
        }

        if (!isset($handlerList[$to])) {
            Cli::error('Target media handler \''.$to.'\' is not enabled');
            return;
        }

        $to = neon\mediaHandler\Base::factory($to);
        Cli::info('Transferring media library from \''.$from->getDisplayName().'\' to \''.$to->getDisplayName().'\'');

        $tempDir = link\http\upload\Handler::createUploadTemp();
        Cli::info('Using temp dir: '.Glitch::normalizePath($tempDir));

        $httpClient = new link\http\Client();

        $files = $this->data->media->file->fetch()
            ->populate('versions')
            ->orderBy('creationDate');

        Cli::newLine();

        foreach ($files as $file) {
            $fileId = $file['id'];

            Cli::{'.yellow'}('Processing file entry: #'.$fileId);

            foreach ($file['versions'] as $version) {
                if ($version['purgeDate']) {
                    continue;
                }

                $versionId = $version['id'];

                if ($from instanceof neon\mediaHandler\ILocalDataHandler) {
                    $filePath = $from->getFilePath($fileId, $versionId);
                    $deleteFile = false;
                } else {
                    $url = $this->uri->__invoke($from->getVersionDownloadUrl(
                        $fileId, $versionId, $version['isActive']
                    ));

                    Cli::{'.yellow'}('Fetching file: '.$url);

                    $response = $httpClient->getFile($url, $tempDir.'/'.$fileId, $versionId);

                    if (!$response->isOk()) {
                        Cli::error('!! HTTP file transfer failed !!');
                        continue;
                    }

                    $deleteFile = true;
                    $filePath = $tempDir.'/'.$fileId.'/'.$versionId;
                }

                Cli::{'yellow'}('Publishing version: #'.$versionId.' - '.$version['fileName'].' ');
                $to->transferFile($fileId, $versionId, $version['isActive'], $filePath, $version['fileName']);

                if ($deleteFile) {
                    Atlas::$fs->deleteFile($filePath);
                }

                Cli::success('done');
            }

            Atlas::$fs->deleteDir($tempDir.'/'.$fileId.'/');
            Cli::newLine();
        }

        Cli::{'yellow'}('Deleting temporary directory: ');
        Atlas::$fs->deleteDir($tempDir);
        Cli::success('done');


        Cli::{'yellow'}('Updating config: ');
        $config = neon\mediaHandler\Config::getInstance();
        $config->setDefaultHandler($to->getName())
            ->save();
        Cli::success('done');


        if (isset($this->request['delete'])) {
            Cli::newLine();

            $files = $this->data->media->file->select('id')
                ->orderBy('creationDate');

            foreach ($files as $file) {
                Cli::{'yellow'}('Deleting file entry: #'.$file['id'].' ');
                $from->deleteFile($file['id']);
                Cli::success('done');
            }
        }
    }
}
