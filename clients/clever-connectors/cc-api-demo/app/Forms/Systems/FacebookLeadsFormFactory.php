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
 * Class FacebookLeadsFormFactory
 *
 * @package App\Form\Systems
 */
class FacebookLeadsFormFactory
{

    /**
     * @var ConnectorManager
     */
    private $connectorManager;

    /**
     * FacebookLeadsFormFactory constructor.
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

        $data = [
            [
                'form_id'   => 'form-1',
                'form_name' => 'Form #1',
                'list'      => NULL,
            ], [
                'form_id'   => 'form-2',
                'form_name' => 'Form #2',
                'list'      => 'ef45fd9b-7d66-5c0e-08ac-956a74121915',
            ],
        ];

        for ($i = 0; $i < count($data); $i++) {
            $container = $form->addContainer($i);

            $container
                ->addText('form_id', 'Form')
                ->setAttribute('readonly');

            $container
                ->addText('form_name', 'Name')
                ->setAttribute('readonly');

            $container
                ->addSelect('list', 'Distribution list', $distributionList)
                ->setPrompt('Choose list');
        }

        $form->addHidden('forms');
        $form->addSelect('page', 'Page');
        $form->addSelect('form', 'Form');
        $form
            ->addSelect('list', 'Distribution list', $distributionList)
            ->setPrompt('Choose list');

        $form->addSubmit('refresh_pages', 'Load Pages');
        $form->addSubmit('refresh_forms', 'Load Forms');
        $form->addSubmit('save_custom_data', 'Save');

        $form->setDefaults($data);
        $form->setRenderer(new BootstrapV4Renderer());

        return $form;
    }

}