<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\csv;

use df;
use df\core;
use df\flex;

use DecodeLabs\Atlas\File;

interface IReader extends core\IArrayProvider, \Iterator
{
    public function getFile(): File;
    public function setDelimiter(string $delimiter): IReader;
    public function getDelimiter(): string;
    public function setEnclosure(string $enclosure): IReader;
    public function getEnclosure(): string;

    public function setFields(string ...$fields): IReader;
    public function extractFields(): IReader;
    public function getFields(): ?array;

    public function getRow(): ?array;
}

interface IBuilder extends core\io\IChunkSender
{
    public function setGenerator($generator=null);
    public function getGenerator();

    public function build();

    public function setFields(array $fields);
    public function getFields();
    public function shouldWriteFields(bool $flag=null);

    public function addRow(array $row);
    public function getRows();
}
