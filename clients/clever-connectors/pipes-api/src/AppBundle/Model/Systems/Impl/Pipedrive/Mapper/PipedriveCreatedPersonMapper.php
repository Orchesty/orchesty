<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CustomerObject\CMSubscriber;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class PipedriveCreatedPersonMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Mapper
 */
class PipedriveCreatedPersonMapper implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        if (!array_key_exists('data', $data)
            || !array_key_exists('email', $data['data'])
            || empty($data['data']['email'][0])
            || !array_key_exists('value', $data['data']['email'][0])
        ) {
            throw new CleverConnectorsException(
                'Missing required email field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $data = $data['data'];

        $obj = new CMSubscriber();
        $obj->setEmail($data['email'][0]['value']);

        if (array_key_exists('first_name', $data)) {
            $obj->setFirstName($data['first_name']);
        }

        if (array_key_exists('last_name', $data)) {
            $obj->setLastName($data['last_name'] ?? '');
        }

        if (array_key_exists('id', $data)) {
            $obj->setForeignId($data['id']);
        }

        return $dto->setData(json_encode($obj->toArray()));
    }

}