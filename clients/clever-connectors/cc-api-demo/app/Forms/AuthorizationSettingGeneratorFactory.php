<?php
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/26/17
 * Time: 9:46 AM
 */

namespace App\Forms;

use CcApi\ApiEntity\UserSystem;
use Nette\Application\UI\Form;

/**
 * Class AuthorizeGeneratorFactory
 *
 * @package App\Forms
 */
class AuthorizationSettingGeneratorFactory
{

    /**
     * @param UserSystem $userSystem
     *
     * @return Form
     */
    public function create(?UserSystem $userSystem = NULL): Form
    {
        $form = new Form();

        if ($userSystem) {
            foreach ($userSystem->getSettingFields() as $field) {

                if ($field->getType() !== 'password') {
                    $form
                        ->addText($field->getKey(), $field->getLabel())
                        ->setHtmlType($field->getType())
                        ->setDefaultValue($field->getValue())
                        ->setRequired($field->isRequired())
                        ->setOption('description', 'Desc')
                        ->setDisabled(TRUE);
                } else {
                    $form
                        ->addText($field->getKey(), $field->getLabel())
                        ->setHtmlType($field->getType())
                        ->setDefaultValue($field->getValue())
                        ->setRequired($field->isRequired())
                        ->setOption('description', 'Desc')
                        ->setDisabled(TRUE);
                    $form->addSubmit('save_password', 'Save password');
                }
            }

            $form->addSubmit('save_auth_setting', 'Save');
        }

        return $form;
    }

}