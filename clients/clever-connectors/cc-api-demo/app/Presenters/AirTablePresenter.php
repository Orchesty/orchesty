<?php

namespace App\Presenters;

use App\Form\Systems\AirTableFormFactory;
use App\Model\DistributionList;
use CcApi\Connector\ConnectorManager;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\Strings;
use SystemCustomPresenter;

/**
 * Class AirTablePresenter
 *
 * @package App\Presenters
 */
class AirTablePresenter extends SystemCustomPresenter
{

    /**
     * @var AirTableFormFactory
     */
    private $airTableFormFactory;

    /**
     * AirTablePresenter constructor.
     *
     * @param AirTableFormFactory $airTableFormFactory
     * @param ConnectorManager    $connectorManager
     * @param DistributionList    $distributionList
     */
    public function __construct(AirTableFormFactory $airTableFormFactory, ConnectorManager $connectorManager,
                                DistributionList $distributionList)
    {
        parent::__construct($connectorManager, $distributionList);
        $this->airTableFormFactory = $airTableFormFactory;
    }

    /**
     * @return Form
     */
    public function createComponentCustomForm(): Form
    {
        $form = $this->airTableFormFactory->create($this->userSystem, $this->list, $this);

        $form['save_custom_data']->onClick[] = [$this, 'processSave'];

        return $form;
    }

    /**
     * @param SubmitButton $submitButton
     *
     * @throws \Nette\Application\AbortException
     */
    public function processSave(SubmitButton $submitButton)
    {
        $data = $submitButton->getForm()->getValues(TRUE);
        foreach ($data['table_multiplier'] as $keyTable => &$table) {
            foreach ($table['data_layout'] as &$dataLayout) {
                $dataLayout['type'] = 'text';
            }

            $table['table-url'] = $table['table_url'];
            $table['list-id']   = $table['list_id'];
            unset($table['table_url'], $table['list_id']);

            $i          = 0;
            $templateIn = [];
            foreach ($table['template_in'][0] as $key => $template) {
                if ($i++ % 2 === 0) {
                    $templateIn[] = [
                        'key'   => $key,
                        'type'  => $table['template_in'][0][$key . '_type'],
                        'items' => [$template],
                    ];
                }
            }
            $templateIn[] = [
                'key'   => '_foreign_id',
                'type'  => 'text',
                'items' => ['id'],
            ];

            $templateOut = [];
            foreach ($table['data_layout'] as $key => $innerDataLayout) {
                $key           = str_replace('-', '_', Strings::webalize($innerDataLayout['key']));
                $templateOut[] = [
                    'key'   => $innerDataLayout['key'],
                    'type'  => $table['template_out'][0][$key . '_type'],
                    'items' => [$table['template_out'][0][$key]],
                ];
            }
            $templateOut[] = [
                'key'   => 'id',
                'type'  => 'text',
                'items' => ['_foreign_id'],
            ];

            $table['map_templates'] = [
                'template_in'  => $templateIn,
                'template_out' => $templateOut,
            ];

            unset($table['template_in'], $table['template_out']);
        }

        $this->connectorManager->customPostAction(
            $this->userId,
            $this->userSystem->getKey(),
            'saveCustomForm',
            $data['table_multiplier']
        );

        $this->session->getSection(AirTableFormFactory::class)->remove();
        $this->redirect('AirTable:', ['systemKey' => $this->userSystem->getKey()]);
    }

}