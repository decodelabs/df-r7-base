<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\migrate\_nodes;

use DecodeLabs\Dictum;
use DecodeLabs\Exceptional;
use DecodeLabs\Hydro;
use DecodeLabs\Terminus as Cli;

use df\arch;
use df\flex;
use df\link;
use df\spur;

class TaskMedia extends arch\node\Task
{
    protected $_migrator;

    public function prepareArguments(): array
    {
        return Cli::$command
            ->addArgument('?url', 'URL of remote site')
            ->addArgument('?bucket', 'Bucket to transfer')
            ->toArray();
    }

    public function execute(): void
    {
        if (!$this->data->media->isLocalDataMediaHandler()) {
            Cli::error('You can currently only migrate to locally stored media libraries');
            return;
        }

        $handler = $this->data->media->getMediaHandler();
        $this->_migrator = new spur\migrate\Handler($this->_getUrl());

        $bucket = $this->_getBucket();
        $limit = $this->_getLimit();

        $query = $this->data->media->version->fetch()
            ->where('purgeDate', '=', null);

        if ($bucket) {
            $query
                ->whereCorrelation('file', 'in', 'id')
                    ->from('axis://media/File')
                    ->whereCorrelation('bucket', 'in', 'id')
                        ->from('axis://media/Bucket')
                        ->where('slug', '=', $bucket)
                        ->endCorrelation()
                    ->endCorrelation();
        }

        if ($limit) {
            $query
                ->where('fileSize', '<', $limit);
        }


        foreach ($query as $version) {
            $fileId = (string)$version['#file'];
            $versionId = (string)$version['id'];

            $path = $handler->getFilePath($fileId, $versionId);

            Cli::{'brightMagenta'}($versionId);
            Cli::{'brightYellow'}(' ' . $version['fileName'] . ' ');

            if (is_file($path)) {
                Cli::operative('skipped');
                continue;
            }

            Cli::operative('fetching...');
            $progressBar = Cli::newProgressBar(0, $version['fileSize'])
                ->setShowCompleted(false);

            $this->_migrator->callAsync($this->_migrator->createRequest(
                'get',
                '~devtools/migrate/media',
                [
                    'file' => $fileId,
                    'version' => $versionId
                ]
            ), function ($response) use ($versionId, $version, $path, &$progressBar) {
                $progressBar->complete();
                Cli::cursorLineUp();
                Cli::clearLine();
                Cli::cursorLineUp();
                Cli::clearLine();
                Cli::{'brightMagenta'}($versionId);
                Cli::{'brightYellow'}(' ' . $version['fileName'] . ' ');

                if ($response->getStatusCode() >= 300) {
                    if ($response->getStatusCode() === 404) {
                        try {
                            $content = flex\Json::stringToTree((string)$response->getBody());
                            $message = $content['message'];
                        } catch (\Throwable $e) {
                            $message = null;
                        }

                        Cli::error('NOT FOUND' . ($message ? ': ' . $message : null));
                    } elseif ($response->getStatusCode() === 403) {
                        throw Exceptional::{'InvalidArgument,Api'}(
                            'Migration key is invalid - check application pass keys match'
                        );
                    } elseif ($response->getStatusCode() >= 400) {
                        throw Exceptional::Api(
                            'Migration failed!!!'
                        );
                    }
                } else {
                    $file = Hydro::responseToFile($response, $path);
                    Cli::success(Dictum::$number->fileSize($file->getSize()));
                }
            }, function ($total, $downloaded) use ($progressBar) {
                $progressBar->advance($downloaded);
            })->wait();
        }
    }

    protected function _getUrl()
    {
        if (isset($this->request['url'])) {
            $url = $this->request['url'];
        } else {
            Cli::{'.cyan'}('Please enter the source root URL:');
            Cli::write('> ');
            $url = Cli::readLine();
        }

        $validator = $this->data->newValidator()
            ->addRequiredField('url')
            ->validate(['url' => $url]);

        if (!$validator->isValid()) {
            throw Exceptional::InvalidArgument([
                'message' => 'Sorry, the URL you entered is invalid',
            ]);
        }

        return new link\http\Url($validator['url']);
    }

    protected function _getBucket(): ?string
    {
        if (isset($this->request['bucket'])) {
            $bucket = $this->request['bucket'];
        } else {
            $bucket = $this->_askFor('Bucket', function (&$answer) {
                if ($answer === 'all') {
                    $answer = null;
                }

                return $this->data->newValidator()
                    ->addField('bucket', 'slug')
                        ->extend(function ($value, $field) {
                            if (empty($value)) {
                                return;
                            }

                            $check = $this->data->media->bucket->select('slug')
                                ->where('slug', '=', $value)
                                ->count();

                            if (!$check) {
                                $field->addError('invalid', 'Bucket not found');
                            }
                        });
            }, 'all');
        }

        return $bucket;
    }

    protected function _getLimit(): ?int
    {
        if (isset($this->request['limit'])) {
            $limit = $this->request['limit'];
        } else {
            $limit = $this->_askFor('Size limit', function (&$answer) {
                if ($answer === 'none') {
                    $answer = null;
                }

                return $this->data->newValidator()
                    ->addField('limit', 'fileSize');
            }, 'none');
        }

        if ($limit) {
            return $limit->getBytes();
        } else {
            return null;
        }
    }
}
