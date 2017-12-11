<?php
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 11.12.17
 * Time: 8:07
 */

namespace App\Forms;

use Nette\Application\UI\Form;
use Nette\Forms\Container;
use WebChemistry\Forms\Controls\Multiplier;

/**
 * Class DataLayoutFormFactory
 *
 * @package App\Forms
 */
class DataLayoutFormFactory
{

    /**
     * @return Form
     */
    public function create(): Form
    {
        $form = new Form();

        $form->addSubmit('save_data_layout', 'Save');

        /** @var Multiplier $multiplier */
        $multiplier = $form->addMultiplier('multiplier', function (Container $container, Form $form) {
            $container
                ->addText('field_key', 'Field Key')
                ->setRequired('The field key id required, please fill it.');
        });

        $multiplier->addCreateButton('Add field');
        $multiplier->addRemoveButton('Remove');

        return $form;
    }
}