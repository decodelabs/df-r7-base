<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\link\http\upload;

use DecodeLabs\Atlas;
use DecodeLabs\Exceptional;

use DecodeLabs\Glitch;
use DecodeLabs\Typify;

use df\core;
use df\link;

use Socket\Raw\Factory as SocketFactory;
use Xenolope\Quahog\Client as Quahog;

class File implements link\http\IUploadFile
{
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

    public function __construct(link\http\IUploadHandler $handler, $fieldName, array $data)
    {
        $this->_handler = $handler;
        $this->_fieldName = $fieldName;

        $fileName = $data['name'];
        $parts = pathinfo($fileName);

        $this->_extension = $parts['extension'] ?? null;

        $this->_fileName = $this->_extension ?
            substr($fileName, 0, -strlen('.' . $this->_extension)) :
            $fileName;

        $this->_type = $data['type'];
        $parts = explode('/', $this->_type, 2);
        $top = array_pop($parts);

        if ($this->_extension !== null && ($top == 'octet-stream' || empty($top))) {
            $this->_type = Typify::detect($this->_extension);
        }

        $this->_tempPath = $data['tmp_name'];
        $this->_error = $data['error'];
        $this->_size = core\unit\FileSize::factory($data['size']);

        $this->_isValid = $this->_error == 0;
    }

    public function setFileName($fileName)
    {
        $this->_fileName = $fileName;
        return $this;
    }

    public function getFileName()
    {
        return $this->_fileName;
    }

    public function setExtension($extension)
    {
        $this->_extension = $extension;
        return $this;
    }

    public function getExtension()
    {
        return $this->_extension;
    }

    public function setBaseName($baseName)
    {
        $parts = explode('.', $baseName);

        if (count($parts) > 1) {
            $this->setExtension(array_pop($parts));
        }

        return $this->setFileName(implode('.', $parts));
    }

    public function getBaseName()
    {
        $output = $this->_fileName;

        if ($this->_extension !== null) {
            $output .= '.' . $this->_extension;
        }

        return $output;
    }


    public function getFieldName()
    {
        return $this->_fieldName;
    }

    public function getTempPath()
    {
        return $this->_tempPath;
    }

    public function getDestinationPath()
    {
        return $this->_destinationPath;
    }

    public function getSize()
    {
        return $this->_size;
    }

    public function getContentType()
    {
        return $this->_type;
    }

    public function getPointer()
    {
        if (!$this->_isSuccess) {
            throw Exceptional::Runtime(
                'No valid file path has been determined yet'
            );
        }

        return Atlas::file($this->_destinationPath);
    }


    public function isValid(): bool
    {
        return $this->_isValid;
    }

    public function isSuccess()
    {
        return $this->_isSuccess;
    }

    public function getErrorCode()
    {
        return $this->_error;
    }

    public function getErrorString()
    {
        switch ($this->getErrorCode()) {
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

    public function upload($destination, core\collection\IInputTree $inputNode, $conflictAction = link\http\IUploadFile::RENAME)
    {
        if ($this->_isProcessed) {
            return $this;
        }

        $this->_isProcessed = true;
        $this->_validateFile($inputNode);

        if (!$inputNode->isValid()) {
            return $this;
        }

        $destination = Atlas::createDir((string)$destination)->getPath();
        $fullPath = rtrim((string)$destination, '/') . '/' . $this->getBaseName();
        $i18n = core\i18n\Manager::getInstance();

        if (file_exists($fullPath)) {
            switch ($conflictAction) {
                case link\http\IUploadFile::HALT:
                    $inputNode->addError('conflict', $i18n->_(
                        'A file the name %n% already exists',
                        ['%n%' => $this->getBaseName()]
                    ));

                    return $this;

                case link\http\IUploadFile::OVERWRITE:
                    try {
                        unlink($fullPath);
                        break;
                    } catch (\Throwable $e) {
                    }

                case link\http\IUploadFile::RENAME:
                default:
                    $fullPath = $this->_autoRename($fullPath);
                    break;
            }
        }

        if (!move_uploaded_file($this->_tempPath, $fullPath)) {
            $inputNode->addError('uploadTransfer', $i18n->_(
                'There was a problem transferring the uploaded file - please try again'
            ));

            return $this;
        }

        $this->_destinationPath = $fullPath;
        $this->_isSuccess = true;

        return $this;
    }

    public function tempUpload(core\collection\IInputTree $inputNode)
    {
        if ($this->_isProcessed) {
            return $this;
        }

        $this->_isProcessed = true;
        $this->_validateFile($inputNode);

        if (!$inputNode->isValid()) {
            return $this;
        }


        $this->_destinationPath = $this->_tempPath;
        $this->_isSuccess = true;

        return $this;
    }

    protected function _validateFile(core\collection\IInputTree $inputNode)
    {
        $i18n = core\i18n\Manager::getInstance();
        $maxSize = $this->_handler->getMaxFileSize()->getMegabytes();

        if ($maxSize > 0 && $this->_size->getMegabytes() > $maxSize) {
            $inputNode->addError('tooBig', $i18n->_(
                'The file exceeds the maximum upload file size of %m%',
                ['%m%' => $maxSize . ' mb']
            ));
        }

        if ($this->_extension && !$this->_handler->isExtensionAllowed($this->_extension)) {
            $inputNode->addError('extensionNotAllowed', $i18n->_(
                'Files with the extension %e% are not allowed to be uploaded here',
                ['%e%' => $this->_extension]
            ));
        }

        if (!$this->_handler->isTypeAccepted($this->_type)) {
            $inputNode->addError('tpyeNotAccepted', $i18n->_(
                'Files of type %t% are not allowed to be uploaded here',
                ['%t%' => $this->_type]
            ));
        }

        if (!is_uploaded_file($this->_tempPath)) {
            $inputNode->addError('uploadNotFound', $i18n->_(
                'There was a problem finding the uploaded file in the temp location - please try again (' . $this->getErrorCode() . ': ' . $this->getErrorString() . ')'
            ));
            return;
        }

        if ($this->_handler->shouldAvScan() && class_exists(Quahog::class)) {
            try {
                $socket = (new SocketFactory())->createClient($this->_handler->getClamAvSocket());
                $quahog = new Quahog($socket, 30, \PHP_NORMAL_READ);
                chmod($this->_tempPath, 0644);
                $result = $quahog->scanFile($this->_tempPath);

                if ($result->isFound()) {
                    $inputNode->addError('virus', $i18n->_(
                        'The uploaded file did not pass AV scan'
                    ));

                    unlink($this->_tempPath);
                } elseif ($result->isError()) {
                    Glitch::logException(
                        Exceptional::Scan($result['reason'])
                    );
                }
            } catch (\Throwable $e) {
                Glitch::logException($e);
            }
        }
    }

    protected function _autoRename($fullPath)
    {
        $this->_renameIndex = 1;
        $origName = $this->_fileName;
        $basePath = dirname($fullPath);

        while (file_exists($fullPath)) {
            $add = '(' . $this->_renameIndex++ . ')';
            $this->_fileName = $origName . $add;
            $fullPath = $basePath . '/' . $this->getBaseName();
        }

        return $fullPath;
    }
}
