<?php declare(strict_types=1);

namespace App\Presenters;

use App\Model\DistributionList;
use CcApi\ApiEntity\UserSystem;
use CcApi\Connector\ConnectorManager;

/**
 * Class SystemCustomPresenter
 */
abstract class SystemCustomPresenter extends BasePresenter
{

    /**
     * @var ConnectorManager
     */
    protected $connectorManager;

    /**
     * @var DistributionList
     */
    protected $distributionList;

    /**
     * @var UserSystem
     */
    protected $userSystem;

    /**
     * @var array
     */
    protected $list = [];

    /**
     * SystemCustomPresenter constructor.
     *
     * @param ConnectorManager $connectorManager
     * @param DistributionList $distributionList
     */
    public function __construct(ConnectorManager $connectorManager, DistributionList $distributionList)
    {
        parent::__construct();
        $this->connectorManager = $connectorManager;
        $this->distributionList = $distributionList;
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
     */
    public function actionDefault($systemKey)
    {
        if ($systemKey) {
            $this->userSystem          = $this->connectorManager->getUserSystem($this->userId, $systemKey);
            $this->list                = $this->distributionList->getListsForSelect(
                $this->userSystem->getToken(),
                $this->userId
            );
            $this->template->system    = $this->userSystem;
            $this->template->systemKey = $systemKey;
        } else {
            $this->template->system = NULL;
        }
    }

}