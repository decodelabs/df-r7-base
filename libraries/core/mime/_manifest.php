<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\mime;

use df;
use df\core;
    

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
interface IMessageType {
	const OCTETSTREAM = 'application/octet-stream';
    const TEXT = 'text/plain';
    const HTML = 'text/html';
}

interface IMessageDisposition {
	const ATTACHMENT = 'attachment';
	const INLINE = 'inline';
}

interface IMessageEncoding {
	const E_7BIT = core\string\IEncoding::E_7BIT;
    const E_8BIT = core\string\IEncoding::E_8BIT;
    const QP = core\string\IEncoding::QP;
    const BASE64 = core\string\IEncoding::BASE64;
    const BINARY = core\string\IEncoding::BINARY;
}

interface IMessageLine {
	const LENGTH = 74;
	const END = PHP_EOL;
}


interface IPart extends core\IStringProvider {
	public function getHeaders();
	public function setHeaders(core\collection\HeaderCollection $headers);

	public function isMultiPart();
	public function isMessage();

	public function setContentType($type);
	public function getContentType();
	public function getFullContentType();

	public function getBodyString();
}

interface IContentPart extends IPart {

	public function setEncoding($encoding);
	public function getEncoding();
	public function setCharacterSet($charset);
	public function getCharacterSet();

	public function setId($id);
	public function getId();
	public function setDisposition($disposition);
	public function getDisposition();
	public function getFullDisposition();

	public function setFileName($fileName, $disposition=null);
	public function getFileName();
	public function setDescription($description);
	public function getDescription();

	public function setContent($content);
	public function getContent();
	public function getEncodedContent();
}


interface IMultiPart extends IPart, \Countable, \RecursiveIterator {

	const ALTERNATIVE = 'multipart/alternative';
    const MIXED = 'multipart/mixed';
    const RELATED = 'multipart/related';
    const PARALLEL = 'multipart/parallel';
    const DIGEST = 'multipart/digest';

    public function setBoundary($boundary);
    public function getBoundary();

    public function setParts(array $parts);
    public function addParts(array $parts);
    public function addPart(IPart $part);
    public function getParts();
    public function clearParts();
    public function isEmpty();

    public function newContentPart($content);
    public function newMultiPart($type=IMultiPart::MIXED);
    public function newMessage($type=IMultiPart::MIXED);
}
