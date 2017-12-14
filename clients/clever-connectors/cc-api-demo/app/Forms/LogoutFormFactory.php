<?php
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 25.10.17
 * Time: 10:56
 */

namespace App\Forms;

use AlesWita\FormRenderer\BootstrapV4Renderer;
use Nette\Application\UI\Form;

/**
 * Class LogoutFormFactory
 *
 * @package App\Forms
 */
class LogoutFormFactory
{

    /**
     * @return Form
     */
    public function create(): Form
    {
        $form = new Form();

        $form
            ->addSubmit('logout', 'Logout');

        $form->setRenderer(new BootstrapV4Renderer);

        return $form;
    }

}