<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper;

use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Class HubspotUpdateContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper
 */
class HubspotUpdateContactMapper extends HubspotMapperAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data  = json_decode($dto->getData(), TRUE);
        $field = CMHeaders::get(CMHeaders::CM_EVENT_TYPE, $dto->getHeaders()) ?? '';

        $contact = [
            'properties' => [
                $this->prepareProperty(CleverCustomKeysEnum::getFromType($field), TRUE),
            ],
        ];

        return $dto->setData(json_encode([
            'id'   => $data[CleverFieldsEnum::FOREIGN_ID],
            'body' => json_encode($contact),
        ]));
    }

}