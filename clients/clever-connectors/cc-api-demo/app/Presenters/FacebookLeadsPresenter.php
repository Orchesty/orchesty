<?php

namespace App\Presenters;

use App\Form\Systems\FacebookLeadsFormFactory;
use App\Model\DistributionList;
use CcApi\Connector\ConnectorManager;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use SystemCustomPresenter;

/**
 * Class FacebookLeadsPresenter
 *
 * @package App\Presenters
 */
class FacebookLeadsPresenter extends SystemCustomPresenter
{

    /**
     * @var FacebookLeadsFormFactory
     */
    private $facebookLeadsFormFactory;

    /**
     * FacebookLeadsPresenter constructor.
     *
     * @param FacebookLeadsFormFactory $facebookLeadsFormFactory
     * @param ConnectorManager         $connectorManager
     * @param DistributionList         $distributionList
     */
    public function __construct(
        FacebookLeadsFormFactory $facebookLeadsFormFactory,
        ConnectorManager $connectorManager,
        DistributionList $distributionList
    )
    {
        parent::__construct($connectorManager, $distributionList);
        $this->facebookLeadsFormFactory = $facebookLeadsFormFactory;
    }

    /**
     * @return Form
     */
    public function createComponentCustomForm(): Form
    {
        $form = $this->facebookLeadsFormFactory->create($this->userSystem, $this->list);

        $form['refresh_pages']->onClick[]    = [$this, 'processRefreshPages'];
        $form['refresh_forms']->onClick[]    = [$this, 'processRefreshForms'];
        $form['save_custom_data']->onClick[] = [$this, 'processSave'];

        return $form;
    }

    /**
     * @param SubmitButton $submitButton
     */
    public function processRefreshPages(SubmitButton $submitButton)
    {
        $data = $this->connectorManager->customGetAction($this->userId, $this->userSystem->getKey(), 'getPages');

        $this->removeComponent($this['customForm']);
        $this['customForm'] = $this->createComponentCustomForm();
        $this['customForm']['page']->setItems($data);

        $this->redrawControl('customForm');
    }

    /**
     * @param SubmitButton $submitButton
     */
    public function processRefreshForms(SubmitButton $submitButton)
    {
        $page = $submitButton->getForm()->getHttpData(Form::DATA_TEXT, 'page');

        if ($page) {
            $data = $this->connectorManager->customPostAction($this->userId, $this->userSystem->getKey(), 'getForms', [
                'page_id' => $page,
            ]);

            $this->userSystem->setCustomForm($data);
            $this->removeComponent($this['customForm']);
            $this['customForm'] = $this->createComponentCustomForm();
            $this['customForm']->setValues($data);

            $this->redrawControl('customForm');
        }
    }

    /**
     * @param SubmitButton $submitButton
     */
    public function processSave(SubmitButton $submitButton)
    {
        $data = $submitButton->getForm()->getValues(TRUE);
        unset($data['page']);

        $this->connectorManager->customPostAction($this->userId, $this->userSystem->getKey(), 'saveCustomForm', $data);

        $this->redirect('FacebookLeads:', ['systemKey' => $this->userSystem->getKey()]);
    }

}