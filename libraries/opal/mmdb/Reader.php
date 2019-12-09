<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\mmdb;

use df;
use df\core;
use df\opal;
use df\link;

use DecodeLabs\Glitch;
use DecodeLabs\Atlas;
use DecodeLabs\Atlas\Mode;
use DecodeLabs\Atlas\File;

class Reader implements IReader
{
    const DATA_SECTION_SEPARATOR_SIZE = 16;
    const METADATA_START_MARKER = "\xAB\xCD\xEFMaxMind.com";

    protected $_file;
    protected $_fileSize;
    protected $_metaData = [];
    protected $_decoder;
    protected $_ipv4Start = 0;

    public function __construct($file)
    {
        if (is_string($file)) {
            $file = Atlas::$fs->file($file, Mode::READ_ONLY);
        } elseif (!$file instanceof File) {
            throw Glitch::EInvalidArgument(
                'MMDB file could not be found'
            );
        }

        $this->_file = $file;
        $this->_fileSize = $file->getSize();

        $metaStart = $this->_findMetadataStart();
        $metaDecoder = new Decoder($this->_file, $metaStart);
        $this->_metaData = $metaDecoder->decode()[0];
        $this->_metaData['node_byte_size'] = $this->_metaData['record_size'] / 4;
        $this->_metaData['search_tree_size'] = $this->_metaData['node_count'] * $this->_metaData['node_byte_size'];

        $this->_decoder = new Decoder(
            $this->_file,
            $this->_metaData['search_tree_size'] + self::DATA_SECTION_SEPARATOR_SIZE
        );
    }

    private function _findMetadataStart()
    {
        $marker = self::METADATA_START_MARKER;
        $markerLength = strlen($marker);
        $read = '';

        for ($i = 0; $i < $this->_fileSize - $markerLength + 1; $i++) {
            for ($j = 0; $j < $markerLength; $j++) {
                $this->_file->setPosition($this->_fileSize - $i - $j - 1);
                $matchBit = $this->_file->readChar();
                $read = $matchBit.$read;

                if ($matchBit != $marker[$markerLength - $j - 1]) {
                    continue 2;
                }
            }

            return $this->_fileSize - $i;
        }

        throw Glitch::EUnexpectedValue(
            'Unable to read metadata start index'
        );
    }


    public function get($ip)
    {
        $ip = link\Ip::factory($ip);

        if ($this->_metaData['ip_version'] == 4 && !$ip->isV4()) {
            throw Glitch::EInvalidArgument(
                'Attempting to lookup IPv6 address in IPv4-only database'
            );
        }

        $pointer = $this->_findAddressInTree($ip);

        if ($pointer === 0) {
            return null;
        }

        return $this->_resolveDataPointer($pointer);
    }

    protected function _findAddressInTree(link\Ip $ip)
    {
        if (false === ($packed = inet_pton($ip->toString()))) {
            throw Glitch::ERuntime('Unable to pack IP string', null, $ip);
        }

        $rawAddress = array_merge(unpack('C*', $packed));
        $bitCount = count($rawAddress) * 8;

        $node = $this->_startNode($bitCount);

        for ($i = 0; $i < $bitCount; $i++) {
            if ($node >= $this->_metaData['node_count']) {
                break;
            }

            $tempBit = 0xFF & $rawAddress[$i >> 3];
            $bit = 1 & ($tempBit >> 7 - ($i % 8));
            $node = $this->_readNode($node, $bit);
        }

        if ($node == $this->_metaData['node_count']) {
            return 0;
        } elseif ($node > $this->_metaData['node_count']) {
            return $node;
        }

        throw Glitch::EUnexpectedValue(
            'Something bad happened looking up MMDB node'
        );
    }

    protected function _startNode($length)
    {
        if ($this->_metaData['ip_version'] == 6 && $length == 32) {
            if ($this->_ipv4Start != 0) {
                return $this->_ipv4Start;
            }

            $node = 0;

            for ($i = 0; $i < 96 && $node < $this->_metaData['node_count']; $i++) {
                $node = $this->_readNode($node, 0);
            }

            $this->_ipv4Start = $node;
            return $node;
        }

        return 0;
    }

    protected function _readNode($nodeNumber, $index)
    {
        $baseOffset = $nodeNumber * $this->_metaData['node_byte_size'];

        switch ($this->_metaData['record_size']) {
            case 24:
                $bytes = $this->_file->readFrom($baseOffset + $index * 3, 3);
                list(, $node) = unpack('N', "\x00".$bytes);
                return $node;

            case 28:
                $middleByte = $this->_file->readFrom($baseOffset + 3, 1);
                list(, $middle) = unpack('C', $middleByte);

                if ($index == 0) {
                    $middle = (0xF0 & $middle) >> 4;
                } else {
                    $middle = 0x0F & $middle;
                }

                $bytes = $this->_file->readFrom($baseOffset + $index * 4, 3);
                list(, $node) = unpack('N', chr($middle).$bytes);
                return $node;

            case 32:
                $bytes = $this->_file->readFrom($baseOffset + $index * 4, 4);
                list(, $node) = unpack('N', $bytes);
                return $node;

            default:
                throw Glitch::EUnexpectedValue(
                    'Unknown record size: '.$this->_metaData['record_size']
                );
        }
    }

    protected function _resolveDataPointer($pointer)
    {
        $resolved = $pointer - $this->_metaData['node_count'] + $this->_metaData['search_tree_size'];

        if ($resolved > $this->_fileSize) {
            throw Glitch::EUnexpectedValue(
                'MMDB file search tree is corrupt'
            );
        }

        return $this->_decoder->decode($resolved)[0];
    }
}
