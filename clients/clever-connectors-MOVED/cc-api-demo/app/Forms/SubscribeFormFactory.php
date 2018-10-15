<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 1.11.17
 * Time: 9:55
 */

namespace App\Forms;

use AlesWita\FormRenderer\BootstrapV4Renderer;
use Nette\Application\UI\Form;

/**
 * Class SubscribeForm
 *
 * @package App\Forms
 */
class SubscribeFormFactory
{

    /**
     * @return Form
     */
    public function create(): Form
    {
        $form = new Form();

        $form
            ->addText('user_id', 'User')
            ->setRequired('The user ID is required.')
            ->setHtmlAttribute('placeholder', 'User ID');

        $form
            ->addText('groups', 'Group')
            ->setRequired('The group(s) is required.')
            ->setHtmlAttribute('placeholder', 'groupA,groupB,...');

        $form
            ->addSubmit('subscribe', 'Subscribe');

        $form->setRenderer(new BootstrapV4Renderer);

        return $form;
    }

}