<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Class HubspotCreateContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper
 */
class HubspotCreateContactMapper extends HubspotMapperAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        $contact = [
            'properties' => [
                $this->prepareProperty('email', $data[CleverFieldsEnum::EMAIL] ?? ''),
                $this->prepareProperty('firstname', $data[CleverFieldsEnum::FIRST_NAME]),
                $this->prepareProperty('lastname', $data[CleverFieldsEnum::LAST_NAME]),
            ],
        ];

        return $dto->setData(json_encode($contact));
    }

}