<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\mime;

use df;
use df\core;
    
class ContentPart implements IContentPart, core\IDumpable {

	use core\TStringProvider;
	use core\collection\THeaderMapProvider;

    protected $_content = null;

    public function __construct($content, $headers=null) {
    	$this->_headers = core\collection\HeaderMap::factory($headers);

    	if($headers === null || !$this->_headers->has('content-type')) {
    		$this->_headers->set('content-type', IMessageType::TEXT.'; charset="utf-8"');
    	}

    	if($headers === null || !$this->_headers->has('content-transfer-encoding')) {
    		$this->_headers->set('content-transfer-encoding', IMessageEncoding::E_7BIT);
    	}

    	$this->setContent($content);
    }

	public function isMultiPart() {
		return false;
	}

    public function setContentType($type) {
    	if(strtolower(substr($type, 0, 10)) == 'multipart/') {
    		throw new InvalidArgumentException(
    			'Please use newMultiPart() for multipart types'
			);
    	}

    	if(preg_match('/boundary=".*"/i', $type)) {
    		throw new InvalidArgumentException(
				'Please use newMultiPart() for multipart types, invalid boundary definition detected'
			);
    	}

    	if(substr($type, 0, 5) == 'text/' && !preg_match('/charset=".*"/i', $type)) {
    		$type .= '; charset="utf-8"';
    	}

    	$this->_headers->set('content-type', $type);
    	return $this;
    }

	public function getContentType() {
		return trim(explode(';', $this->_headers->get('content-type'))[0]);
	}

	public function getFullContentType() {
		return $this->_headers->get('content-type');
	}

	public function setEncoding($encoding) {
		switch($encoding) {
			case IMessageEncoding::E_8BIT:
            case IMessageEncoding::E_7BIT:
            case IMessageEncoding::QP:
            case IMessageEncoding::BASE64:
            case IMessageEncoding::BINARY:
                break;
                
            default: 
                throw new InvalidArgumentException(
                	'Invalid encoding type: '.$encoding
            	);
        }

		$this->_headers->set('content-transfer-encoding', $encoding);
		return $this;
	}

	public function getEncoding() {
		return $this->_headers->get('content-transfer-encoding');
	}

	public function setCharacterSet($charset) {
		$this->_headers->setNamedValue('content-type', 'charset', $charset);
		return $this;
	}

	public function getCharacterSet() {
		return $this->_headers->getNamedValue('content-type', 'charset', 'utf-8');
	}


	public function setId($id) {
		$this->_headers->set('content-id', '<'.$id.'>');
		return $this;
	}

	public function getId() {
		return substr($this->_headers->get('content-id'), 1, -1);
	}

	public function setDisposition($disposition) {
		$this->_headers->set('content-disposition', $disposition);
		return $this;
	}

	public function getDisposition() {
		return trim(explode(';', $this->getFullDisposition())[0]);
	}

	public function getFullDisposition() {
		return $this->_headers->get('content-disposition', 'inline');
	}

	public function setFileName($fileName, $disposition=null) {
		if($disposition === null) {
			$disposition = $this->getDisposition();
		}

		$this->setDisposition($disposition.'; filename="'.$fileName.'"');
		return $this;
	}

	public function getFileName() {
		return $this->_headers->getNamedValue('content-disposition', 'filename');
	}

	public function setDescription($description) {
		$this->_headers->set('content-description', $description);
		return $this;
	}

	public function getDescription() {
		return $this->_headers->get('content-description');
	}
	

	public function setContent($content) {
		if(is_resource($content)) {
			throw new RuntimeException(
				'Resource streams are not currently supported in mime messages'
			);	
		}

		$this->_content = (string)$content;
		return $this;
	}

	public function getContent() {
		return $this->_content;
	}

	public function getEncodedContent() {
		switch($this->getEncoding()) {
			case IMessageEncoding::E_8BIT:
            case IMessageEncoding::E_7BIT:
            	return wordwrap($this->_content, IMessageLine::LENGTH, IMessageLine::END, 1);

            case IMessageEncoding::QP:
            	return quoted_printable_encode($this->_content);

            case IMessageEncoding::BASE64:
            	return rtrim(chunk_split(base64_encode($this->_content), IMessageLine::LENGTH, IMessageLine::END));

            case IMessageEncoding::BINARY:
            default:
            	return $this->_content;
		}
	}


	public function toString() {
		$output = $this->getHeaderString().IMessageLine::END.IMessageLine::END;
		$output .= $this->getEncodedContent();

		return $output;
	}

	public function getHeaderString(array $skipKeys=null) {
        $this->prepareHeaders();

        if(!$this->_headers) {
        	return '';
        }

        $output = $this->_headers->toString($skipKeys);
        $output = preg_replace('/\; ([a-z]+)\=/i', ";\r\n    ".'$1=', $output);
        return $output;
    }

	public function getBodyString() {
		return $this->getEncodedContent();
	}

// Dump
	public function getDumpProperties() {
		$output = array();

		foreach($this->_headers as $key => $header) {
			$output[] = new core\debug\dumper\Property($key, $header, 'protected');
		}

		$output[] = new core\debug\dumper\Property('content', $this->_content);

		return $output;
	}
}