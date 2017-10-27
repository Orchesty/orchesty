<?php declare(strict_types=1);

namespace App\Forms;

use Nette\Application\UI\Form;

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/23/17
 * Time: 2:31 PM
 */
class LoginFormFactory
{

    /**
     * @return Form
     */
    public function create(): Form
    {
        $form = new Form();

        $form
            ->addText('user_id', 'User ID')
            ->setHtmlAttribute('placeholder', 'Clever Monitor user ID');

        $form
            ->addSubmit('login', 'Login');

        return $form;
    }

}