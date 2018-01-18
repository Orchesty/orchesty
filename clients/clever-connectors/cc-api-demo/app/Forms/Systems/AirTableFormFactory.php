<?php

namespace App\Form\Systems;

use AlesWita\FormRenderer\BootstrapV4Renderer;
use App\Presenters\AirTablePresenter;
use CcApi\ApiEntity\UserSystem;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Utils\Strings;
use WebChemistry\Forms\Controls\Multiplier;

/**
 * Class AirTableFormFactory
 *
 * @package App\Form\Systems
 */
class AirTableFormFactory
{

    /**
     * @var SessionSection
     */
    private $session;

    /**
     * @var AirTablePresenter
     */
    private $presenter;

    /**
     * @var UserSystem
     */
    private $system;

    /**
     * @var array
     */
    private $distributionList = [];

    /**
     * @var int
     */
    private $counter = 0;

    /**
     * @var array
     */
    private $types = [
        'text'   => 'Text',
        'number' => 'Number',
        'bool'   => 'Boolean',
        'date'   => 'Date',
        'url'    => 'Url',
        'email'  => 'Email',
    ];

    /**
     * AirTableFormFactory constructor.
     *
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session->getSection(self::class);
    }

    /**
     * @param UserSystem $system
     * @param array      $distributionList
     *
     * @return Form
     */
    public function create(UserSystem $system, array $distributionList = [], $presenter): Form
    {
        natcasesort($distributionList);

        $this->system           = $system;
        $this->presenter        = $presenter;
        $this->distributionList = $distributionList;
        $data                   = $system->getCustomForm();

        foreach ($data as &$table) {
            $table['table_url'] = $table['table-url'] ?? '';
            $table['list_id']   = $table['list-id'] ?? NULL;
            unset($table['table-url'], $table['list-id']);
        }

        $form = new Form();

        /** @var Multiplier $tableMultiplier */
        $tableMultiplier = $form->addMultiplier('table_multiplier',
            function (Container $container, Form $form) {
                $dataLayout = $this->getCurrentDataLayoutForSelect();
                $container
                    ->addText('table_url', 'URL*')
                    ->setRequired('Table URL is required, please fill it.');
                $container
                    ->addText('view', 'View');
                $container
                    ->addSelect('list_id', 'Distribution list', $this->distributionList)
                    ->setPrompt('Choose list');

                /** @var Multiplier $dataLayoutMultiplier */
                $dataLayoutMultiplier = $container->addMultiplier('data_layout',
                    function (Container $innerContainer, Form $form) {
                        $innerContainer
                            ->addText('key', 'Key*')
                            ->setRequired('DataLayout key is required, please fill it.');
                    }, 1);

                $container->addSubmit(
                    'data_layout_save',
                    'Save Layout'
                )->setValidationScope(FALSE)->onClick[] = function (SubmitButton $submitButton) {
                    $this->session->data = $submitButton->getForm()->getValues(TRUE);
                };
                $container->addSubmit(
                    'data_layout_refresh',
                    'Refresh Mapping'
                )->setValidationScope(FALSE)->onClick[] = function (SubmitButton $submitButton) {
                    $this->session->data = $submitButton->getForm()->getValues(TRUE);
                };

                $dataLayoutMultiplier->addCreateButton('Create Layout', 1, [$this, 'redrawControl']);
                $dataLayoutMultiplier->addRemoveButton('Delete Layout', [$this, 'redrawControl']);
                $dataLayoutMultiplier->setDefaults($this->getCurrentDataLayout());

                /** @var Multiplier $mappingInMultiplier */
                $mappingInMultiplier = $container->addMultiplier('template_in',
                    function (Container $innerContainer, Form $form) use ($dataLayout) {
                        $this->prepareMappingInContainer($innerContainer, $dataLayout);
                    }, 1, 1);
                $mappingInMultiplier->addCreateButton('Create Mapping', 1, [$this, 'redrawControl']);
                $mappingInMultiplier->setValues($this->getCurrentMappingIn($dataLayout));

                /** @var Multiplier $mappingOutMultiplier */
                $mappingOutMultiplier = $container->addMultiplier('template_out',
                    function (Container $innerContainer, Form $form) use ($dataLayout) {
                        $this->prepareMappingOutContainer($innerContainer, $dataLayout);
                    }, 1, 1);
                $mappingOutMultiplier->addCreateButton('Create Mapping', 1, [$this, 'redrawControl']);
                $mappingOutMultiplier->setDefaults($this->getCurrentMappingOut());

                $this->counter++;
            });

        $tableMultiplier->addCreateButton('Create Table', 1, [$this, 'redrawControl']);
        $tableMultiplier->addRemoveButton('Delete Table', [$this, 'redrawControl']);
        $tableMultiplier->setDefaults($data);

        $form->addSubmit('save_custom_data', 'Save Everything');
        $form->setRenderer(new BootstrapV4Renderer);

        return $form;
    }

    /**
     * @param SubmitButton $submitButton
     */
    public function redrawControl(SubmitButton $submitButton): void
    {
        $this->presenter->redrawControl('customForm');
    }

    /**
     * @return array
     */
    private function getCurrentDataLayout(): array
    {
        return $this->session->data['table_multiplier'][$this->counter]['data_layout']
            ?? $this->system->getCustomForm()[$this->counter]['data_layout']
            ?? [];
    }

    /**
     * @return array
     */
    private function getCurrentDataLayoutForSelect(): array
    {
        $dataLayout = array_map(function ($innerData) {
            return $innerData['key'];
        }, $this->getCurrentDataLayout());

        return array_combine($dataLayout, $dataLayout);
    }

    /**
     * @param array $dataLayout
     *
     * @return array
     */
    private function getCurrentMappingIn(array $dataLayout): array
    {
        $rawMapping = $this->system->getCustomForm()[$this->counter]['map_templates']['template_in'] ?? [];
        $mappings   = [];

        foreach ($rawMapping as $mapping) {
            if (in_array($mapping['items'][0], $dataLayout, TRUE)) {
                $mappings[$mapping['key']]           = $mapping['items'][0];
                $mappings[$mapping['key'] . '_type'] = $mapping['type'];
            }
        }

        return [$mappings];
    }

    /**
     * @return array
     */
    private function getCurrentMappingOut(): array
    {
        $rawMapping = $this->system->getCustomForm()[$this->counter]['map_templates']['template_out'] ?? [];
        $mappings   = [];

        foreach ($rawMapping as $mapping) {
            $key                      = str_replace('-', '_', Strings::webalize($mapping['key']));
            $mappings[$key]           = $mapping['items'][0];
            $mappings[$key . '_type'] = $mapping['type'];
        }

        return [$mappings];
    }

    /**
     * @param Container $container
     * @param array     $dataLayout
     */
    private function prepareMappingInContainer(Container &$container, array $dataLayout): void
    {
        $this->generateMappingContainer($container, [
            'email'       => $dataLayout,
            'first_name'  => $dataLayout,
            'last_name'   => $dataLayout,
            'unsubscribe' => $dataLayout,
            'hard_bounce' => $dataLayout,
        ]);
    }

    /**
     * @param Container $container
     * @param array     $dataLayout
     */
    private function prepareMappingOutContainer(Container &$container, array $dataLayout): void
    {
        $mappings = [];
        foreach ($dataLayout as $item) {
            $mappings[str_replace('-', '_', Strings::webalize($item))] = [
                'email'       => 'Email',
                'first_name'  => 'First Name',
                'last_name'   => 'Last Name',
                'unsubscribe' => 'UnSubscribe',
                'hard_bounce' => 'Hard Bounce',
            ];
        }

        $this->generateMappingContainer($container, $mappings);
    }

    /**
     * @param Container $container
     * @param array     $mappings
     */
    private function generateMappingContainer(Container &$container, array $mappings): void
    {
        foreach ($mappings as $key => $value) {
            if ($key) {
                $container
                    ->addSelect($key, $key, $value)
                    ->setPrompt('Choose key')
                    ->setRequired('Mapping fields are required, please fill them.');

                $container
                    ->addSelect($key . '_type', 'Format*', $this->types)
                    ->setPrompt('Choose format')
                    ->setDefaultValue('text')
                    ->setRequired('Mapping formats are required, please fill them.');
            }
        }
    }

}