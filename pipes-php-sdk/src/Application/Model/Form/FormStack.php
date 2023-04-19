<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Model\Form;

/**
 * Class FormStack
 *
 * @package Hanaboso\PipesPhpSdk\Application\Model\Form
 */
final class FormStack
{

    /**
     * @var Form[]
     */
    private array $forms = [];

    /**
     * @return Form[]
     */
    public function getForms(): array {
        return $this->forms;
    }

    /**
     * @param Form $value
     *
     * @return self
     */
    public function addForm(Form $value): self {
        $this->forms[] = $value;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array {
        $output = [];
        foreach ($this->forms as $form) {
            $output[$form->getKey()] = $form->toArray();
        }

        return $output;
    }

}
