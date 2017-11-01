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
            ->setRequired('The event name is required.')
            ->setHtmlAttribute('placeholder', 'my_event')
            ->setDefaultValue('demo_event');

        $form
            ->addText('content', 'Content')
            ->setRequired('The content is required')
            ->setHtmlAttribute('placeholder', 'My message');

        $form
            ->addText('groups', 'Group')
            ->setRequired('The group(s) is required.')
            ->setHtmlAttribute('placeholder', 'groupA,groupB,...');

        $form
            ->addSubmit('publish', 'Publish message');

        return $form;
    }

}