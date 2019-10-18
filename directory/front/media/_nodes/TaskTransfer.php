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

class TaskTransfer extends arch\node\Task
{
    public function execute()
    {
        $handlerList = neon\mediaHandler\Base::getEnabledHandlerList();
        $from = neon\mediaHandler\Base::getInstance();
        unset($handlerList[$from->getName()]);

        $to = ucfirst($this->request['to']);

        if ($to == $from->getName()) {
            $this->io->writeErrorLine('Target media handler \''.$to.'\' is already the default');
            return;
        }

        if (!isset($handlerList[$to])) {
            $this->io->writeErrorLine('Target media handler \''.$to.'\' is not enabled');
            return;
        }

        $to = neon\mediaHandler\Base::factory($to);
        $this->io->writeLine('Transferring media library from \''.$from->getDisplayName().'\' to \''.$to->getDisplayName().'\'');

        $tempDir = link\http\upload\Handler::createUploadTemp();
        $this->io->writeLine('Using temp dir: '.core\fs\Dir::stripPathLocation($tempDir));

        $httpClient = new link\http\Client();

        $files = $this->data->media->file->fetch()
            ->populate('versions')
            ->orderBy('creationDate');

        $this->io->writeLine();

        foreach ($files as $file) {
            $fileId = $file['id'];

            $this->io->writeLine('Processing file entry: #'.$fileId);

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

                    $this->io->writeLine('Fetching file: '.$url);

                    $response = $httpClient->getFile($url, $tempDir.'/'.$fileId, $versionId);

                    if (!$response->isOk()) {
                        $this->io->writeErrorLine('!! HTTP file transfer failed !!');
                        continue;
                    }

                    $deleteFile = true;
                    $filePath = $tempDir.'/'.$fileId.'/'.$versionId;
                }

                $this->io->writeLine('Publishing version: #'.$versionId.' - '.$version['fileName']);
                $to->transferFile($fileId, $versionId, $version['isActive'], $filePath, $version['fileName']);

                if ($deleteFile) {
                    core\fs\File::delete($filePath);
                }
            }

            core\fs\Dir::delete($tempDir.'/'.$fileId.'/');
            $this->io->writeLine();
        }

        $this->io->writeLine('Deleting temporary directory');
        core\fs\Dir::delete($tempDir);

        $this->io->writeLine('Updating config');
        $config = neon\mediaHandler\Config::getInstance();
        $config->setDefaultHandler($to->getName())
            ->save();


        if (isset($this->request['delete'])) {
            $this->io->writeLine();
            $this->io->writeLine('Deleting files from source storage \''.$from->getDisplayName().'\'');

            $files = $this->data->media->file->select('id')
                ->orderBy('creationDate');

            foreach ($files as $file) {
                $this->io->writeLine('Deleting file entry: #'.$file['id']);
                $from->deleteFile($file['id']);
            }

            $this->io->writeLine();
        }


        $this->io->writeLine('All done!');
    }
}
