<?php

namespace EffectConnect\Marketplaces\Model;

class ConfigurationValue
{
    /**
     * Input fields types.
     */
    const TYPE_TEXT     = 'text';
    const TYPE_NUMBER   = 'number';
    const TYPE_SELECT   = 'select';
    const TYPE_CHECKBOX = 'checkbox';

    /**
     * @var string
     */
    protected $type = self::TYPE_TEXT;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param string $type
     * @param string $name
     * @param string $description
     * @param mixed $value
     * @param array $options
     */
    public function __construct(string $type, string $name, string $description, $value = null, array $options = [])
    {
        $this->type        = in_array($type, $this->getTypes()) ? $type : self::TYPE_TEXT;
        $this->name        = $name;
        $this->description = $description;
        $this->value       = $value;
        $this->options     = $options;
    }

    /**
     * @return string[]
     */
    protected function getTypes(): array
    {
        return [
            self::TYPE_TEXT,
            self::TYPE_NUMBER,
            self::TYPE_SELECT,
            self::TYPE_CHECKBOX,
        ];
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param mixed $value
     * @return void
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return bool
     */
    public function typeIsText(): bool
    {
        return $this->type === self::TYPE_TEXT;
    }

    /**
     * @return bool
     */
    public function typeIsNumber(): bool
    {
        return $this->type === self::TYPE_NUMBER;
    }

    /**
     * @return bool
     */
    public function typeIsSelect(): bool
    {
        return $this->type === self::TYPE_SELECT;
    }

    /**
     * @return bool
     */
    public function typeIsCheckbox(): bool
    {
        return $this->type === self::TYPE_CHECKBOX;
    }
}