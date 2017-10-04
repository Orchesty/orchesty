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

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string|int|bool|null
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
     * Field constructor.
     *
     * @param string $type
     * @param string $key
     * @param string $label
     * @param null   $value
     * @param bool   $required
     *
     * @throws CleverConnectorsException
     */
    public function __construct(string $type, string $key, string $label, $value = NULL, bool $required = FALSE)
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
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return int|string|bool|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
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
        ];
    }

}