<?php

namespace App\Presenters;

use Nette;
use Nette\Security\Identity;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{

    /**
     * @var string
     */
    protected $userId;

    /**
     * @var string
     */
    protected $token;

    /**
     *
     */
    public function startup()
    {
        parent::startup();
        if ($this->user->isLoggedIn()) {
            /** @var Identity $identity */
            $identity = $this->getUser()->getIdentity();

            $this->userId = $identity->getId();
            $this->token  = $identity->getData()['token'] ?? '';
        }
    }

    /**
     *
     */
    public function beforeRender()
    {
        parent::beforeRender();
        $this->template->menuItems = [
            'Systems'      => 'Homepage:',
            'User Systems' => 'System:',
            'Stream'       => 'Stream:',
        ];
        $this->template->host      = $this->context->getParameters()['ws']['host'];
    }

}
