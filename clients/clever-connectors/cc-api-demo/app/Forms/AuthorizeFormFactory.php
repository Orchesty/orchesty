<?php
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 27.10.17
 * Time: 8:20
 */

namespace App\Forms;

use Nette\Application\UI\Form;

/**
 * Class AuthorizeFormFactory
 *
 * @package App\Forms
 */
class AuthorizeFormFactory
{
    /**
     * @return Form
     */
    public function create(): Form
    {
        $form = new Form();

        $form
            ->addSubmit('authorize', 'Authorize');

        return $form;
    }

}