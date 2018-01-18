<?php
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/26/17
 * Time: 9:46 AM
 */

namespace App\Forms;

use AlesWita\FormRenderer\BootstrapV4Renderer;
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
     * @param array      $distributionList
     *
     * @return Form
     */
    public function create(?UserSystem $userSystem = NULL, array $distributionList = []): Form
    {
        $form = new Form();

        if ($userSystem) {
            foreach ($userSystem->getSettingFields() as $field) {

                if ($field->getKey() === 'list') {
                    natcasesort($distributionList);

                    $form
                        ->addSelect($field->getKey(), $field->getLabel(), $distributionList)
                        ->setPrompt('Choose list')
                        ->setOption('description', $field->getDescription());

                    if ($field->getValue() !== '' && $field->getValue() !== NULL) {
                        $form[$field->getKey()]
                            ->setDefaultValue($field->getValue());
                    }

                } else {
                    switch ($field->getType()) {
                        case 'password':
                            $form
                                ->addPassword($field->getKey(), $field->getLabel());

                            if ($field->getValue()) {
                                $message = 'Password exists.';
                            } else {
                                $message = 'Password not exists.';
                            }

                            if ($field->getDescription() != '') {
                                $form[$field->getKey()]
                                    ->setOption('description', $message . ' - ' . $field->getDescription());
                            } else {
                                $form[$field->getKey()]
                                    ->setOption('description', $message);
                            }
                            break;
                        case 'checkbox':
                            $form
                                ->addCheckbox($field->getKey(), $field->getLabel());
                            break;
                        default:
                            $form
                                ->addText($field->getKey(), $field->getLabel())
                                ->setType($field->getType());

                            if ($field->getDescription() != '') {
                                $form[$field->getKey()]
                                    ->setOption('description', $field->getDescription());
                            }
                            break;
                    }

                    $form[$field->getKey()]
                        ->setDefaultValue($field->getValue());
                }

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

        $form->setRenderer(new BootstrapV4Renderer);

        return $form;
    }

}