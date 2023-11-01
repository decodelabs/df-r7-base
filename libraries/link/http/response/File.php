<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\link\http\response;

use DecodeLabs\Atlas;
use DecodeLabs\Atlas\Mode;
use DecodeLabs\Exceptional;
use DecodeLabs\Harvest;
use DecodeLabs\Typify;
use df\core;
use df\link;
use Psr\Http\Message\ResponseInterface as PsrResponse;

class File extends Base implements link\http\IFileResponse
{
    protected $_file;

    public function __construct($file, $checkPath = true, link\http\IResponseHeaderCollection $headers = null)
    {
        parent::__construct($headers);
        $this->setFile($file, $checkPath);

        /*
        $this->headers
            ->setCacheAccess('public')
            ->setCacheExpiration(core\time\Duration::fromDays(1))
            //->shouldRevalidateCache(true)
            ;
         */
    }

    public function setFile($file, $checkPath = true)
    {
        $file = Atlas::file($file);

        if ($checkPath && !$file->exists()) {
            throw Exceptional::NotFound(
                'Static file could not be found'
            );
        }

        $this->_file = $file;

        if ($file->isOnDisk()) {
            if (!$this->headers->has('content-disposition')) {
                $this->headers->setFileName($this->_file->getName());
            }

            $this->headers
                ->set('content-type', Typify::detect($this->_file->getPath()))
                //->set('content-length', $this->_file->getSize())
                ->set('last-modified', core\time\Date::factory($this->_file->getLastModified()));
        }

        return $this;
    }

    public function isStaticFile()
    {
        return $this->_file->isOnDisk();
    }

    public function getStaticFilePath()
    {
        if ($this->isStaticFile()) {
            return $this->_file->getPath();
        }

        return null;
    }

    public function getContent()
    {
        return $this->_file->getContents();
    }

    public function getContentFileStream()
    {
        return $this->_file->open(Mode::READ_ONLY);
    }


    public function toPsrResponse(): PsrResponse
    {
        if ($this->hasCookies()) {
            $this->getCookies()->applyTo($this->headers);
        }

        return Harvest::stream(
            $this->_file->open('r'),
            $this->headers->getStatusCode(),
            $this->headers->toArray()
        );
    }
}
