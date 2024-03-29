<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\scaffold\Record;

use DecodeLabs\Exceptional;

use DecodeLabs\Tightrope\Manifest\Requirable;
use DecodeLabs\Tightrope\Manifest\RequirableTrait;
use df\opal\query\ISelectQuery as SelectQuery;

class Filter implements Requirable
{
    use RequirableTrait;

    protected $key;
    protected $label;
    protected $optionGenerator;

    protected $valueGenerator;
    protected $value;
    protected $valueGenerated = false;

    protected $applicator;
    protected $grouped = false;
    protected $listFieldModifier;
    protected $overrideContextKeys = [];

    /**
     * Init with options
     */
    public function __construct(
        string $key,
        ?string $label = null,
        ?callable $optionGenerator = null,
        bool $required = false
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
    public function setOptionGenerator(?callable $optionGenerator = null): Filter
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
        if ($this->valueGenerated) {
            return $this->value;
        }

        if (!$this->valueGenerator) {
            return null;
        }

        $this->value = ($this->valueGenerator)();
        $this->valueGenerated = true;

        return $this->value;
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


    /**
     * Set field modifier
     */
    public function setListFieldModifier(?callable $fieldModifier): Filter
    {
        $this->listFieldModifier = $fieldModifier;
        return $this;
    }

    /**
     * Get field modifier
     */
    public function getListFieldModifier(): ?callable
    {
        return $this->listFieldModifier;
    }

    /**
     * Get list fields
     */
    public function getListFields(): ?array
    {
        if (!$this->listFieldModifier) {
            return null;
        }

        $output = ($this->listFieldModifier)($this->getValue());

        if (!is_array($output)) {
            return null;
        }

        return $output;
    }



    /**
     * Set override context keys
     */
    public function setOverrideContextKeys(string ...$contextKeys): Filter
    {
        $this->overrideContextKeys = $contextKeys;
        return $this;
    }

    /**
     * Get override context keys
     */
    public function getOverrideContextKeys(): array
    {
        return $this->overrideContextKeys;
    }

    /**
     * Has override context key
     */
    public function isOverridden(?string $contextKey): bool
    {
        if ($contextKey === null) {
            return false;
        }

        return in_array($contextKey, $this->overrideContextKeys);
    }
}
