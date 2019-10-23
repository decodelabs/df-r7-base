<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link;

interface Socket
{
    public function getId(): string;
    public function getImplementationName();
    public function getAddress();
    public function getSocketDescriptor();
    public function setSessionId($id);
    public function getSessionId();

    // Options
    public function getOptions();

    public function setSendBufferSize($buffer);
    public function getSendBufferSize();
    public function setReceiveBufferSize($buffer);
    public function getReceiveBufferSize();

    public function setSendLowWaterMark($bytes);
    public function getSendLowWaterMark();
    public function setReceiveLowWaterMark($bytes);
    public function getReceiveLowWaterMark();

    public function setSendTimeout($timeout);
    public function getSendTimeout();
    public function setReceiveTimeout($timeout);
    public function getReceiveTimeout();

    // State
    public function isConnected();
    public function isActive();
    public function isReadingEnabled();
    public function isWritingEnabled();
    public function shouldBlock(bool $flag=null);

    // Shutdown
    public function shutdownReading();
    public function shutdownWriting();
    public function close();
}
