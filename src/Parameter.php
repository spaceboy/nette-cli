<?php
namespace Spaceboy\NetteCli;

use Nette\Utils\AssertionException;
use Nette\Utils\Validators;

class Parameter
{
    /** @var string parameter full name (used as --parameter) */
    private string $name;
    
    /** @var string parameter shortcut (used as -p) */
    private ?string $shortcut = null;
    
    /** @var string parameter validation format in Nette\Util\Validators format */
    private ?string $format = null;
    
    /** @var mixed parameter value */
    private $value = null;

    /** @var bool unset */
    private bool $unset = true;
    
    /** @var string parameter description */
    private ?string $description = null;

    /**
     * Parameter creator function.
     * @param string $name
     * @return Parameter
     */
    public static function create(string $name): self
    {
        return new static($name);
    }

    /**
     * Class constructor.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    
    /**
     * Name getter.
     * @return string
     */ 
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * Description setter.
     * @param string $description
     * @return Command
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Description getter.
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Shortcut setter.
     * @param string $shortcut
     * @return Parameter
     */
    public function setShortcut(string $shortcut): self
    {
        $this->shortcut = $shortcut;
        return $this;
    }

    /**
     * Shortcut getter
     * @return string
     */
    public function getShortcut(): ?string
    {
        return $this->shortcut;
    }

    /**
     * Format setter.
     * @param string  $format
     * @return Parameter
     */
    public function setFormat(string $format): self
    {
        $this->format = $format;
        return $this;
    }
    
    /**
     * Validation method.
     * @param bool $isRequired
     * @return void
     * @throws AssertionException
     */
    public function validate(bool $isRequired = false): void
    {
        if ($isRequired && $this->unset) {
            throw new AssertionException("Required parameter missing ({$this->getName()}).");
        }
        if ($this->format === null) {
            return;
        }
        Validators::assert($this->getValue(), $this->format, $this->getName());
    }
    
    /**
     * Value setter.
     * @param mixed $value
     * @return Parameter
     */
    public function setValue($value): self
    {
        $this->value = $value;
        $this->unset = false;
        return $this;
    }
    
    /**
     * Value getter.
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Unset getter.
     * @return bool
     */
    /*
    public function isUnset(): bool
    {
        return $this->unset;
    }
    */
}
