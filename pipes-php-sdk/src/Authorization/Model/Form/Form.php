<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Model\Form;

/**
 * Class Form
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Model\Form
 */
class Form
{

    /**
     * @var Field[]
     */
    private $fields = [];

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
     * @return array
     */
    public function toArray(): array
    {
        $fields = [];
        foreach ($this->fields as $field) {
            $fields[] = $field->toArray();
        }

        return $fields;
    }

    /**
     * @return Field[] | array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

}