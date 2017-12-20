<?php

namespace App\Presenters;

use App\Form\Systems\FacebookLeadsFormFactory;
use App\Model\DistributionList;
use CcApi\Connector\ConnectorManager;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\Json;
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
     * FacebookLeadsFormFactory constructor.
     *
     * @param FacebookLeadsFormFactory $facebookLeadsFormFactory
     * @param ConnectorManager         $connectorManager
     * @param DistributionList         $distributionList
     */
    public function __construct(FacebookLeadsFormFactory $facebookLeadsFormFactory, ConnectorManager $connectorManager,
                                DistributionList $distributionList)
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
     *
     * @throws \CcApi\Connector\Exception\ConnectorException
     */
    public function processRefreshPages(SubmitButton $submitButton)
    {
        //        $data = $this->connectorManager->customGetAction($this->userId, $this->userSystem->getKey(), 'getPages');

        $data = [
            'page-1' => 'Page #1',
            'page-2' => 'Page #2',
            'page-3' => 'Page #3',
            'page-4' => 'Page #4',
            'page-5' => 'Page #5',
        ];

        $this->removeComponent($this['customForm']);
        $this['customForm'] = $this->createComponentCustomForm();
        $this['customForm']['page']->setItems($data);

        $this->redrawControl('customForm');
    }

    /**
     * @param SubmitButton $submitButton
     *
     * @throws \CcApi\Connector\Exception\ConnectorException
     */
    public function processRefreshForms(SubmitButton $submitButton)
    {
        //        $data = $this->connectorManager->customGetAction($this->userId, $this->userSystem->getKey(), 'getPages');
        //        $data2 = $this->connectorManager->customGetAction($this->userId, $this->userSystem->getKey(), 'getForms', [
        //            'page_id' => $submitButton->getForm()->getValues(TRUE)['pages'],
        //        ]);

        $data2 = [
            'form-1' => 'Form #1',
            'form-2' => 'Form #2',
            'form-3' => 'Form #3',
            'form-4' => 'Form #4',
            'form-5' => 'Form #5',
        ];

        $data = [
            'page-1' => 'Page #1',
            'page-2' => 'Page #2',
            'page-3' => 'Page #3',
            'page-4' => 'Page #4',
            'page-5' => 'Page #5',
        ];

        $this->removeComponent($this['customForm']);
        $this['customForm'] = $this->createComponentCustomForm();
        $this['customForm']['page']->setItems($data);
        $this['customForm']['form']->setItems($data2);
        $this['customForm']['forms']->setValue(Json::encode($data2));

        $this->redrawControl('customForm');
    }

    /**
     * @param SubmitButton $submitButton
     *
     * @throws \Nette\Application\AbortException
     */
    public function processSave(SubmitButton $submitButton)
    {
        $form   = $submitButton->getForm();
        $id     = $form->getHttpData($form::DATA_TEXT, '_form');
        $data   = $form->getValues(TRUE);
        $data[] = [
            'form_id'   => $id,
            'form_name' => Json::decode($data['forms'], TRUE)[$id],
            'list'      => $data['list'],
        ];

        unset($data['forms'], $data['page'], $data['form'], $data['list']);
        dump($data);
        exit;

        //$this->connectorManager->customPostAction($this->userId, $this->userSystem->getKey(), 'saveCustomForm', $data);

        $this->redirect('WisePop:', ['systemKey' => $this->userSystem->getKey()]);
    }

}