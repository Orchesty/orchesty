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

        $i = 0;
        foreach ($system->getCustomForm() as $item) {

            $con = $form->addContainer($i);

            $con
                ->addText('form_id', $item['form_id']);

            $con
                ->addText('form_name', $item['form_name']);

            $con->addSelect('list', 'Distribution list', $distributionList);

            $i++;
        }

        $form->addSubmit('refresh', 'Refresh')
            ->getControlPrototype()
            ->addClass('ajax');

        $form->addSubmit('save_custom_data', 'Save');

        $form->setRenderer(new BootstrapV4Renderer());

        return $form;
    }

}