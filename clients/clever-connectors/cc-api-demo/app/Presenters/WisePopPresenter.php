<?php
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 12/14/17
 * Time: 3:57 PM
 */

namespace App\Presenters;

use App\Form\Systems\WisePopFormFactory;
use App\Model\DistributionList;
use CcApi\ApiEntity\UserSystem;
use CcApi\Connector\ConnectorManager;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;

/**
 * Class WisePopPresenter
 *
 * @package App\Presenters
 */
class WisePopPresenter extends BasePresenter
{

    /**
     * @var WisePopFormFactory
     */
    private $wisePopFormFactory;

    /**
     * @var ConnectorManager
     */
    private $connectorManager;

    /**
     * @var DistributionList
     */
    private $distributionList;

    /**
     * @var UserSystem
     */
    private $userSystem;

    /**
     * @var array
     */
    private $list = [];

    /**
     * WisePopPresenter constructor.
     *
     * @param WisePopFormFactory $wisePopFormFactory
     * @param ConnectorManager   $connectorManager
     * @param DistributionList   $distributionList
     */
    public function __construct(WisePopFormFactory $wisePopFormFactory, ConnectorManager $connectorManager,
                                DistributionList $distributionList)
    {
        parent::__construct();
        $this->wisePopFormFactory = $wisePopFormFactory;
        $this->connectorManager   = $connectorManager;
        $this->distributionList   = $distributionList;
    }

    /**
     * @param $systemKey
     *
     * @throws \CcApi\Connector\Exception\ConnectorException
     */
    public function actionDefault($systemKey)
    {
        if ($systemKey) {
            $this->userSystem       = $this->connectorManager->getUserSystem($this->userId, $systemKey);
            $this->list             = $this->distributionList->getListsForSelect(
                $this->userSystem->getToken(),
                $this->userId
            );
            $this->template->system = $this->userSystem;
        } else {
            $this->template->system = NULL;
        }
    }

    /**
     * @return Form
     */
    public function createComponentCustomForm(): Form
    {
        $form = $this->wisePopFormFactory->create($this->userSystem, $this->list);

        $form['refresh']->onClick[]          = [$this, 'processRefresh'];
        $form['save_custom_data']->onClick[] = [$this, 'processSave'];

        return $form;
    }

    /**
     * @param SubmitButton $submitButton
     *
     * @throws \CcApi\Connector\Exception\ConnectorException
     */
    public function processRefresh(SubmitButton $submitButton)
    {
        //$data = $this->connectorManager->customGetAction($this->userId, $this->userSystem->getKey(), 'refreshForms');

        $form = $submitButton->getForm();

        $form->addText('test', 'Test');

        $this->redrawControl('customForm');
    }

    /**
     * @param SubmitButton $submitButton
     *
     * @throws \Nette\Application\AbortException
     */
    public function processSave(SubmitButton $submitButton)
    {
        $data = $submitButton->getForm()->getValues(TRUE);

        $this->redirect('WisePop:', ['systemKey' => $this->userSystem->getKey()]);
    }

}