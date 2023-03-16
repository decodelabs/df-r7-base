<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flex\csv;

use DecodeLabs\Atlas\File;

use DecodeLabs\Deliverance\DataSender;
use df\core;

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

interface IBuilder extends DataSender
{
    public function setGenerator(?callable $generator): IBuilder;
    public function getGenerator(): ?callable;

    public function build();

    public function setFields(array $fields): IBuilder;
    public function getFields(): ?array;
    public function shouldWriteFields(bool $flag = null);

    public function addInfoRow(array $row): void;
    public function addRow(array $row): void;
    public function getRows(): array;
}
