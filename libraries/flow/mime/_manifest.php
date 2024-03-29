<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flow\mime;

use df\core;

interface IPart extends core\IStringProvider, core\collection\IHeaderMapProvider
{
    public const LINE_LENGTH = 74;
    public const LINE_END = "\r\n";

    public function isMultiPart();

    public function setContentType($type);
    public function getContentType();
    public function getFullContentType();

    public function getBodyString();
}

interface IContentPart extends IPart
{
    public function setEncoding($encoding);
    public function getEncoding();
    public function setCharacterSet($charset);
    public function getCharacterSet();

    public function setId(string $id);
    public function getId(): string;
    public function setDisposition($disposition);
    public function getDisposition();
    public function getFullDisposition();

    public function setFileName($fileName, $disposition = null);
    public function getFileName();
    public function setDescription($description);
    public function getDescription();

    public function setContent($content);
    public function getContent();
    public function getContentString();
    public function getEncodedContent();
}


interface IMultiPart extends IPart, \Countable, \Iterator
{
    public const ALTERNATIVE = 'multipart/alternative';
    public const MIXED = 'multipart/mixed';
    public const RELATED = 'multipart/related';
    public const PARALLEL = 'multipart/parallel';
    public const DIGEST = 'multipart/digest';

    public function setBoundary($boundary);
    public function getBoundary();

    public function setParts(array $parts);
    public function addParts(array $parts);
    public function addPart(IPart $part);
    public function prependPart(IPart $part);
    public function getParts();
    public function getPart($index);
    public function clearParts();
    public function isEmpty(): bool;

    public function newContentPart($content);
    public function newMultiPart($type = IMultiPart::MIXED);
}
