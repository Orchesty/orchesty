<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Model\Form;

use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;

/**
 * Class Form
 *
 * @package Hanaboso\PipesPhpSdk\Application\Model\Form
 */
final class Form
{

    /**
     * @var string
     */
    private string $description = '';

    /**
     * @var Field[]
     */
    private array $fields = [];

    /**
     * Form constructor.
     *
     * @param string $key
     * @param string $publicName
     */
    public function __construct(private readonly string $key, private string $publicName){
    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return $this->description;
    }

    /**
     * @param string $value
     *
     * @return self
     */
    public function setDescription(string $value): self {
        $this->description = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getKey(): string {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getPublicName(): string {
        return $this->publicName;
    }

    /**
     * @param string $value
     *
     * @return self
     */
    public function setPublicName(string $value): self {
        $this->publicName = $value;

        return $this;
    }

    /**
     * @param Field $field
     *
     * @return self
     */
    public function addField(Field $field): self
    {
        $this->fields[] = $field;

        return $this;
    }

    /**
     * @return Field[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        $fields = [];
        foreach ($this->fields as $field) {
            $fields[] = $field->toArray();
        }

        return [
            'description' => $this->description,
            'key' => $this->key,
            'publicName' => $this->publicName,
            ApplicationInterface::FIELDS => $fields,
        ];
    }

}
