<?php
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 11.12.17
 * Time: 7:34
 */

namespace App\Presenters;

use App\Forms\DataLayoutFormFactory;
use App\Forms\MappingFormFactory;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;

/**
 * Class MappingPresenter
 *
 * @package App\Presenters
 */
class MappingPresenter extends BasePresenter
{

    /**
     * @var DataLayoutFormFactory
     */
    private $dataLayoutFormFactory;

    /**
     * @var MappingFormFactory
     */
    private $mappingFormFactory;

    /**
     * MappingPresenter constructor.
     *
     * @param DataLayoutFormFactory $dataLayoutFormFactory
     * @param MappingFormFactory    $mappingFormFactory
     */
    public function __construct(DataLayoutFormFactory $dataLayoutFormFactory, MappingFormFactory $mappingFormFactory)
    {
        parent::__construct();
        $this->dataLayoutFormFactory = $dataLayoutFormFactory;
        $this->mappingFormFactory    = $mappingFormFactory;
    }

    /**
     * @return Form
     */
    public function createComponentDataLayoutForm(): Form
    {
        $form = $this->dataLayoutFormFactory->create();

        $form['save_data_layout']->onClick[] = [$this, 'processDataLayoutForm'];

        return $form;
    }

    /**
     * @param SubmitButton $button
     *
     * @throws \Nette\Application\AbortException
     */
    public function processDataLayoutForm(SubmitButton $button)
    {
        $data = $button->getForm()->getValues(TRUE);

        var_dump($data);
        die;

        $this->redirect('Mapping:');
    }

    /**
     * @return Form
     */
    public function createComponentMappingForm(): Form
    {
        $dataLayout = ['field_1' => 'field_1'];

        $form = $this->mappingFormFactory->create([
            'first_name' => $dataLayout,
            'last_name'  => $dataLayout,
            'email'      => $dataLayout,
        ]);

        $form->onSubmit[] = [$this, 'processMappingForm'];

        return $form;
    }

    /**
     * @param Form $form
     *
     * @throws \Nette\Application\AbortException
     */
    public function processMappingForm(Form $form)
    {
        $data = $form->getValues(TRUE);

        var_dump($data);
        die;

        $this->redirect('Mapping:');
    }

}