<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Nette\Utils\Json;

/**
 * Class AirtableUpdateContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Mapper
 */
class AirtableUpdateContactMapper implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = Json::decode($dto->getData(), TRUE);

        if (!is_array($data) || !array_key_exists(CleverFieldsEnum::FOREIGN_ID, $data)) {
            throw new CleverConnectorsException(
                'Missing data or required field _foreign_id',
                CleverConnectorsException::MISSING_DATA
            );
        }

        /** @var string $eventType */
        $eventType = CMHeaders::get(CMHeaders::CM_EVENT_TYPE, $dto->getHeaders());
        $eventKey  = CleverFieldsEnum::getFromType($eventType);

        $data[$eventKey] = 1;

        return $dto->setData(Json::encode($data));
    }

}