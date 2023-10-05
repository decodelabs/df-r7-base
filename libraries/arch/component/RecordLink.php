<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\component;

use DecodeLabs\Dictum;
use DecodeLabs\Glitch\Dumpable;
use DecodeLabs\Metamorph;
use df\arch;
use df\aura;

use df\core;
use df\opal;
use df\user;

abstract class RecordLink extends Base implements aura\html\widget\IWidgetProxy, Dumpable
{
    use user\TAccessControlled;
    use core\constraint\TDisableable;
    use core\constraint\TNullable;

    public const DEFAULT_MISSING_MESSAGE = 'not found';

    protected $icon = 'item';
    protected $disposition = 'transitive';
    protected $note;
    protected $maxLength;
    protected $missingMessage;
    protected $node;
    protected $redirectFrom;
    protected $redirectTo;
    protected $name;
    protected $matchRequest;
    protected $record;

    protected function init($record = null, $name = null, $match = null)
    {
        if ($record) {
            $this->setRecord($record);
        }

        if ($name !== null) {
            $this->setName($name);
        }

        if ($match !== null) {
            $this->setMatchRequest($match);
        }
    }

    // Record
    public function setRecord($record)
    {
        if ($record instanceof opal\record\IPrimaryKeySet
        && $record->isNull()) {
            $record = null;
        }

        if (is_scalar($record)) {
            $record = ['id' => $record];
        }

        $this->record = $record;
        return $this;
    }

    public function getRecord()
    {
        return $this->record;
    }

    // Icon
    public function setIcon(string $icon = null)
    {
        $this->icon = $icon;
        return $this;
    }

    public function getIcon()
    {
        return $this->icon;
    }

    // Disposition
    public function setDisposition($disposition)
    {
        $this->disposition = $disposition;
        return $this;
    }

    public function getDisposition()
    {
        return $this->disposition;
    }

    // Note
    public function setNote($note)
    {
        $this->note = $note;
        return $this;
    }

    public function getNote()
    {
        return $this->note;
    }

    // Missing message
    public function setMissingMessage($message)
    {
        $this->missingMessage = $message;
        return $this;
    }

    public function getMissingMessage()
    {
        if (empty($this->missingMessage)) {
            return static::DEFAULT_MISSING_MESSAGE;
        }

        return $this->missingMessage;
    }

    // Max length
    public function setMaxLength(?int $length)
    {
        if (!$length) {
            $length = null;
        } else {
            $length = (int)$length;
        }

        $this->maxLength = $length;
        return $this;
    }

    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    // Match
    public function setMatchRequest($request)
    {
        $this->matchRequest = $request;
        return $this;
    }

    public function getMatchRequest()
    {
        return $this->matchRequest;
    }

    // Node
    public function setNode($node)
    {
        switch ($node) {
            case 'edit':
                $this->setIcon('edit');
                $this->setDisposition('operative');

                if ($this->redirectFrom === null) {
                    $this->setRedirectFrom(true);
                }

                if (!$this->name) {
                    $this->setName($this->_('Edit'));
                }

                break;

            case 'delete':
                $this->setIcon('delete');
                $this->setDisposition('negative');

                if ($this->redirectFrom === null) {
                    $this->setRedirectFrom(true);
                }

                if (!$this->name) {
                    $this->setName($this->_('Delete'));
                }

                break;
        }

        $this->node = $node;
        return $this;
    }

    public function getNode()
    {
        return $this->node;
    }


    // Name
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    // Redirect
    public function setRedirectFrom($rf)
    {
        $this->redirectFrom = $rf;
        return $this;
    }

    public function getRedirectFrom()
    {
        return $this->redirectFrom;
    }

    public function setRedirectTo($rt)
    {
        if (is_string($rt)) {
            $rt = $this->context->uri->backRequest($rt);
        }

        $this->redirectTo = $rt;
        return $this;
    }

    public function getRedirectTo()
    {
        return $this->redirectTo;
    }

    // Render
    public function toWidget(): ?aura\html\widget\IWidget
    {
        return $this->render();
    }

    protected function _execute()
    {
        if ($this->record === null && $this->_isNullable) {
            return null;
        }

        $id = null;

        if ($this->record) {
            $id = $this->getRecordId();
        }

        /** @phpstan-ignore-next-line */
        if (!$this->record || $id === null) {
            $message = $this->missingMessage;

            if (empty($message)) {
                $message = $this->_(static::DEFAULT_MISSING_MESSAGE);
            }

            return $this->html->link('#', $message)
                ->isDisabled(true)
                ->setIcon('error')
                ->addClass('error');
        }



        try {
            $name = $this->name ?? $this->getRecordName();
        } catch (\Throwable $e) {
            $name = $id;
        }

        $title = null;

        if ($this->name !== null) {
            $title = $name;
            $name = $this->name;
        }

        $url = $this->getRecordUri($id);

        if ($url !== null) {
            if (!$this->redirectFrom) {
                if ($this->disposition == 'positive' || $this->disposition == 'negative' || $this->disposition == 'operative') {
                    $this->redirectFrom = true;
                }
            }

            $url = $this->uri->__invoke($url, $this->redirectFrom, $this->redirectTo, true);

            if ($url instanceof arch\IRequest && $this->node) {
                $url->setNode($this->node);
            }
        }


        if ($this->maxLength && is_string($name)) {
            if ($title === null) {
                $title = $name;
            }

            $name = Dictum::shorten($name, $this->maxLength);
        }

        if ($title !== null) {
            $title = Metamorph::htmlToText($title);
        }

        $name = $this->_decorateBody($name);

        $output = $this->html->link($url, $name, $this->matchRequest)
            //->shouldCheckAccess((bool)$this->node)
            ->setIcon($this->icon)
            ->setDisposition($this->disposition)
            ->setNote($this->note)
            ->setTitle($title)
            ->addAccessLocks($this->_accessLocks)
            ->isDisabled($this->_isDisabled);

        if ($this->node && $this->record instanceof user\IAccessLock) {
            switch ($this->node) {
                case 'edit':
                case 'delete':
                    $output->addAccessLock($this->record->getActionLock($this->node));
                    break;

                default:
                    $output->addAccessLock($this->record->getActionLock('access'));
                    break;
            }
        }

        $this->_decorate($output);
        return $output;
    }

    protected function getRecordId(): string
    {
        return (string)$this->record['id'];
    }

    protected function getRecordName()
    {
        if (isset($this->record['name'])) {
            return $this->record['name'];
        } else {
            return '???';
        }
    }

    protected function _decorateBody($name)
    {
        return $name;
    }

    protected function _decorate($link)
    {
    }

    abstract protected function getRecordUri(string $id);


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'text' => $this->render();
    }
}
