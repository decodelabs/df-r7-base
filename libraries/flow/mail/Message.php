<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flow\mail;

use DecodeLabs\Atlas\File;
use DecodeLabs\Exceptional;
use df\core;
use df\flow;
use df\user;

class Message implements IMessage
{
    protected $_subject;
    protected $_bodyText;
    protected $_bodyHtml;

    protected $_from;
    protected $_replyTo;
    protected $_returnPath;

    protected $_toUsers = [];
    protected $_toAdmin = false;
    protected $_filterClient = false;

    protected $_toAddresses;
    protected $_ccAddresses;
    protected $_bccAddresses;

    protected $_attachments = [];

    protected $_journalName;
    protected $_journalDuration;
    protected $_journalKey1;
    protected $_journalKey2;
    protected $_shouldJournal = true;
    protected $_forceSend = false;


    public function __construct($subject, $body, ...$to)
    {
        $this->setSubject($subject);
        $this->setBodyHtml($body);

        $this->_toAddresses = new AddressList();
        $this->_ccAddresses = new AddressList();
        $this->_bccAddresses = new AddressList();


        if (!empty($to)) {
            $this->addRecipients(...$to);
        }
    }

    public function __clone()
    {
        $this->_toAddresses = clone $this->_toAddresses;
        $this->_ccAddresses = clone $this->_ccAddresses;
        $this->_bccAddresses = clone $this->_bccAddresses;

        foreach ($this->_attachments as $id => $attachment) {
            $this->_attachments[$id] = clone $attachment;
        }
    }


    // Subject
    public function setSubject(string $subject)
    {
        $this->_subject = $subject;
        return $this;
    }

    public function getSubject(): string
    {
        return $this->_subject;
    }



    // From
    public function setFromAddress($address, $name = null)
    {
        $this->_from = Address::factory($address, $name);
        return $this;
    }

    public function getFromAddress()
    {
        return $this->_from;
    }

    public function hasFromAddress(): bool
    {
        return $this->_from !== null;
    }

    public function isFromAddressValid(): bool
    {
        if (!$this->_from) {
            return false;
        }

        return $this->_from->isValid();
    }


    // Reply to
    public function setReplyToAddress($address = null)
    {
        $this->_replyTo = Address::factory($address);
        return $this;
    }

    public function getReplyToAddress()
    {
        return $this->_replyTo;
    }


    // Return path
    public function setReturnPath($address = null)
    {
        $this->_returnPath = Address::factory($address);
        return $this;
    }

    public function getReturnPath()
    {
        return $this->_returnPath;
    }


    // Recipients
    public function setRecipients(...$to)
    {
        $this->clearRecipients();
        return $this->addRecipients(...$to);
    }

    public function addRecipients(...$to)
    {
        foreach ($to as $recipient) {
            $this->addRecipient($recipient);
        }

        return $this;
    }

    public function addRecipient($to, $name = null)
    {
        if ($to === true) {
            $this->shouldSendToAdmin(true);
            return;
        }

        if (is_numeric($to)) {
            return $this->addToUser((int)$to);
        }

        if (is_string($to)) {
            $to = Address::factory($to, $name);
        }

        if ($to instanceof IAddress) {
            return $this->addToAddress($to);
        } elseif ($to instanceof IAddressList) {
            foreach ($to->toArray() as $address) {
                $this->addToAddress($address);
            }

            return $this;
        }

        if ($to instanceof user\IClientDataObject) {
            return $this->addToUser($to);
        }

        if (is_array($to) || $to instanceof \ArrayAccess) {
            if (isset($to['id'])) {
                return $this->addToUser($to);
            } elseif (isset($to['email'])) {
                if (!$name && isset($to['fullName'])) {
                    $name = $to['fullName'];
                } elseif (!$name && isset($to['name'])) {
                    $name = $to['name'];
                }

                return $this->addToAddress($to['email'], $name);
            }
        }

        throw Exceptional::InvalidArgument(
            'Invalid recipient'
        );
    }

    public function countRecipients(): int
    {
        return count($this->_toUsers)
            + $this->_toAddresses->count()
            + $this->_ccAddresses->count()
            + $this->_bccAddresses->count()
            + $this->_toAdmin ? 1 : 0;
    }

    public function hasRecipients(): bool
    {
        return !empty($this->_toUsers)
            || $this->_toAdmin
            || !$this->_toAddresses->isEmpty()
            || !$this->_ccAddresses->isEmpty()
            || !$this->_bccAddresses->isEmpty();
    }

    public function hasRecipient(...$to): bool
    {
        foreach ($to as $recipient) {
            if (is_numeric($recipient)) {
                if ($this->hasToUser((int)$recipient)) {
                    return true;
                } else {
                    continue;
                }
            }

            if (is_string($recipient) || $recipient instanceof IAddress) {
                if ($this->hasToAddress($recipient)
                || $this->hasCcAddress($recipient)
                || $this->hasBccAddress($recipient)) {
                    return true;
                } else {
                    continue;
                }
            }

            if ($recipient instanceof user\IClientDataObject) {
                if ($this->hasToUser($recipient)) {
                    return true;
                } else {
                    continue;
                }
            }

            if (is_array($recipient) || $recipient instanceof \ArrayAccess) {
                if (isset($recipient['id'])) {
                    if ($this->hasToUser((int)$recipient['id'])) {
                        return true;
                    } else {
                        continue;
                    }
                } elseif (isset($recipient['email'])) {
                    if ($this->hasToAddress($recipient['email'])
                    || $this->hasCcAddress($recipient['email'])
                    || $this->hasBccAddress($recipient['email'])) {
                        return true;
                    } else {
                        continue;
                    }
                }
            }
        }

        return false;
    }

    public function removeRecipient(...$to)
    {
        foreach ($to as $recipient) {
            if (is_numeric($recipient)) {
                $this->removeToUser((int)$recipient);
                continue;
            }

            if (is_string($recipient) || $recipient instanceof IAddress) {
                $this->removeToAddress($recipient);
                $this->removeCcAddress($recipient);
                $this->removeBccAddress($recipient);
                continue;
            }

            if ($recipient instanceof user\IClientDataObject) {
                $this->removeToUser($recipient);
                $this->removeToAddress($recipient->getEmail());
                $this->removeCcAddress($recipient->getEmail());
                $this->removeBccAddress($recipient->getEmail());
                continue;
            }

            if (is_array($recipient) || $recipient instanceof \ArrayAccess) {
                if (isset($recipient['id'])) {
                    $this->removeToUser((int)$recipient['id']);
                    continue;
                } elseif (isset($recipient['email'])) {
                    $this->removeToAddress($recipient['email']);
                    $this->removeCcAddress($recipient['email']);
                    $this->removeBccAddress($recipient['email']);
                    continue;
                }
            }
        }

        return $this;
    }

    public function clearRecipients()
    {
        $this->_toUsers = [];
        $this->_toAddresses->clear();
        $this->_ccAddresses->clear();
        $this->_bccAddresses->clear();
        return $this;
    }

    public function shouldSendToAdmin(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_toAdmin = $flag;
            return $this;
        }

        return $this->_toAdmin;
    }

    public function shouldFilterClient(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_filterClient = $flag;
            return $this;
        }

        return $this->_filterClient;
    }



    // To users
    public function setToUsers(...$ids)
    {
        return $this->clearToUsers()->addToUsers(...$ids);
    }

    public function addToUsers(...$ids)
    {
        foreach ($ids as $id) {
            $this->addToUser($id);
        }

        return $this;
    }

    public function addToUser($id)
    {
        if ($id instanceof user\IClientDataObject) {
            $this->_toUsers[$id->getId()] = [
                'email' => $id->getEmail(),
                'name' => $id->getFullName()
            ];
        } else {
            $name = $object = null;

            if ((is_array($id) || $id instanceof \ArrayAccess) && isset($id['id'])) {
                if (isset($id['fullName'])) {
                    $name = $id['fullName'];
                } elseif (isset($id['name'])) {
                    $name = $id['name'];
                }

                if (isset($id['email'])) {
                    $object = [
                        'email' => $id['email'],
                        'name' => $name
                    ];
                }

                $id = $id['id'];
            }

            if (is_numeric($id)) {
                $this->_toUsers[(int)$id] = $object;
            }
        }

        return $this;
    }

    public function getToUsers(): array
    {
        return $this->_toUsers;
    }

    public function countToUsers(): int
    {
        return count($this->_toUsers);
    }

    public function hasToUsers(): bool
    {
        return !empty($this->_toUsers);
    }

    public function hasToUser(...$ids): bool
    {
        foreach ($ids as $id) {
            if ($id instanceof user\IClientDataObject) {
                $id = $id->getId();
            } elseif ((is_array($id) || $id instanceof \ArrayAccess) && isset($id['id'])) {
                $id = $id['id'];
            }

            if (isset($this->_toUsers[(int)$id])) {
                return true;
            }
        }

        return false;
    }

    public function removeToUser(...$ids)
    {
        foreach ($ids as $id) {
            if ($id instanceof user\IClientDataObject) {
                $id = $id->getId();
            } elseif ((is_array($id) || $id instanceof \ArrayAccess) && isset($id['id'])) {
                $id = $id['id'];
            }

            unset($this->_toUsers[(int)$id]);
        }

        return $this;
    }

    public function clearToUsers()
    {
        $this->_toUsers = [];
        return $this;
    }


    // To addresses
    public function setToAddresses(...$addresses)
    {
        $this->_toAddresses->clear()->import(...$addresses);
        return $this;
    }

    public function addToAddresses(...$addresses)
    {
        $this->_toAddresses->import(...$addresses);
        return $this;
    }

    public function addToAddress($address, $name = null)
    {
        $this->_toAddresses->add($address, $name);
        return $this;
    }

    public function getToAddresses(): IAddressList
    {
        return $this->_toAddresses;
    }

    public function countToAddresses(): int
    {
        return $this->_toAddresses->count();
    }

    public function hasToAddresses(): bool
    {
        return !$this->_toAddresses->isEmpty();
    }

    public function hasToAddress(...$addresses): bool
    {
        return $this->_toAddresses->has(...$addresses);
    }

    public function removeToAddress(...$addresses)
    {
        $this->_toAddresses->remove(...$addresses);
        return $this;
    }

    public function clearToAddresses()
    {
        $this->_toAddresses->clear();
        return $this;
    }


    // CC addresses
    public function setCcAddresses(...$addresses)
    {
        $this->_ccAddresses->clear()->import(...$addresses);
        return $this;
    }

    public function addCcAddresses(...$addresses)
    {
        $this->_ccAddresses->import(...$addresses);
        return $this;
    }

    public function addCcAddress($address, $name = null)
    {
        $this->_ccAddresses->add($address, $name);
        return $this;
    }

    public function getCcAddresses(): IAddressList
    {
        return $this->_ccAddresses;
    }

    public function countCcAddresses(): int
    {
        return $this->_ccAddresses->count();
    }

    public function hasCcAddresses(): bool
    {
        return !$this->_ccAddresses->isEmpty();
    }

    public function hasCcAddress(...$addresses): bool
    {
        return $this->_ccAddresses->has(...$addresses);
    }

    public function removeCcAddress(...$addresses)
    {
        $this->_ccAddresses->remove(...$addresses);
        return $this;
    }

    public function clearCcAddresses()
    {
        $this->_ccAddresses->clear();
        return $this;
    }


    // BCC addresses
    public function setBccAddresses(...$addresses)
    {
        $this->_bccAddresses->clear()->import(...$addresses);
        return $this;
    }

    public function addBccAddresses(...$addresses)
    {
        $this->_bccAddresses->import(...$addresses);
        return $this;
    }

    public function addBccAddress($address, $name = null)
    {
        $this->_bccAddresses->add($address, $name);
        return $this;
    }

    public function getBccAddresses(): IAddressList
    {
        return $this->_bccAddresses;
    }

    public function countBccAddresses(): int
    {
        return $this->_bccAddresses->count();
    }

    public function hasBccAddresses(): bool
    {
        return !$this->_bccAddresses->isEmpty();
    }

    public function hasBccAddress(...$addresses): bool
    {
        return $this->_bccAddresses->has(...$addresses);
    }

    public function removeBccAddress(...$addresses)
    {
        $this->_bccAddresses->remove(...$addresses);
        return $this;
    }

    public function clearBccAddresses()
    {
        $this->_bccAddresses->clear();
        return $this;
    }


    // Body
    public function setBodyHtml(string $body = null)
    {
        $this->_bodyHtml = $body;
        return $this;
    }

    public function getBodyHtml()
    {
        return $this->_bodyHtml;
    }

    public function setBodyText(string $body = null)
    {
        $this->_bodyText = $body;
        return $this;
    }

    public function getBodyText()
    {
        return $this->_bodyText;
    }


    // Attachments
    public function attach(File $file, string $contentId = null): IAttachment
    {
        $attachment = new Attachment($file, $contentId);
        $this->addAttachment($attachment);
        return $attachment;
    }

    public function addAttachment(IAttachment $attachment)
    {
        $this->_attachments[$attachment->getContentId()] = $attachment;
        return $this;
    }

    public function getAttachments(): array
    {
        return $this->_attachments;
    }

    public function getAttachment(string $id)
    {
        if (isset($this->_attachments[$id])) {
            return $this->_attachments[$id];
        }
    }

    public function hasAttachment(string ...$ids)
    {
        foreach ($ids as $id) {
            if (isset($this->_attachments[$id])) {
                return true;
            }
        }

        return false;
    }

    public function removeAttachment(string ...$ids)
    {
        foreach ($ids as $id) {
            unset($this->_attachments[$id]);
        }

        return $this;
    }

    public function clearAttachments()
    {
        $this->_attachments = [];
        return $this;
    }



    // Journal
    public function setJournalName(string $name = null)
    {
        $this->_journalName = $name;
        return $this;
    }

    public function getJournalName()
    {
        return $this->_journalName;
    }

    public function setJournalDuration(core\time\IDuration $duration = null)
    {
        $this->_journalDuration = $duration;
        return $this;
    }

    public function getJournalDuration()
    {
        if ($this->_journalDuration) {
            return $this->_journalDuration;
        }

        return core\time\Duration::fromWeeks(12);
    }

    public function setJournalKey1($key)
    {
        $this->_journalKey1 = $key;
        return $this;
    }

    public function getJournalKey1()
    {
        return $this->_journalKey1;
    }

    public function setJournalKey2($key)
    {
        $this->_journalKey2 = $key;
        return $this;
    }

    public function getJournalKey2()
    {
        return $this->_journalKey2;
    }


    public function shouldJournal(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_shouldJournal = $flag;
            return $this;
        }

        return $this->_shouldJournal && $this->_journalName !== null;
    }


    // Send
    public function send(ITransport $transport = null)
    {
        flow\Manager::getInstance()->sendMail($this, $transport);
        return $this;
    }

    public function shouldForceSend(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_forceSend = $flag;
            return $this;
        }

        return $this->_forceSend;
    }

    public function forceSend()
    {
        flow\Manager::getInstance()->forceSendMail($this);
        return $this;
    }
}
