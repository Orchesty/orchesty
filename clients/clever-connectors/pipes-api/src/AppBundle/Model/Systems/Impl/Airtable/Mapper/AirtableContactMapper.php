<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Mapper;

use CleverConnectors\AppBundle\Document\MapTemplate;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\CustomNode\UniversalMapperNode;
use CleverConnectors\AppBundle\Model\Mapper\UniversalMapper;
use CleverConnectors\AppBundle\Model\MapTemplate\MapField;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\AirtableSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Exception;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use LogicException;

/**
 * Class AirtableContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Mapper
 */
class AirtableContactMapper extends UniversalMapperNode
{

    /**
     * @var bool
     */
    protected $includeList = FALSE;

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws Exception
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $template = $this->getMapTemplate($dto);

        if (!$template) {
            return $dto;
        }

        try {
            $this->formatDtoDataIn($dto);

            $mapper = new UniversalMapper();
            /** @var ProcessDto $dto */
            $dto = $mapper
                ->setAllowedMissingKey(TRUE)
                ->process($template, $dto);

            if ($this->includeList) {
                $this->addList($dto);
            }
            $this->formatDtoDataOut($dto);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            throw $exception;
        }

        return $dto;
    }

    /**
     * @param ProcessDto $dto
     */
    protected function addList(ProcessDto $dto): void
    {
        $headers = $dto->getHeaders();
        $list    = CMHeaders::get(AirtableSystem::LIST_ID, $headers);

        if ($list) {
            $data                          = json_decode($dto->getData(), TRUE);
            $data[CleverFieldsEnum::LISTS] = [$list];

            $dto->setData(json_encode($data));
        }
    }

    /**
     * @param ProcessDto $dto
     */
    protected function formatDtoDataIn(ProcessDto $dto): void
    {
        if ($this->suffix !== 'in') {
            return;
        }

        $data   = json_decode($dto->getData(), TRUE);
        $fields = $data['fields'];
        unset($data['fields']);
        $data = array_merge($fields, $data);
        $dto->setData(json_encode($data));
    }

    /**
     * @param ProcessDto $dto
     */
    protected function formatDtoDataOut(ProcessDto $dto): void
    {
        if ($this->suffix !== 'out') {
            return;
        }

        $data   = json_decode($dto->getData(), TRUE);
        $fields = $data;
        unset($fields['id']);

        if (array_key_exists('id', $data)) {
            $data = [
                'id' => $data['id'],
            ];
        } else {
            $data = [];
        }
        $data['fields'] = $fields;

        $dto->setData(json_encode($data));
    }

    /**
     * @param ProcessDto $dto
     *
     * @return MapTemplate|null
     */
    protected function getMapTemplate(ProcessDto $dto): ?MapTemplate
    {
        $systemInstall = $this->systemRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $actionName    = CMHeaders::get(CMHeaders::TOPOLOGY_NAME, $dto->getHeaders());
        $table         = CMHeaders::get(AirtableSystem::TABLE_URL, $dto->getHeaders());
        $actions       = $this->loader->getSystem($systemInstall->getSystem())->getAllowedActions();

        if ($this->suffix) {
            $actionName = sprintf('%s-%s', $actionName, $this->suffix);
        }

        if (!array_key_exists($actionName, $actions)) {
            $this->logger->alert(
                sprintf('Not allowed action "%s" found for system "%s"!', $actionName, $systemInstall->getSystem())
            );

            return NULL;
        }

        $sett = $systemInstall->getSettings();
        foreach ($sett[SystemInstall::FORMS] as $row) {
            if ($row[AirtableSystem::TABLE_URL] === $table) {
                $map = new MapTemplate();

                $key = $this->suffix === 'in' ? AirtableSystem::TEMPLATE_IN : AirtableSystem::TEMPLATE_OUT;

                foreach ($row[AirtableSystem::TEMPLATE][$key] as $field) {
                    $map->addField(MapField::from($field));
                }

                return $map;
            }
        }

        throw new LogicException('Map template not found');
    }

}