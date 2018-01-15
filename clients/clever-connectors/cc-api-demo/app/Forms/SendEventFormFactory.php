<?php declare(strict_types=1);

namespace App\Forms;

use AlesWita\FormRenderer\BootstrapV4Renderer;
use CcApi\ApiEntity\UserSystem;
use DateTime;
use Nette\Application\UI\Form;

/**
 * Class SendEventFormFactory
 *
 * @package App\Forms
 */
class SendEventFormFactory
{

    public const CREATE      = 'create';
    public const UNSUBSCRIBE = 'unsubscribe';
    public const HARD_BOUNCE = 'hard_bounce';

    /**
     * @param UserSystem $userSystem
     * @param array      $lists
     * @param array      $actions
     *
     * @return Form
     */
    public function create(UserSystem $userSystem, array $lists, array $actions): Form
    {
        $form = new Form();

        $form
            ->addSelect('type', 'Type', $actions)
            ->setPrompt('Choose Type')
            ->setRequired('Type is required, please fill it.')
            ->addCondition($form::EQUAL, self::CREATE)
            ->toggle('email')
            ->toggle('first-name')
            ->toggle('last-name')
            ->endCondition()
            ->addCondition($form::IS_IN, [self::UNSUBSCRIBE, self::HARD_BOUNCE])
            ->toggle('foreign-id');

        $form
            ->addSelect('lists', 'Distribution list', $lists)
            ->setRequired('Distribution list is required, please fill it.');

        $form
            ->addText('email', 'E-mail')
            ->setOption('id', 'email');

        $form
            ->addText('first_name', 'First Name')
            ->setOption('id', 'first-name');

        $form
            ->addText('last_name', 'Last Name')
            ->setOption('id', 'last-name');

        $form
            ->addText('_foreign_id', 'ID')
            ->setOption('id', 'foreign-id');

        $form->addSubmit('submit', 'Send');

        $form->setRenderer(new BootstrapV4Renderer());

        $date = (new DateTime())->format('Y-m-d-H-i');
        $form->setDefaults([
            'lists'       => array_search($userSystem->getName(), $lists, TRUE) ?: array_keys($lists)[0],
            'email'       => sprintf('%s@%s.com', $date, $userSystem->getKey()),
            'last_name'   => $date,
            'first_name'  => $date,
            '_foreign_id' => 'ID',
        ]);

        return $form;
    }

}