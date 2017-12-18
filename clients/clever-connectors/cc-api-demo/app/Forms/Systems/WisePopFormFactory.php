<?php
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 12/14/17
 * Time: 2:15 PM
 */

namespace App\Form\Systems;

use AlesWita\FormRenderer\BootstrapV4Renderer;
use CcApi\ApiEntity\UserSystem;
use CcApi\Connector\ConnectorManager;
use Nette\Application\UI\Form;

/**
 * Class WisePopFormFactory
 *
 * @package App\Form\Systems
 */
class WisePopFormFactory
{

    /**
     * @var ConnectorManager
     */
    private $connectorManager;

    /**
     * WisePopFormFactory constructor.
     *
     * @param ConnectorManager $connectorManager
     */
    public function __construct(ConnectorManager $connectorManager)
    {
        $this->connectorManager = $connectorManager;
    }

    /**
     * @param UserSystem $system
     * @param array      $distributionList
     *
     * @return Form
     */
    public function create(UserSystem $system, array $distributionList = []): Form
    {
        $form = new Form();

        for ($i = 0; $i < count($system->getCustomForm()); $i++) {
            $con = $form->addContainer($i);

            $con->addText('form_id', 'Form')
                ->setAttribute('readonly');

            $con->addText('form_name', 'Name')
                ->setAttribute('readonly');

            $con->addSelect('list', 'Distribution list', $distributionList)
                ->setPrompt('Choose list');
        }

        $form->addSubmit('refresh', 'Refresh')
            ->getControlPrototype()
            ->addClass('ajax');

        $form->setDefaults($system->getCustomForm());

        $form->addSubmit('save_custom_data', 'Save');

        $form->setRenderer(new BootstrapV4Renderer());

        return $form;
    }

}