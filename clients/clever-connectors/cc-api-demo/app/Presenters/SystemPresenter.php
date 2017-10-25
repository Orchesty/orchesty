<?php
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 25.10.17
 * Time: 8:33
 */

namespace App\Presenters;

use App\Forms\LoginFormFactory;
use App\Forms\LogoutFormFactory;
use CcApi\Connector\ConnectorManager;
use Nette\Forms\Form;
use Nette\Security\Identity;

/**
 * Class SystemPresenter
 *
 * @package App\Presenters
 */
class SystemPresenter extends BasePresenter
{

    /**
     * @var ConnectorManager
     */
    private $connectorManager;

    /**
     * @var LoginFormFactory
     */
    private $loginFormFactory;
    /**
     * @var LogoutFormFactory
     */
    private $logoutFormFactory;

    /**
     * HomepagePresenter constructor.
     *
     * @param ConnectorManager  $connectorManager
     * @param LoginFormFactory  $loginFormFactory
     * @param LogoutFormFactory $logoutFormFactory
     */
    public function __construct(ConnectorManager $connectorManager, LoginFormFactory $loginFormFactory,
                                LogoutFormFactory $logoutFormFactory)
    {
        parent::__construct();
        $this->connectorManager  = $connectorManager;
        $this->loginFormFactory  = $loginFormFactory;
        $this->logoutFormFactory = $logoutFormFactory;
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
        $this->user->login($data['user_id'], $data['token']);

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

    /**
     *
     */
    public function renderDefault()
    {
        /** @var Identity $identity */
        $identity = $this->getUser()->getIdentity();

        $this->template->userId = $identity->getId();
        $this->template->token  = $identity->getData()['token'] ?? '';
    }

}