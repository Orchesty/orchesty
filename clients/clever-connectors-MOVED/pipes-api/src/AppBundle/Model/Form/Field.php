<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Form;

use CleverConnectors\AppBundle\Document\SystemInstall;
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
    public const SELECT   = 'select';

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $key;

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
    private $required = FALSE;

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
     * @var array
     */
    private $choices = [];

    /**
     * @var string
     */
    private $actionUrl = '';

    /**
     * @var string
     */
    private $dependsOn = '';

    /**
     * Field constructor.
     *
     * @param string     $type
     * @param string     $key
     * @param string     $label
     * @param mixed|null $value
     * @param bool       $required
     *
     * @throws CleverConnectorsException
     */
    public function __construct(
        string $type,
        string $key,
        string $label,
        $value = NULL,
        bool $required = FALSE
    )
    {
        if (!in_array($type, $this->getTypes())) {
            throw new CleverConnectorsException(
                sprintf('Invalid field type "%s"', $type),
                CleverConnectorsException::INVALID_FIELD_TYPE
            );
        }

        $this->type     = $type;
        $this->key      = $key;
        $this->label    = $label;
        $this->value    = $value;
        $this->required = $required;
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
    public function getChoices(): array
    {
        return $this->choices;
    }

    /**
     * @param array $choices
     *
     * @return Field
     */
    public function setChoices(array $choices): Field
    {
        $this->choices = $choices;

        return $this;
    }

    /**
     * @return string
     */
    public function getActionUrl(): string
    {
        return $this->actionUrl;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $host
     * @param string        $action
     *
     * @return Field
     */
    public function setAction(SystemInstall $systemInstall, string $host, string $action): Field
    {
        $this->actionUrl = sprintf(
            '%s/system/%s/user/%s/action/%s',
            rtrim($host),
            $systemInstall->getSystem(),
            $systemInstall->getUser(),
            $action
        );

        return $this;
    }

    /**
     * @return string
     */
    public function getDependsOn(): string
    {
        return $this->dependsOn;
    }

    /**
     * @param Field $field
     */
    public function setDependsOn(Field $field): void
    {
        $this->dependsOn = $field->getKey();
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
            'choices'     => $this->choices,
            'action'      => $this->actionUrl,
            'depends_on'  => $this->dependsOn,
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
            self::SELECT,
        ];
    }

}