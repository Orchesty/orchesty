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
class SyncFormFactory
{

    /**
     * @return Form
     */
    public function create(): Form
    {
        $form = new Form();
        $form->getElementPrototype()->appendAttribute('class', 'ajax');

        $form
            ->addSubmit('start_sync', 'Sync');

        return $form;
    }

}