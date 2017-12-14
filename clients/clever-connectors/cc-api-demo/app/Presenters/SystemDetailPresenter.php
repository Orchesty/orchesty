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
use App\Model\DistributionList;
use CcApi\ApiEntity\UserSystem;
use CcApi\Connector\ConnectorManager;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Tracy\Debugger;
use WebChemistry\Forms\Controls\Multiplier;

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
     * @var DistributionList
     */
    private $distributionList;

    /**
     * HomepagePresenter constructor.
     *
     * @param ConnectorManager                     $connectorManager
     * @param AuthorizationSettingGeneratorFactory $authorizationSettingGeneratorFactory
     * @param SwitchTokenFormFactory               $switchTokenFormFactory
     * @param DistributionList                     $distributionList
     */
    public function __construct(ConnectorManager $connectorManager,
                                AuthorizationSettingGeneratorFactory $authorizationSettingGeneratorFactory,
                                SwitchTokenFormFactory $switchTokenFormFactory, DistributionList $distributionList)
    {
        parent::__construct();
        $this->connectorManager                     = $connectorManager;
        $this->authorizationSettingGeneratorFactory = $authorizationSettingGeneratorFactory;
        $this->switchTokenFormFactory               = $switchTokenFormFactory;
        $this->distributionList                     = $distributionList;
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
     * @param $systemKey
     *
     * @return Form|null
     */
    private function getForm($systemKey): ?Form
    {
        $distributionList = $this->distributionList->getListsForSelect($this->userSystem->getToken(), $this->userId);
        $form             = $this->authorizationSettingGeneratorFactory->create($this->userSystem, $distributionList);

        switch ($systemKey) {
            case 'airtable':

                /** @var Multiplier $multiplier */
                $multiplier = $form->addMultiplier('multiplier', function (Container $container, Form $form) {

                    $container
                        ->addText('table_id', 'Table ID')
                        ->setRequired('The field key id required, please fill it.');
                    $container
                        ->addButton('redirect', 'Detail');
                });

                $multiplier->addCreateButton('Add table');
                $multiplier->addRemoveButton('Remove');

                $form->setCurrentGroup();

                break;
        }

        return $form;
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
        $form = $this->getForm($this->userSystem->getKey());

        $form->onSuccess[] = [$this, 'processAuthorizationSettingGenerator'];

        return $form;
    }

    /**
     * @param Form $form
     *
     * @throws \Nette\Application\AbortException
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

        $this->redirect('SystemDetail:', ['systemKey' => $this->userSystem->getKey()]);
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
     *
     * @throws \Nette\Application\AbortException
     */
    public function processSwitchToken(Form $form)
    {
        $data = $form->getValues();

        $this->connectorManager->switchUserSystemToken($this->userId, $this->userSystem->getKey(), $data['token']);

        $this->flashMessage('Token was switched.');
        $this->redirect('SystemDetail:', ['systemKey' => $this->userSystem->getKey()]);
    }

}