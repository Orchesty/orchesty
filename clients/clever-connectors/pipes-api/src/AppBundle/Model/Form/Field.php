<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Form;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;

/**
 * Class Field
 *
 * @package CleverConnectors\AppBundle\Model\Form
 */
class Field
{

    public const TEXT     = 'text';
    public const URL      = 'url';
    public const NUMBER   = 'number';
    public const PASSWORD = 'password';
    public const CHECKBOX = 'checkbox';

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $label;

    /**
     * @var mixed|null
     */
    private $value;

    /**
     * @var bool
     */
    private $required;

    /**
     * @var string
     */
    private $key;

    /**
     * @var bool
     */
    private $readOnly = FALSE;

    /**
     * @var bool
     */
    private $disabled = FALSE;

    /**
     * @var string
     */
    private $description = '';

    /**
     * Field constructor.
     *
     * @param string     $type
     * @param string     $key
     * @param string     $label
     * @param mixed|null $value
     * @param bool       $required
     * @param bool       $readOnly
     *
     * @throws CleverConnectorsException
     */
    public function __construct(
        string $type,
        string $key,
        string $label,
        $value = NULL,
        bool $required = FALSE,
        bool $readOnly = FALSE
    )
    {
        if (!in_array($type, $this->getTypes())) {
            throw new CleverConnectorsException(
                sprintf('Invalid field type "%s"', $type),
                CleverConnectorsException::INVALID_FIELD_TYPE
            );
        }

        $this->type        = $type;
        $this->key         = $key;
        $this->label       = $label;
        $this->value       = $value;
        $this->required    = $required;
        $this->readOnly    = $readOnly;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return Field
     */
    public function setLabel(string $label): Field
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return int|string|bool|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     *
     * @return Field
     */
    public function setValue($value): Field
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @param bool $required
     *
     * @return Field
     */
    public function setRequired(bool $required): Field
    {
        $this->required = $required;

        return $this;
    }

    /**
     * @return bool
     */
    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    /**
     * @param bool $readOnly
     *
     * @return Field
     */
    public function setReadOnly(bool $readOnly): Field
    {
        $this->readOnly = $readOnly;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * @param bool $disabled
     *
     * @return Field
     */
    public function setDisabled(bool $disabled): Field
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return Field
     */
    public function setDescription(string $description): Field
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $field = [
            'type'        => $this->type,
            'key'         => $this->key,
            'label'       => $this->label,
            'value'       => $this->value,
            'required'    => $this->required,
            'read_only'   => $this->readOnly,
            'disabled'    => $this->disabled,
            'description' => $this->description,
        ];

        if ($this->type === Field::PASSWORD) {
            $field['value'] = !empty($this->value) ? TRUE : FALSE;
        }

        return $field;
    }

    /**
     * @return array
     */
    private function getTypes(): array
    {
        return [
            self::TEXT,
            self::URL,
            self::NUMBER,
            self::PASSWORD,
            self::CHECKBOX,
        ];
    }

}