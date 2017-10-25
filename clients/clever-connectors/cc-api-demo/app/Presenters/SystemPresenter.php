<?php
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 25.10.17
 * Time: 8:33
 */

namespace App\Presenters;

use App\Forms\AuthorizeFormFactory;
use App\Forms\LoginFormFactory;
use App\Forms\LogoutFormFactory;
use App\Forms\SystemActionFormFactory;
use CcApi\Connector\ConnectorManager;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Form;

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
     * @var SystemActionFormFactory
     */
    private $systemActionFormFactory;

    /**
     * @var AuthorizeFormFactory
     */
    private $authorizedFormFactory;

    /**
     * HomepagePresenter constructor.
     *
     * @param ConnectorManager        $connectorManager
     * @param LoginFormFactory        $loginFormFactory
     * @param LogoutFormFactory       $logoutFormFactory
     * @param SystemActionFormFactory $systemActionFormFactory
     * @param AuthorizeFormFactory    $authorizedFormFactory
     */
    public function __construct(ConnectorManager $connectorManager, LoginFormFactory $loginFormFactory,
                                LogoutFormFactory $logoutFormFactory, SystemActionFormFactory $systemActionFormFactory,
                                AuthorizeFormFactory $authorizedFormFactory)
    {
        parent::__construct();
        $this->connectorManager        = $connectorManager;
        $this->loginFormFactory        = $loginFormFactory;
        $this->logoutFormFactory       = $logoutFormFactory;
        $this->systemActionFormFactory = $systemActionFormFactory;
        $this->authorizedFormFactory   = $authorizedFormFactory;
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
     * @return \Nette\Application\UI\Form
     */
    public function createComponentSystemActionForm()
    {
        $systems = $this->connectorManager->getAllSystems();

        $items = [];
        foreach ($systems as $system) {
            $items[$system->getKey()] = $system->getName();
        }

        $form                         = $this->systemActionFormFactory->create($items);
        $form['install']->onClick[]   = [$this, 'processInstall'];
        $form['uninstall']->onClick[] = [$this, 'processUninstall'];

        return $form;
    }

    /**
     * @param SubmitButton $button
     */
    public function processInstall(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $this->connectorManager->installUserSystem($this->userId, $data['systems'], $this->token);

        $this->redirect('System:');
    }

    /**
     * @param SubmitButton $button
     */
    public function processUninstall(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $this->connectorManager->uninstallUserSystem($this->userId, $data['systems']);

        $this->redirect('System:');
    }

    /**
     * @return \Nette\Application\UI\Form
     */
    public function createComponentAuthorizeForm()
    {
        $form              = $this->authorizedFormFactory->create();
        $form->onSuccess[] = [$this, 'processAuthorize'];

        return $form;
    }

    /**
     * @param Form $form
     */
    public function processAuthorize(Form $form)
    {
        $data = $form->getHttpData();

        $userSystem = $this->connectorManager->getUserSystem($this->userId, $data['system_key']);

        $this->template->userSystem = $userSystem;

        $this->redrawControl('systemData');
    }

    /**
     *
     */
    public function actionDefault()
    {
        $this->template->userId     = $this->userId ?? '';
        $this->template->token      = $this->token ?? '';
        $this->template->userSystem = NULL;

        if ($this->user->isLoggedIn()) {
            $userSystems = $this->connectorManager->getAllUserSystems($this->userId);

            $this->template->installedSystems = $userSystems;
        }
    }

}