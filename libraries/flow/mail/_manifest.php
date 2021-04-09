<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mail;

use df;
use df\core;
use df\flow;

use DecodeLabs\Atlas\File;

interface IAddress extends core\IStringProvider
{
    public function setAddress($address);
    public function getAddress();
    public function getDomain();
    public function setName($name);
    public function getName(): ?string;
    public function isValid(): bool;
}

interface IAddressList extends \IteratorAggregate, core\collection\IMappedCollection, core\IStringProvider
{
    public function toNameMap();
    public function add($address, $name=null);
}


interface IJournalableMessage
{
    public function setJournalName(string $name=null);
    public function getJournalName();
    public function setJournalDuration(core\time\IDuration $duration=null);
    public function getJournalDuration();
    public function setJournalKey1($key);
    public function getJournalKey1();
    public function setJournalKey2($key);
    public function getJournalKey2();
    public function shouldJournal(bool $flag=null);
}



interface IMessage extends IJournalableMessage
{
    public function setSubject(string $subject);
    public function getSubject(): string;

    public function setFromAddress($address, $name=null);
    public function getFromAddress();
    public function hasFromAddress(): bool;
    public function isFromAddressValid(): bool;

    public function setReplyToAddress($address=null);
    public function getReplyToAddress();

    public function setReturnPath($address=null);
    public function getReturnPath();

    public function setRecipients(...$to);
    public function addRecipients(...$to);
    public function addRecipient($to, $name=null);
    public function countRecipients(): int;
    public function hasRecipients(): bool;
    public function hasRecipient(...$to): bool;
    public function removeRecipient(...$to);
    public function clearRecipients();
    public function shouldSendToAdmin(bool $flag=null);
    public function shouldFilterClient(bool $flag=null);

    public function setToUsers(...$ids);
    public function addToUsers(...$ids);
    public function addToUser($id);
    public function getToUsers(): array;
    public function countToUsers(): int;
    public function hasToUsers(): bool;
    public function hasToUser(...$ids): bool;
    public function removeToUser(...$ids);
    public function clearToUsers();

    public function setToAddresses(...$addresses);
    public function addToAddresses(...$addresses);
    public function addToAddress($address, $name=null);
    public function getToAddresses(): IAddressList;
    public function countToAddresses(): int;
    public function hasToAddresses(): bool;
    public function hasToAddress(...$addresses): bool;
    public function removeToAddress(...$addresses);
    public function clearToAddresses();

    public function setCcAddresses(...$addresses);
    public function addCcAddresses(...$addresses);
    public function addCcAddress($address, $name=null);
    public function getCcAddresses(): IAddressList;
    public function countCcAddresses(): int;
    public function hasCcAddresses(): bool;
    public function hasCcAddress(...$addresses): bool;
    public function removeCcAddress(...$addresses);
    public function clearCcAddresses();

    public function setBccAddresses(...$addresses);
    public function addBccAddresses(...$addresses);
    public function addBccAddress($address, $name=null);
    public function getBccAddresses(): IAddressList;
    public function countBccAddresses(): int;
    public function hasBccAddresses(): bool;
    public function hasBccAddress(...$addresses): bool;
    public function removeBccAddress(...$addresses);
    public function clearBccAddresses();

    public function setBodyHtml(string $body=null);
    public function getBodyHtml();
    public function setBodyText(string $body=null);
    public function getBodyText();

    public function attach(File $file, string $contentId=null): IAttachment;
    public function addAttachment(IAttachment $attachment);
    public function getAttachments(): array;
    public function getAttachment(string $id);
    public function hasAttachment(string ...$ids);
    public function removeAttachment(string ...$ids);
    public function clearAttachments();

    public function send(ITransport $transport=null);
    public function shouldForceSend(bool $flag=null);
    public function forceSend();
}

interface IAttachment
{
    public function setFile(File $file);
    public function getFile(): File;

    public function setFileName(string $fileName=null);
    public function getFileName();

    public function getContentId(): string;
    public function getContentType(): string;
}


interface ITransport
{
    public static function getName(): string;
    public static function getDescription();

    public function send(IMessage $message, flow\mime\IMultiPart $mime);
}



interface IMailModel
{
    public function captureMail(flow\mime\IMultiPart $message);
    public function journalMail(IJournalableMessage $message);
}

interface IMailRecord
{
    public function getId(): ?string;
    public function getFromAddress();
    public function getToAddresses();
    public function getSubject();
    public function getBodyString();
    public function getDate();
    public function toMessage();
}
