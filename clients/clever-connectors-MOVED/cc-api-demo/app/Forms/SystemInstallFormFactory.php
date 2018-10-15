<?php
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 25.10.17
 * Time: 12:10
 */

namespace App\Forms;

use AlesWita\FormRenderer\BootstrapV4Renderer;
use CcApi\ApiEntity\System;
use Nette\Application\UI\Form;

/**
 * Class SystemActionFormFactory
 *
 * @package App\Forms
 */
class SystemInstallFormFactory
{

    /**
     * @param array|System[] $systems
     *
     * @return Form
     */
    public function create(array $systems): Form
    {
        $form = new Form();

        $form
            ->addSelect('systems', 'Systems', $systems)
            ->setPrompt('Choose system')
            ->setRequired('Choose any system.');

        $form->addText('token', 'Token')
            ->setRequired('Required field.')
            ->setHtmlAttribute('placeholder', 'Clever Monitor token');

        $form
            ->addSubmit('install', 'Install');

        $form->setRenderer(new BootstrapV4Renderer);

        return $form;
    }

}