<?php
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 25.10.17
 * Time: 8:33
 */

namespace App\Presenters;

use App\Forms\AuthorizationSettingFormFactory;
use App\Forms\AuthorizationSettingGeneratorFactory;
use App\Forms\LoginFormFactory;
use App\Forms\LogoutFormFactory;
use App\Forms\SystemActionFormFactory;
use CcApi\ApiEntity\UserSystem;
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
     * @var AuthorizationSettingFormFactory
     */
    private $authorizationSettingFormFactory;

    /**
     * @var AuthorizationSettingGeneratorFactory
     */
    private $authorizeGeneratorFactory;

    /**
     * @var UserSystem|null
     */
    private $userSystem;

    /**
     * HomepagePresenter constructor.
     *
     * @param ConnectorManager                     $connectorManager
     * @param LoginFormFactory                     $loginFormFactory
     * @param LogoutFormFactory                    $logoutFormFactory
     * @param SystemActionFormFactory              $systemActionFormFactory
     * @param AuthorizationSettingFormFactory      $authorizationSettingFormFactory
     * @param AuthorizationSettingGeneratorFactory $authorizationSettingGeneratorFactory
     */
    public function __construct(ConnectorManager $connectorManager, LoginFormFactory $loginFormFactory,
                                LogoutFormFactory $logoutFormFactory, SystemActionFormFactory $systemActionFormFactory,
                                AuthorizationSettingFormFactory $authorizationSettingFormFactory,
                                AuthorizationSettingGeneratorFactory $authorizationSettingGeneratorFactory)
    {
        parent::__construct();
        $this->connectorManager                = $connectorManager;
        $this->loginFormFactory                = $loginFormFactory;
        $this->logoutFormFactory               = $logoutFormFactory;
        $this->systemActionFormFactory         = $systemActionFormFactory;
        $this->authorizationSettingFormFactory = $authorizationSettingFormFactory;
        $this->authorizeGeneratorFactory       = $authorizationSettingGeneratorFactory;
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
    public function createComponentAuthorizationSettingForm()
    {
        $form              = $this->authorizationSettingFormFactory->create();
        $form->onSuccess[] = [$this, 'processAuthorizationSetting'];

        return $form;
    }

    /**
     * @param Form $form
     */
    public function processAuthorizationSetting(Form $form)
    {
        $data = $form->getHttpData();

        $this->userSystem               = $this->connectorManager->getUserSystem($this->userId, $data['system_key']);
        $this->template->userSystemData = TRUE;

        $this->redrawControl('systemData');
    }

    /**
     * @return \Nette\Application\UI\Form
     */
    public function createComponentAuthorizationSettingGeneratorForm()
    {
        if ($this->userSystem) {
            $form = $this->authorizeGeneratorFactory->create($this->userSystem);

            $form['save_password']->onClick[]     = [$this, 'processPassword'];
            $form['save_auth_setting']->onClick[] = [$this, 'processAuthorizationSettingGenerator'];
        } else {
            $form = $this->authorizeGeneratorFactory->create();
        }

        return $form;
    }

    public function processPassword(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        // save password and refresh form
    }

    /**
     * @param SubmitButton $button
     */
    public function processAuthorizationSettingGenerator(SubmitButton $button)
    {
        // save setting without password
    }

    /**
     *
     */
    public function actionDefault()
    {
        $this->template->userId         = $this->userId ?? '';
        $this->template->token          = $this->token ?? '';
        $this->template->userSystemData = FALSE;

        if ($this->user->isLoggedIn()) {
            $userSystems = $this->connectorManager->getAllUserSystems($this->userId);

            $this->template->installedSystems = $userSystems;
        }
    }

}