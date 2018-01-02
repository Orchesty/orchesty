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
use CcApi\Connector\ConnectorManager;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use SystemCustomPresenter;

/**
 * Class WisePopPresenter
 *
 * @package App\Presenters
 */
class WisePopPresenter extends SystemCustomPresenter
{

    /**
     * @var WisePopFormFactory
     */
    private $wisePopFormFactory;

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
        parent::__construct($connectorManager, $distributionList);
        $this->wisePopFormFactory = $wisePopFormFactory;
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
        $data = $this->connectorManager->customGetAction($this->userId, $this->userSystem->getKey(), 'refreshForms');

        $this->userSystem->setCustomForm($data);

        $this->removeComponent($this['customForm']);
        $this['customForm'] = $this->createComponentCustomForm();
        $this['customForm']->setValues($data, TRUE);

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

        $this->connectorManager->customPostAction($this->userId, $this->userSystem->getKey(), 'saveCustomForm', $data);

        $this->redirect('WisePop:', ['systemKey' => $this->userSystem->getKey()]);
    }

}