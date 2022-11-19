<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\github;

use df\core;

class File implements IFile
{
    protected $_filename;
    protected $_type;
    protected $_language;
    protected $_url;
    protected $_size;

    public function __construct(core\collection\ITree $data)
    {
        $this->_filename = $data['filename'];
        $this->_type = $data['type'];
        $this->_language = $data['language'];
        $this->_url = $data['raw_url'];
        $this->_size = $data['size'];
    }

    public function getFilename()
    {
        return $this->_filename;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function getLanguage()
    {
        return $this->_language;
    }

    public function getUrl()
    {
        return $this->_url;
    }

    public function getSize()
    {
        return $this->_size;
    }
}
