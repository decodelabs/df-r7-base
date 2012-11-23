<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\protocol\http\upload;

use df;
use df\core;
use df\halo;
    
class File implements halo\protocol\http\IUploadFile {

    protected $_fieldName;
    protected $_fileName;
    protected $_extension;
    protected $_type;
    protected $_tempPath;
    protected $_destinationPath;
    protected $_error;
    protected $_size;

    protected $_isProcessed = false;
    protected $_isSuccess = false;
    protected $_isValid = false;
    protected $_renameIndex = 0;

    protected $_handler;

    public function __construct(halo\protocol\http\IUploadHandler $handler, $fieldName, array $data) {
        $this->_handler = $handler;
        $this->_fieldName = $fieldName;

        $fileName = $data['name'];
        $parts = pathinfo($fileName);

        $this->_extension = isset($parts['extension']) ?
            $parts['extension'] : null;

        $this->_fileName = $this->_extension ?
            substr($fileName, 0, -strlen('.'.$this->_extension)) :
            $fileName;

        $this->_type = $data['type'];
        $this->_tempPath = $data['tmp_name'];
        $this->_error = $data['error'];
        $this->_size = core\unit\FileSize::factory($data['size']);

        $this->_isValid = $this->_error == 0;
    }

    public function setFileName($fileName) {
        $this->_fileName = $fileName;
        return $this;
    }

    public function getFileName() {
        return $this->_fileName;
    }

    public function setExtension($extension) {
        $this->_extension = $extension;
        return $this;
    }

    public function getExtension() {
        return $this->_extension;
    }

    public function setBaseName($baseName) {
        $parts = explode('.', $baseName);

        if(count($parts) > 1) {
            $this->setExtension(array_pop($parts));
        }

        return $this->setFileName(implode('.', $parts));
    }

    public function getBaseName() {
        $output = $this->_fileName;

        if($this->_extension !== null) {
            $output .= '.'.$this->_extension;
        }

        return $output;
    }


    public function getFieldName() {
        return $this->_fieldName;
    }

    public function getTempPath() {
        return $this->_tempPath;
    }

    public function getDestinationPath() {
        return $this->_destinationPath;
    }

    public function getSize() {
        return $this->_size;
    }

    public function getContentType() {
        return $this->_type;
    }
    

    public function isValid() {
        return $this->_isValid;
    }

    public function isSuccess() {
        return $this->_isSuccess;
    }

    public function getErrorCode() {
        return $this->_error;
    }

    public function getErrorString() {
        switch($this->getErrorCode()) {
            case 0: 
                return 'Upload successful';
                
            case 1:
            case 2: 
                return 'Filesize limit exceeded';
                
            case 3: 
                return 'File only partially uploaded';
                
            case 4: 
                return 'No file uploaded';
                
            case 5:
            case 6: 
                return 'No temporary folder';
                
            case 7: 
                return 'Failed to write file to disk';    
        }
    }

    public function upload($destination, core\collection\IInputTree $inputNode, $conflictAction=halo\protocol\http\IUploadFile::RENAME) {
        if($this->_isProcessed) {
            return $this;
        }

        $this->_isProcessed = true;
        $maxSize = $this->_handler->getMaxFileSize()->getMegabytes();

        if($maxSize > 0 && $this->_size->getMegabytes() > $maxSize) {
            $inputNode->addError('tooBig', $this->_(
                'The file exceeds the maximum upload file size'
            ));
        }

        if($this->_extension && !$this->_handler->isExtensionAllowed($this->_extension)) {
            $inputNode->addError('extensionNotAllowed', $this->_(
                'Files with the extension %e% are not allowed to be uploaded here',
                ['%e%' => $this->_extension]
            ));
        }

        if(!$this->_handler->isTypeAccepted($this->_type)) {
            $inputNode->addError('tpyeNotAccepted', $this->_(
                'File of type %t% are not allowed to be uploaded here',
                ['%t%' => $this->_type]
            ));
        }

        if(!$inputNode->isValid()) {
            return $this;
        }

        core\io\Util::ensureDirExists($destination);
        $fullPath = rtrim($destination, '/').'/'.$this->getBaseName();

        if(file_exists($fullPath)) {
            switch($conflictAction) {
                case halo\protocol\http\IUploadFile::HALT:
                    $inputNode->addError('conflict', $this->_(
                        'A file the name %n% already exists',
                        ['%n%' => $this->getBaseName()]
                    ));

                    return $this;

                case halo\protocol\http\IUploadFile::OVERWRITE:
                    try {
                        unlink($fullPath);
                        break;
                    } catch(\Exception $e) {}

                case halo\protocol\http\IUploadFile::RENAME:
                default:
                    $fullPath = $this->_autoRename($fullPath);
                    break;
            }
        }

        if(!is_uploaded_file($this->_tempPath)) {
            $inputNode->addError('uploadNotFound', $this->_(
                'There was a problem finding the uploaded file in the temp location - please try again'
            ));

            return $this;
        }

        if(!move_uploaded_file($this->_tempPath, $fullPath)) {
            $inputNode->addError('uploadTransfer', $this->_(
                'There was a problem transferring the uploaded file - please try again'
            ));

            return $this;
        }

        $this->_destinationPath = $fullPath;
        $this->_isSuccess = true;

        return $this;
    }

    protected function _autoRename($fullPath) {
        $this->_renameIndex = 1;
        $origName = $this->_fileName;
        $basePath = dirname($fullPath);

        while(file_exists($fullPath)) {
            $add = '('.$this->_renameIndex++.')';
            $this->_fileName = $origName.$add;
            $fullPath = $basePath.'/'.$this->getBaseName();
        }

        return $fullPath;
    }

    public function _($phrase, array $data=null, $plural=null, $locale=null) {
        $translator = core\i18n\translate\Handler::factory('halo/protocol/http/Upload', $locale);
        return $translator->_($phrase, $data, $plural);
    }
}