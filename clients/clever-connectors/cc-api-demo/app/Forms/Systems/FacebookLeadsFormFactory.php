<?php

namespace App\Form\Systems;

use AlesWita\FormRenderer\BootstrapV4Renderer;
use CcApi\ApiEntity\UserSystem;
use Nette\Application\UI\Form;

/**
 * Class FacebookLeadsFormFactory
 *
 * @package App\Form\Systems
 */
class FacebookLeadsFormFactory
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
            $container = $form->addContainer($i);

            $container->addText('form_id', 'Form')
                ->setAttribute('readonly');

            $container->addText('form_name', 'Name')
                ->setAttribute('readonly');

            $container->addSelect('list', 'Distribution list', $list)
                ->setPrompt('Choose list');
        }

        $form
            ->addSelect('page', 'Page')
            ->setPrompt('Choose page');

        $form->addSubmit('refresh_pages', 'Refresh Pages');
        $form->addSubmit('refresh_forms', 'Refresh Forms');
        $form->addSubmit('save_custom_data', 'Save');
        $form->setDefaults($system->getCustomForm());
        $form->setRenderer(new BootstrapV4Renderer());

        return $form;
    }

}