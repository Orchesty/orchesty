<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 2:55 PM
 */

namespace CcApi\ApiEntity;

/**
 * Class SettingField
 *
 * @package CcApi\Entity
 */
class SettingField
{

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $value;

    /**
     * @var string
     */
    private $label;

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
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return SettingField
     */
    public function setKey(string $key): SettingField
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return SettingField
     */
    public function setType(string $type): SettingField
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     *
     * @return SettingField
     */
    public function setValue($value): SettingField
    {
        $this->value = $value;

        return $this;
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
     * @return SettingField
     */
    public function setLabel(string $label): SettingField
    {
        $this->label = $label;

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
     * @return SettingField
     */
    public function setRequired(bool $required): SettingField
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
     * @return $this
     */
    public function setReadOnly(bool $readOnly): SettingField
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
     * @return $this
     */
    public function setDisabled(bool $disabled): SettingField
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
     * @return $this
     */
    public function setDescription(string $description): SettingField
    {
        $this->description = $description;

        return $this;
    }

}