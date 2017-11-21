<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/31/17
 * Time: 11:12 AM
 */

namespace App\Presenters;

use App\Forms\AuthorizationSettingGeneratorFactory;
use App\Forms\SwitchTokenFormFactory;
use CcApi\ApiEntity\UserSystem;
use CcApi\Connector\ConnectorManager;
use Nette\Application\UI\Form;

/**
 * Class SystemDetailPresenter
 *
 * @package App\Presenters
 */
class SystemDetailPresenter extends BasePresenter
{

    /**
     * @var ConnectorManager
     */
    private $connectorManager;

    /**
     * @var UserSystem
     */
    private $userSystem;

    /**
     * @var AuthorizationSettingGeneratorFactory
     */
    private $authorizationSettingGeneratorFactory;
    /**
     * @var SwitchTokenFormFactory
     */
    private $switchTokenFormFactory;

    /**
     * HomepagePresenter constructor.
     *
     * @param ConnectorManager                     $connectorManager
     * @param AuthorizationSettingGeneratorFactory $authorizationSettingGeneratorFactory
     * @param SwitchTokenFormFactory               $switchTokenFormFactory
     */
    public function __construct(ConnectorManager $connectorManager,
                                AuthorizationSettingGeneratorFactory $authorizationSettingGeneratorFactory,
                                SwitchTokenFormFactory $switchTokenFormFactory)
    {
        parent::__construct();
        $this->connectorManager                     = $connectorManager;
        $this->authorizationSettingGeneratorFactory = $authorizationSettingGeneratorFactory;
        $this->switchTokenFormFactory               = $switchTokenFormFactory;
    }

    /**
     * @param $systemKey
     */
    public function actionDefault($systemKey): void
    {
        if ($systemKey) {
            $this->userSystem = $this->connectorManager->getUserSystem($this->userId, $systemKey);

            $this->template->system = $this->userSystem;
        } else {
            $this->template->system = NULL;
        }
    }

    /**
     */
    public function handleAuthorize()
    {
        $this->connectorManager->authorizeUserSystem(
            $this->userId,
            $this->userSystem->getKey(),
            sprintf(
                '%s%s?systemKey=%s',
                $this->getHttpRequest()->getUrl()->getHostUrl(),
                $this->getHttpRequest()->getUrl()->getPath(),
                $this->getHttpRequest()->getUrl()->getQueryParameter('systemKey')
            )
        );
    }

    /**
     *
     */
    public function handleStartSync()
    {
        $count = $this->connectorManager->synchronizeUserSystem($this->userId, $this->userSystem->getKey());

        $this->flashMessage(sprintf('It was started %s synchronizations.', $count));
        $this->redrawControl('flashMessages');
    }

    /**
     * @return \Nette\Application\UI\Form
     */
    public function createComponentAuthorizationSettingGeneratorForm(): Form
    {
        $form = $this->authorizationSettingGeneratorFactory->create($this->userSystem);

        $form->onSuccess[] = [$this, 'processAuthorizationSettingGenerator'];

        return $form;
    }

    /**
     * @param Form $form
     */
    public function processAuthorizationSettingGenerator(Form $form): void
    {
        $data = $form->getValues(TRUE);

        $password = (isset($data['password']) && $data['password'] !== '');

        if ($password) {
            $this->connectorManager->setUserSystemPassword($this->userId, $this->userSystem->getKey(),
                $data['password']);

            unset($data['password']);
        }

        $this->connectorManager->saveUserSystemSetting($this->userId, $this->userSystem->getKey(), $data);

        $this->flashMessage('Setting was saved.');

        $this->redirect('//SystemDetail:', ['systemKey' => $this->userSystem->getKey()]);
    }

    /**
     * @return \Nette\Application\UI\Form
     */
    public function createComponentSwitchTokenForm()
    {
        $form = $this->switchTokenFormFactory->create();

        $form->onSuccess[] = [$this, 'processSwitchToken'];

        return $form;
    }

    /**
     * @param Form $form
     */
    public function processSwitchToken(Form $form)
    {
        $data = $form->getValues();

        $this->connectorManager->switchUserSystemToken($this->userId, $this->userSystem->getKey(), $data['token']);

        $this->flashMessage('Token was switched.');
        $this->redirect('//SystemDetail:', ['systemKey' => $this->userSystem->getKey()]);
    }

}