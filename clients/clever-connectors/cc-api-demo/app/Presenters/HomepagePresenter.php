<?php

namespace App\Presenters;

use App\Forms\SystemFilterFormFactory;
use CcApi\Connector\ConnectorManager;
use Nette\Application\UI\Form;

/**
 * Class HomepagePresenter
 *
 * @package App\Presenters
 */
class HomepagePresenter extends BasePresenter
{

    /**
     * @var ConnectorManager
     */
    private $connectorManager;

    /**
     * @var SystemFilterFormFactory
     */
    private $systemFilterFormFactory;

    /**
     * HomepagePresenter constructor.
     *
     * @param ConnectorManager        $connectorManager
     * @param SystemFilterFormFactory $systemFilterGeneratorFactory
     */
    public function __construct(ConnectorManager $connectorManager, SystemFilterFormFactory $systemFilterGeneratorFactory)
    {
        parent::__construct();
        $this->connectorManager        = $connectorManager;
        $this->systemFilterFormFactory = $systemFilterGeneratorFactory;
    }

    /**
     * @return Form
     */
    public function createComponentSystemFilterForm(): Form
    {
        $form = $this->systemFilterFormFactory->create();
        $form->getElementPrototype()->setAttribute('class', 'ajax');

        $form->onSuccess[] = [$this, 'processSystemFilter'];

        return $form;
    }

    /**
     * @param Form $form
     *
     * @throws \CcApi\Connector\Exception\ConnectorException
     */
    public function processSystemFilter(Form $form): void
    {
        $data = $form->getValues(TRUE);

        $this->template->systems = $this->connectorManager->getAllSystems(
            $data['group'] ?? NULL,
            $data['user_id'] ?? NULL
        );

        $this->redrawControl('allSystems');
    }

    /**
     * @throws \CcApi\Connector\Exception\ConnectorException
     */
    public function actionDefault(): void
    {
        $systems = $this->connectorManager->getAllSystems();

        $this->template->systems = $systems;
    }

}
