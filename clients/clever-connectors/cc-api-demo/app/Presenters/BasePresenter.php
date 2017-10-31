<?php

namespace App\Presenters;

use App\Forms\LoginFormFactory;
use App\Forms\LogoutFormFactory;
use Nette;
use Nette\Application\UI\Form;
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
     * @var LoginFormFactory @inject
     */
    public $loginFormFactory;

    /**
     * @var LogoutFormFactory @inject
     */
    public $logoutFormFactory;

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

        $this->template->userId = $this->userId ?? '';
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

    /**
     * @return \Nette\Application\UI\Form
     */
    public function createComponentLoginForm()
    {
        $form              = $this->loginFormFactory->create();
        $form->onSuccess[] = [$this, 'processLogin'];

        return $form;
    }

    /**
     * @param Form $form
     */
    public function processLogin(Form $form)
    {
        $data = $form->getValues();
        $this->user->login($data['user_id'], '');

        $form->reset();
        $this->redirect('System:');
    }

    /**
     * @return \Nette\Application\UI\Form
     */
    public function createComponentLogoutForm()
    {
        $form              = $this->logoutFormFactory->create();
        $form->onSuccess[] = [$this, 'processLogout'];

        return $form;
    }

    /**
     *
     */
    public function processLogout()
    {
        $this->user->logout();
        $this->redirect('System:');
    }

}
