<?php

namespace App\Presenters;

use CcApi\Connector\ConnectorManager;

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
     * HomepagePresenter constructor.
     *
     * @param ConnectorManager $connectorManager
     */
    public function __construct(ConnectorManager $connectorManager)
    {
        parent::__construct();
        $this->connectorManager = $connectorManager;
    }

    /**
     *
     */
    public function renderDefault()
    {
        $systems = $this->connectorManager->getAllSystems();

        $this->template->systems = $systems;
    }

}
