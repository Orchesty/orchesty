<?php declare(strict_types=1);

namespace App\Forms;

use Nette\Application\UI\Form;

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/23/17
 * Time: 2:31 PM
 */
class PublishFormFactory
{

    /**
     * @return Form
     */
    public function create(): Form
    {
        $form = new Form();

        $form
            ->addText('event', 'Event')
            ->setHtmlAttribute('placeholder', 'my_event');

        $form
            ->addText('content', 'Content')
            ->setHtmlAttribute('placeholder', 'My message');

        $form
            ->addText('groups', 'Group')
            ->setHtmlAttribute('placeholder', 'groupA,groupB,...');

        $form
            ->addSubmit('publish', 'Publish message');

        return $form;
    }

}