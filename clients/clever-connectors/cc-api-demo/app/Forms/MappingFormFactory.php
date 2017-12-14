<?php
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 11.12.17
 * Time: 10:39
 */

namespace App\Forms;

use AlesWita\FormRenderer\BootstrapV4Renderer;
use Nette\Application\UI\Form;

/**
 * Class MappingFormFactory
 *
 * @package App\Forms
 */
class MappingFormFactory
{

    /**
     * @param array $data
     *
     * @return Form
     */
    public function create(array $data): Form
    {
        $form = new Form();

        foreach ($data as $key => $value) {

            $form->addSelect($key, $key, $value)
                ->setPrompt('Choose key');

            $form->addSelect($key . '_format', 'Format', [
                'text'   => 'Text',
                'number' => 'Number',
                'bool'   => 'Boolean',
                'date'   => 'Date',
                'url'    => 'Url',
                'email'  => 'Email',
            ])->setPrompt('Choose format');

        }

        $form->addSubmit('save_mapping', 'Save');

        $form->setRenderer(new BootstrapV4Renderer);

        return $form;
    }
}