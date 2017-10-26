<?php
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 25.10.17
 * Time: 14:33
 */

namespace App\Forms;

use Nette\Application\UI\Form;

/**
 * Class AuthorizedFormFactory
 *
 * @package App\Forms
 */
class AuthorizationSettingFormFactory
{

    /**
     * @return Form
     */
    public function create(): Form
    {
        $form = new Form();
        $form->getElementPrototype()->appendAttribute('class', 'ajax');

        $form
            ->addSubmit('authorize_setting', 'Authorize setting');

        return $form;
    }

}