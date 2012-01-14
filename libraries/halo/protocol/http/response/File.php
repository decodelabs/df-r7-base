<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\protocol\http\response;

use df;
use df\core;
use df\halo;

class File extends Base implements halo\protocol\http\IFileResponse {
    
    protected $_file;
    
    public function __construct($file, $checkPath=true) {
        $this->setFile($file, $checkPath);
        
        /*
        $this->_headers
            ->setCacheAccess('public')
            ->setCacheExpiration(core\time\Duration::fromDays(1))
            //->shouldRevalidateCache(true)
            ;
            */
    }
    
    public function setFile($file, $checkPath=true) {
        if(!$file instanceof core\io\file\IPointer) {
            $file = new core\io\file\LocalPointer($file);
        }
        
        if($checkPath && !$file->exists()) {
            throw new halo\protocol\http\RuntimeException('Static file could not be found', 404);
        }
        
        $this->_file = $file;
        $this->getHeaders()
            ->set('content-type', $this->_file->getContentType())
            //->set('content-length', $this->_file->getSize())
            ->set('last-modified', core\time\Date::factory($this->_file->getLastModified()));
        
        return $this;
    }
    
    public function isStaticFile() {
        return $this->_file instanceof core\io\file\IFileSystemPointer
            && $this->_file->isOnDisk();
    }
    
    public function getStaticFilePath() {
        if($this->isStaticFile()) {
            return $this->_file->getPath();
        }
        
        return null;
    }
    
    public function getContent() {
        return $this->_file->getContents();
    }
    
    public function getContentFileStream() {
        return $this->_file->open(core\io\file\READ_ONLY);
    }
}