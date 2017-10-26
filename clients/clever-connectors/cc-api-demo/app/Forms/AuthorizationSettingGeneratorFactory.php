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
                    $form->addText($field->getKey(), $field->getLabel());

                    if ($field->getDescription() != '') {
                        $form[$field->getKey()]->setOption('description', $field->getDescription());
                    }
                } else {
                    $form->addText($field->getKey(), $field->getLabel());

                    if ($field->getValue()) {
                        $message = 'Password exists.';
                    } else {
                        $message = 'Password not exists.';
                    }

                    if ($field->getDescription() != '') {
                        $form[$field->getKey()]->setOption('description', $message . ' - ' . $field->getDescription());
                    } else {
                        $form[$field->getKey()]->setOption('description', $message);
                    }
                }

                $form[$field->getKey()]
                    ->setHtmlType($field->getType())
                    ->setDefaultValue($field->getValue());

                if ($field->isRequired()) {
                    $form[$field->getKey()]->setRequired('This field is required.');
                }

                if ($field->isDisabled()) {
                    $form[$field->getKey()]->setDisabled(TRUE);
                }

                if ($field->isReadOnly()) {
                    $form[$field->getKey()]->setAttribute('readonly');
                }
            }

            $form->addHidden('system_key', $userSystem->getKey());

            $form->addSubmit('save_auth_setting', 'Save');
        }

        return $form;
    }

}