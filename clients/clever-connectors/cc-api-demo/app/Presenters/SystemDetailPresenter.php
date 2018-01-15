<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/31/17
 * Time: 11:12 AM
 */

namespace App\Presenters;

use App\Forms\AuthorizationSettingGeneratorFactory;
use App\Forms\SendEventFormFactory;
use App\Forms\SwitchTokenFormFactory;
use App\Model\DistributionList;
use CcApi\ApiEntity\Subscriber;
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
     * @var SendEventFormFactory
     */
    private $sendEventFormFactory;

    /**
     * @var DistributionList
     */
    private $distributionList;

    /**
     * @var array
     */
    private $actions = [];

    /**
     * @var array
     */
    private $list = [];

    /**
     * HomepagePresenter constructor.
     *
     * @param ConnectorManager                     $connectorManager
     * @param AuthorizationSettingGeneratorFactory $authorizationSettingGeneratorFactory
     * @param SwitchTokenFormFactory               $switchTokenFormFactory
     * @param SendEventFormFactory                 $sendEventFormFactory
     * @param DistributionList                     $distributionList
     */
    public function __construct(ConnectorManager $connectorManager,
                                AuthorizationSettingGeneratorFactory $authorizationSettingGeneratorFactory,
                                SwitchTokenFormFactory $switchTokenFormFactory,
                                SendEventFormFactory $sendEventFormFactory,
                                DistributionList $distributionList)
    {
        parent::__construct();
        $this->connectorManager                     = $connectorManager;
        $this->authorizationSettingGeneratorFactory = $authorizationSettingGeneratorFactory;
        $this->switchTokenFormFactory               = $switchTokenFormFactory;
        $this->sendEventFormFactory                 = $sendEventFormFactory;
        $this->distributionList                     = $distributionList;
    }

    /**
     *
     */
    public function startup()
    {
        parent::startup();

        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('System:default');
        }
    }

    /**
     * @param $systemKey
     *
     * @throws \CcApi\Connector\Exception\ConnectorException
     * @throws \Nette\Application\UI\InvalidLinkException
     */
    public function actionDefault($systemKey): void
    {
        if ($systemKey) {
            $this->userSystem                  = $this->connectorManager->getUserSystem($this->userId, $systemKey);
            $this->list                        = $this->distributionList->getListsForSelect(
                $this->userSystem->getToken(),
                $this->userId
            );
            $this->template->additionalSetting = $this->createLink($this->userSystem);
            $this->template->system            = $this->userSystem;

            foreach ($this->userSystem->getSettingFields() as $settingField) {
                if ($settingField->getKey() === 'eventCreate' && $settingField->getValue() === TRUE) {
                    $this->actions[SendEventFormFactory::CREATE] = 'Create';
                }

                if ($settingField->getKey() === 'eventUnsubscribe' && $settingField->getValue() === TRUE) {
                    $this->actions[SendEventFormFactory::UNSUBSCRIBE] = 'UnSubscribe';
                }

                if ($settingField->getKey() === 'eventHardBounce' && $settingField->getValue() === TRUE) {
                    $this->actions[SendEventFormFactory::HARD_BOUNCE] = 'Hard Bounce';
                }
            }

            $this->template->hasActions = !empty($this->actions);
        } else {
            $this->template->system = NULL;
        }
    }

    /**
     * @param UserSystem $userSystem
     *
     * @return string
     * @throws \Nette\Application\UI\InvalidLinkException
     */
    private function createLink(UserSystem $userSystem): string
    {
        switch ($userSystem->getKey()) {
            case 'wisepops':
                return $this->link('WisePop:', ['systemKey' => $this->userSystem->getKey()]);
                break;
            case 'airtable':
                return $this->link('AirTable:', ['systemKey' => $this->userSystem->getKey()]);
                break;
            case 'facebookleads':
                return $this->link('FacebookLeads:', ['systemKey' => $this->userSystem->getKey()]);
                break;
            default:
                return '';
        }
    }

    /**
     *
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
     * @throws \CcApi\Connector\Exception\ConnectorException
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
        $form = $form = $this->authorizationSettingGeneratorFactory->create($this->userSystem, $this->list);

        $form->onSuccess[] = [$this, 'processAuthorizationSettingGenerator'];

        return $form;
    }

    /**
     * @param Form $form
     *
     * @throws \Nette\Application\AbortException
     * @throws \CcApi\Connector\Exception\ConnectorException
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
     * @throws \CcApi\Connector\Exception\ConnectorException
     */
    public function processSwitchToken(Form $form)
    {
        $data = $form->getValues();

        $this->connectorManager->switchUserSystemToken($this->userId, $this->userSystem->getKey(), $data['token']);

        $this->flashMessage('Token was switched.');
        $this->redirect('SystemDetail:', ['systemKey' => $this->userSystem->getKey()]);
    }

    /**
     * @return Form
     */
    public function createComponentSendEventForm(): Form
    {
        $form = $this->sendEventFormFactory->create($this->userSystem, $this->list, $this->actions);

        $form->onSuccess[] = [$this, 'processSendEvent'];

        return $form;
    }

    /**
     * @param Form $form
     */
    public function processSendEvent(Form $form, array $values): void
    {
        $subscriber = (new Subscriber())
            ->setEmail($values['email'])
            ->setLastName($values['last_name'])
            ->setFirstName($values['first_name'])
            ->setForeignId($values['_foreign_id'])
            ->setLists([$values['lists']]);

        switch ($values['type']) {
            case SendEventFormFactory::CREATE:
                $this->connectorManager->subscribe($this->userId, $subscriber);
                break;

            case SendEventFormFactory::UNSUBSCRIBE:
                $this->connectorManager->unSubscribe($this->userId, $subscriber);
                break;

            case SendEventFormFactory::HARD_BOUNCE:
                $this->connectorManager->hardBounce($this->userId, $subscriber);
                break;
        }
    }

}