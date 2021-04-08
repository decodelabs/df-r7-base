<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http\response;

use df;
use df\core;
use df\link;

use DecodeLabs\Atlas;
use DecodeLabs\Atlas\Mode;
use DecodeLabs\Typify;
use DecodeLabs\Exceptional;

class File extends Base implements link\http\IFileResponse
{
    protected $_file;

    public function __construct($file, $checkPath=true, link\http\IResponseHeaderCollection $headers=null)
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

    public function setFile($file, $checkPath=true)
    {
        $file = Atlas::$fs->file($file);

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
}
