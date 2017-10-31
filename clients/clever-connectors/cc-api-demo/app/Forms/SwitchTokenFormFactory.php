<?php
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 27.10.17
 * Time: 13:20
 */

namespace App\Forms;

use Nette\Application\UI\Form;

/**
 * Class SwitchTokenFormFactory
 *
 * @package App\Forms
 */
class SwitchTokenFormFactory
{

    /**
     * @return Form
     */
    public function create(): Form
    {
        $form = new Form();

        $form
            ->addText('token', 'Token')
            ->setHtmlAttribute('placeholder', 'Clever Monitor new token');

        $form
            ->addSubmit('switch_token', 'Switch token');

        return $form;
    }

}