<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mime;

use df;
use df\core;
use df\flow;
use df\flex;

class ContentPart implements IContentPart, core\IDumpable {

    use core\TStringProvider;
    use core\collection\THeaderMapProvider;

    protected $_content = null;

    public function __construct($content, $headers=null, $decodeContent=false) {
        $this->_headers = core\collection\HeaderMap::factory($headers);

        if($headers === null || !$this->_headers->has('content-type')) {
            $this->_headers->set('content-type', 'text/plain; charset="utf-8"');
        }

        if($headers === null || !$this->_headers->has('content-transfer-encoding')) {
            $this->_headers->set('content-transfer-encoding', flex\IEncoding::A7BIT);
        }

        if($decodeContent) {
            $encoding = $this->_headers->get('content-transfer-encoding');

            switch(strtolower($encoding)) {
                case strtolower(flex\IEncoding::A7BIT):
                case strtolower(flex\IEncoding::A8BIT):
                    $content = $this->_unchunk($content, IPart::LINE_LENGTH, IPart::LINE_END);
                    break;

                case strtolower(flex\IEncoding::QP):
                    $content = quoted_printable_decode($content);
                    break;

                case strtolower(flex\IEncoding::BASE64):
                    $content = base64_decode($this->_unchunk($content, IPart::LINE_LENGTH, IPart::LINE_END));
                    break;

                case strtolower(flex\IEncoding::BINARY):
                    break;

                default:
                    throw new InvalidArgumentException(
                        'Invalid encoding type: '.$encoding
                    );
            }
        }

        $this->setContent($content);
    }

    protected function _unchunk($content, $length, $end) {
        $strlen = strlen($content) + $length - 1;
        $endlen = strlen($end);
        $output = '';

        for($i = 0; $i < $strlen; $i += $length) {
            if($i) {
                $output .= substr($content, $i - $length, $length);
                $i += $endlen;
            }
        }

        return $output;
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
            case flex\IEncoding::A8BIT:
            case flex\IEncoding::A7BIT:
            case flex\IEncoding::QP:
            case flex\IEncoding::BASE64:
            case flex\IEncoding::BINARY:
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
        $this->_headers->setDelimitedValue('content-type', 'charset', $charset);
        return $this;
    }

    public function getCharacterSet() {
        return $this->_headers->getDelimitedValue('content-type', 'charset', 'utf-8');
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
        return $this->_headers->getDelimitedValue('content-disposition', 'filename');
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

        $this->_content = $content;
        return $this;
    }

    public function getContent() {
        return $this->_content;
    }

    public function getContentString() {
        if($this->_content instanceof core\fs\IFile) {
            return $this->_content->getContents();
        }

        return (string)$this->_content;
    }

    public function getEncodedContent() {
        $content = $this->getContentString();

        switch(strtolower($this->getEncoding())) {
            case strtolower(flex\IEncoding::A8BIT):
            case strtolower(flex\IEncoding::A7BIT):
                return wordwrap($content, IPart::LINE_LENGTH, IPart::LINE_END, 1);

            case strtolower(flex\IEncoding::QP):
                return quoted_printable_encode($content);

            case strtolower(flex\IEncoding::BASE64):
                return rtrim(chunk_split(base64_encode($content), IPart::LINE_LENGTH, IPart::LINE_END));

            case strtolower(flex\IEncoding::BINARY):
            default:
                return $content;
        }
    }


    public function toString() {
        $output = $this->getHeaderString().IPart::LINE_END.IPart::LINE_END;
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

    public function mergeSinglePartHeaders() {
        return $this->_headers->toArray();
    }

    public function getBodyString() {
        return $this->getEncodedContent();
    }

// Dump
    public function getDumpProperties() {
        $output = [];

        foreach($this->_headers as $key => $header) {
            $output[] = new core\debug\dumper\Property($key, $header, 'protected');
        }

        if($type = $this->_headers->getBase('content-type')) {
            $parts = explode('/', $type);

            if($parts[0] == 'text' || $parts[0] == 'application') {
                $output[] = new core\debug\dumper\Property('content', $this->_content);
            } else {
                $output[] = new core\debug\dumper\Property('content', strlen($this->_content).' bytes', 'private');
            }
        }

        return $output;
    }
}