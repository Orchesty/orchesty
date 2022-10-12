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
    public function __construct(private string $key, private string $publicName){
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
     * @return $this
     */
    public function setDescription(string $value): Form {
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
     * @return $this
     */
    public function setPublicName(string $value): Form {
        $this->publicName = $value;

        return $this;
    }

    /**
     * @param Field $field
     *
     * @return Form
     */
    public function addField(Field $field): Form
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
            'key' => $this->key,
            'publicName' => $this->publicName,
            'description' => $this->description,
            ApplicationInterface::FIELDS => $fields,
        ];
    }

}
