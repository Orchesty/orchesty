<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/31/17
 * Time: 10:00 AM
 */

namespace App\Forms;

use Nette\Application\UI\Form;

/**
 * Class SystemFilterFormFactory
 *
 * @package App\Forms
 */
class SystemFilterFormFactory
{

    /**
     * @return Form
     */
    public function create(): Form
    {
        $form = new Form();

        $form
            ->addText('group', 'Group')
            ->setHtmlAttribute('placeholder', 'System group');

        $form->addText('user_id', 'User ID')
            ->setHtmlAttribute('placeholder', 'Clever Monitor user ID');

        $form
            ->addSubmit('filter', 'Filter');

        return $form;
    }

}