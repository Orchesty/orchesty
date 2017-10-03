<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Form;

/**
 * Class Form
 *
 * @package CleverConnectors\AppBundle\Model\Form
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
            $fields[] = [
                'type'     => $field->getType(),
                'label'    => $field->getLabel(),
                'value'    => $field->getValue(),
                'required' => $field->isRequired(),
            ];
        }

        return $fields;
    }

}