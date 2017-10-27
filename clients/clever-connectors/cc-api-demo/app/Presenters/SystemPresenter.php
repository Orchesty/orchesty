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
use App\Forms\AuthorizeFormFactory;
use App\Forms\LoginFormFactory;
use App\Forms\LogoutFormFactory;
use App\Forms\SwitchTokenFormFactory;
use App\Forms\SyncFormFactory;
use App\Forms\SystemActionFormFactory;
use CcApi\ApiEntity\UserSystem;
use CcApi\Connector\ConnectorManager;
use Nette\Forms\Controls\SubmitButton;
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
     * @var AuthorizeFormFactory
     */
    private $authorizeFormFactory;

    /**
     * @var SyncFormFactory
     */
    private $syncFormFactory;

    /**
     * @var SwitchTokenFormFactory
     */
    private $switchTokenFormFactory;

    /**
     * HomepagePresenter constructor.
     *
     * @param ConnectorManager                     $connectorManager
     * @param LoginFormFactory                     $loginFormFactory
     * @param LogoutFormFactory                    $logoutFormFactory
     * @param SystemActionFormFactory              $systemActionFormFactory
     * @param AuthorizationSettingFormFactory      $authorizationSettingFormFactory
     * @param AuthorizationSettingGeneratorFactory $authorizationSettingGeneratorFactory
     * @param AuthorizeFormFactory                 $authorizeFormFactory
     * @param SyncFormFactory                      $syncFormFactory
     * @param SwitchTokenFormFactory               $switchTokenFormFactory
     */
    public function __construct(ConnectorManager $connectorManager, LoginFormFactory $loginFormFactory,
                                LogoutFormFactory $logoutFormFactory, SystemActionFormFactory $systemActionFormFactory,
                                AuthorizationSettingFormFactory $authorizationSettingFormFactory,
                                AuthorizationSettingGeneratorFactory $authorizationSettingGeneratorFactory,
                                AuthorizeFormFactory $authorizeFormFactory, SyncFormFactory $syncFormFactory,
                                SwitchTokenFormFactory $switchTokenFormFactory)
    {
        parent::__construct();
        $this->connectorManager                = $connectorManager;
        $this->loginFormFactory                = $loginFormFactory;
        $this->logoutFormFactory               = $logoutFormFactory;
        $this->systemActionFormFactory         = $systemActionFormFactory;
        $this->authorizationSettingFormFactory = $authorizationSettingFormFactory;
        $this->authorizeGeneratorFactory       = $authorizationSettingGeneratorFactory;
        $this->authorizeFormFactory            = $authorizeFormFactory;
        $this->syncFormFactory                 = $syncFormFactory;
        $this->switchTokenFormFactory          = $switchTokenFormFactory;
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

        $this->connectorManager->installUserSystem($this->userId, $data['systems'], $data['token']);

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
        } else {
            $form = $this->authorizeGeneratorFactory->create();
        }

        $form->onSuccess[] = [$this, 'processAuthorizationSettingGenerator'];

        return $form;
    }

    /**
     * @param Form $tmpForm
     */
    public function processAuthorizationSettingGenerator(Form $tmpForm)
    {
        $this->userSystem = $this->connectorManager->getUserSystem(
            $this->userId,
            $tmpForm->getHttpData()['system_key']
        );
        $form             = $this->authorizeGeneratorFactory->create($this->userSystem);
        $form->setDefaults($tmpForm->getHttpData());
        $data = $form->getValues(TRUE);

        if (isset($data['password'])) {
            $this->connectorManager->setUserSystemPassword($this->userId, $data['system_key'], $data['password']);

            unset($data['password']);
        }

        $this->connectorManager->saveUserSystemSetting($this->userId, $data['system_key'], $data);

        $this->redirect('System:');
    }

    /**
     * @return SystemPresenter|\Nette\Application\UI\Form
     */
    public function createComponentAuthorizeForm()
    {
        $form = $this->authorizeFormFactory->create();

        $form->onSuccess[] = [$this, 'processAuthorize'];

        return $form;
    }

    /**
     * @param Form $form
     */
    public function processAuthorize(Form $form)
    {
        $data = $form->getHttpData();

        $this->connectorManager->authorizeUserSystem(
            $this->userId,
            $data['system_key'],
            $this->getHttpRequest()->getUrl()->getAbsoluteUrl()
        );
    }

    /**
     * @return \Nette\Application\UI\Form
     */
    public function createComponentSyncForm()
    {
        $form = $this->syncFormFactory->create();

        $form->onSuccess[] = [$this, 'processSync'];

        return $form;
    }

    /**
     * @param Form $form
     */
    public function processSync(Form $form)
    {
        $data = $form->getHttpData();

        $this->connectorManager->synchronizeUserSystem($this->userId, $data['system_key']);

        $this->sendJson(['start_sync' => TRUE]);
    }

    /**
     * @return \Nette\Application\UI\Form
     */
    public function createComponentSwitchTokenForm()
    {
        $systems = $this->connectorManager->getAllUserSystems($this->userId);

        $items = [];
        foreach ($systems as $system) {
            $items[$system->getKey()] = $system->getName();
        }

        $form = $this->switchTokenFormFactory->create($items);

        $form->onSuccess[] = [$this, 'processSwitchToken'];

        return $form;
    }

    /**
     * @param Form $form
     */
    public function processSwitchToken(Form $form)
    {
        $data = $form->getValues();

        $this->connectorManager->switchUserSystemToken($this->userId, $data['systems'], $data['token']);

        $this->redirect('System:');
    }

    /**
     *
     */
    public function actionDefault()
    {
        $this->template->userId         = $this->userId ?? '';
        $this->template->userSystemData = FALSE;

        if ($this->user->isLoggedIn()) {
            $userSystems = $this->connectorManager->getAllUserSystems($this->userId);

            $this->template->installedSystems = $userSystems;
        }
    }

}