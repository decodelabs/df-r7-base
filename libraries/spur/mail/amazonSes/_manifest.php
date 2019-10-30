<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\mail\amazonSes;

use df;
use df\core;
use df\spur;
use df\flow;

// Interfaces
interface IMediator extends spur\IGuzzleMediator
{
    public function setUrl($url);
    public function getUrl();
    public function setAccessKey($key);
    public function getAccessKey();
    public function setSecretKey($key);
    public function getSecretKey();

    public function getVerifiedAddresses();
    public function deleteVerifiedAddress($address);
    public function getSendQuota();
    public function getSendStatistics();

    public function sendMessage(flow\mail\IMessage $message, flow\mime\IMultiPart $mime);
    public function sendRawMessage(flow\mail\IMessage $message, flow\mime\IMultiPart $mime);

    public function requestXml($method, array $data=[], array $headers=[]);
}
