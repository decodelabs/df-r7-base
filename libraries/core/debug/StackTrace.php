<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug;

use df;
use df\core;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class StackTrace implements IStackTrace, Inspectable
{
    protected $_message;
    protected $_calls = [];
    protected $_file;
    protected $_line;

    public static function fromException(\Throwable $e)
    {
        return self::factory(0, $e->getTrace())
            ->setMessage($e->getMessage());
    }

    public static function factory($rewind=0, array $data=null)
    {
        if ($data === null) {
            $data = debug_backtrace();
        }

        $output = [];

        while ($rewind > 0) {
            $rewind--;
            array_shift($data);
        }

        $last = array_shift($data);
        $last['fromFile'] = @$last['file'];
        $last['fromLine'] = @$last['line'];

        foreach ($data as $callData) {
            $callData['fromFile'] = @$callData['file'];
            $callData['fromLine'] = @$callData['line'];
            $callData['file'] = $last['fromFile'];
            $callData['line'] = $last['fromLine'];

            $output[] = new StackCall($callData);
            $last = $callData;
        }

        return new self($output);
    }

    protected function __construct(array $calls=null)
    {
        if (!empty($calls)) {
            foreach ($calls as $call) {
                if ($call instanceof IStackCall) {
                    $this->_calls[] = $call;
                }
            }

            if (isset($this->_calls[0])) {
                $this->_file = $this->_calls[0]->getFile();
                $this->_line = $this->_calls[0]->getLine();
            } else {
                $data = debug_backtrace();
                $this->_file = $data[1]['file'];
                $this->_line = $data[1]['line'];
            }
        }
    }

    public function setMessage(?string $message)
    {
        $this->_message = $message;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->_message;
    }

    public function toArray(): array
    {
        $output = [];

        foreach ($this->_calls as $call) {
            $output[] = $call->toArray();
        }

        return $output;
    }

    public function toJsonArray(): array
    {
        $output = [];

        foreach ($this->_calls as $call) {
            $output[] = $call->toJsonArray();
        }

        return $output;
    }

    public function toJson(): string
    {
        return (string)json_encode($this->toJsonArray());
    }

    public function getCalls(): array
    {
        return $this->_calls;
    }

    public function getFirstCall(): ?IStackCall
    {
        return $this->_calls[0] ?? null;
    }


    public function getFile(): ?string
    {
        return $this->_file;
    }

    public function getLine(): ?int
    {
        return $this->_line;
    }


    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setValues($inspector->inspectList($this->_calls));
    }
}
