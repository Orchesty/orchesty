<?php

namespace App\Form\Systems;

use AlesWita\FormRenderer\BootstrapV4Renderer;
use CcApi\ApiEntity\UserSystem;
use Nette\Application\UI\Form;

/**
 * Class WisePopFormFactory
 *
 * @package App\Form\Systems
 */
class WisePopFormFactory
{

    /**
     * @param UserSystem $system
     * @param array      $list
     *
     * @return Form
     */
    public function create(UserSystem $system, array $list = []): Form
    {
        natcasesort($list);

        $form = new Form();

        for ($i = 0; $i < count($system->getCustomForm()); $i++) {
            $con = $form->addContainer($i);

            $con->addText('form_id', 'Form')
                ->setAttribute('readonly');

            $con->addText('form_name', 'Name')
                ->setAttribute('readonly');

            $con->addSelect('list', 'Distribution list', $list)
                ->setPrompt('Choose list');
        }

        $form->addSubmit('refresh', 'Refresh');
        $form->addSubmit('save_custom_data', 'Save');
        $form->setDefaults($system->getCustomForm());
        $form->setRenderer(new BootstrapV4Renderer());

        return $form;
    }

}