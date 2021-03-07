<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Record;

use df\opal\query\ISelectQuery as SelectQuery;

use DecodeLabs\Exceptional;
use DecodeLabs\Gadgets\Constraint\Requirable;
use DecodeLabs\Gadgets\Constraint\RequirableTrait;

class Filter implements Requirable
{
    use RequirableTrait;

    protected $key;
    protected $label;
    protected $optionGenerator;
    protected $valueGenerator;
    protected $applicator;
    protected $grouped = false;

    /**
     * Init with options
     */
    public function __construct(
        string $key,
        ?string $label=null,
        ?callable $optionGenerator=null,
        bool $required=false
    ) {
        $this->setKey($key);
        $this->setLabel($label);
        $this->setOptionGenerator($optionGenerator);
        $this->setRequired($required);
    }


    /**
     * Set key
     */
    public function setKey(string $key): Filter
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Get key
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Set label
     */
    public function setLabel(?string $label): Filter
    {
        $this->label = $label;
        return $this;
    }


    /**
     * Get label
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Set option generator
     */
    public function setOptionGenerator(?callable $optionGenerator=null): Filter
    {
        $this->optionGenerator = $optionGenerator;
        return $this;
    }

    /**
     * Get option generator
     */
    public function getOptionGenerator(): ?callable
    {
        return $this->optionGenerator;
    }

    /**
     * Get options
     */
    public function getOptions(): iterable
    {
        if (!$this->optionGenerator) {
            return [];
        }

        $output = ($this->optionGenerator)();

        if (!is_iterable($output)) {
            throw Exceptional::UnexpectedValue('Options are not iterable');
        }

        return $output;
    }



    /**
     * Set value generator
     */
    public function setValueGenerator(?callable $valueGenerator): Filter
    {
        $this->valueGenerator = $valueGenerator;
        return $this;
    }

    /**
     * Get value generator
     */
    public function getValueGenerator(): ?callable
    {
        return $this->valueGenerator;
    }

    /**
     * Get value
     */
    public function getValue()
    {
        if (!$this->valueGenerator) {
            return null;
        }

        return ($this->valueGenerator)();
    }


    /**
     * Set applicator
     */
    public function setApplicator(?callable $applicator): Filter
    {
        $this->applicator = $applicator;
        return $this;
    }

    /**
     * Get applicator
     */
    public function getApplicator(): ?callable
    {
        return $this->applicator;
    }

    /**
     * Apply queries
     */
    public function apply(SelectQuery $query): void
    {
        $value = $this->getValue();

        if (empty($value)) {
            return;
        }

        $applicator = $this->applicator ?? function ($query, $value) {
            $query->where($this->key, '=', $value);
        };

        $applicator($query, $value);
    }


    /**
     * Set grouped
     */
    public function setGrouped(bool $grouped): Filter
    {
        $this->grouped = $grouped;
        return $this;
    }

    /**
     * Is grouped select
     */
    public function isGrouped(): bool
    {
        return $this->grouped;
    }
}
