<?php
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 25.10.17
 * Time: 8:33
 */

namespace App\Presenters;

use App\Forms\SystemInstallFormFactory;
use CcApi\ApiEntity\System;
use CcApi\ApiEntity\UserSystem;
use CcApi\Connector\ConnectorManager;
use Nette\Forms\Form;

/**
 * Class SystemPresenter
 *
 * @package App\Presenters
 */
class SystemPresenter extends BasePresenter
{

    /**
     * @var ConnectorManager
     */
    private $connectorManager;

    /**
     * @var SystemInstallFormFactory
     */
    private $systemActionFormFactory;

    /**
     * HomepagePresenter constructor.
     *
     * @param ConnectorManager                     $connectorManager
     * @param SystemInstallFormFactory             $systemActionGeneratorFactory
     */
    public function __construct(ConnectorManager $connectorManager, SystemInstallFormFactory $systemActionGeneratorFactory)
    {
        parent::__construct();
        $this->connectorManager                = $connectorManager;
        $this->systemActionFormFactory         = $systemActionGeneratorFactory;
    }



    /**
     * @return \Nette\Application\UI\Form
     */
    public function createComponentSystemActionForm()
    {
        $systems = $this->connectorManager->getAllSystems();

        usort($systems, function(System $systemOne, System $systemTwo) {
            return strcasecmp($systemOne->getKey(), $systemTwo->getKey());
        });

        $items = [];
        foreach ($systems as $system) {
            $items[$system->getKey()] = $system->getName();
        }

        $form                         = $this->systemActionFormFactory->create($items);
        $form->onSuccess[]   = [$this, 'processInstall'];

        return $form;
    }

    /**
     * @param Form $form
     */
    public function processInstall(Form $form)
    {
        $data = $form->getValues();

        $this->connectorManager->installUserSystem($this->userId, $data['systems'], $data['token']);

        $this->redirect('//System:');
    }

    /**
     * @param $systemKey
     */
    public function handleUninstall($systemKey)
    {
        $this->connectorManager->uninstallUserSystem($this->userId, $systemKey);

        $this->redirect('//System:');
    }

    /**
     *
     */
    public function actionDefault()
    {
        $this->template->userSystemData = FALSE;

        if ($this->user->isLoggedIn()) {
            $userSystems = $this->connectorManager->getAllUserSystems($this->userId);

            usort($userSystems, function(UserSystem $userSystemOne, UserSystem $userSystemTwo) {
                return strcasecmp($userSystemOne->getKey(), $userSystemTwo->getKey());
            });

            $this->template->installedSystems = $userSystems;
        }
    }

}