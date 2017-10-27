<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CustomerObject\CMSubscriber;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class BasecrmCreatedContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Mapper
 */
class BasecrmCreatedContactMapper implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $item = json_decode($dto->getData(), TRUE);

        if (!array_key_exists('data', $item)
            || !array_key_exists('email', $item['data'])
            || is_null($item['data']['email'])
            || is_null($item['data']['id'])
        ) {
            throw new CleverConnectorsException(
                'Missing required email or id field in item data, BaseCRM - createdMapper.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $obj = new CMSubscriber();
        $obj->setEmail($item['data']['email'])
            ->setForeignId($item['data']['id']);

        if (array_key_exists('first_name', $item['data'])) {
            $obj->setFirstName($item['data']['first_name'] ?? '');
        }

        if (array_key_exists('last_name', $item['data'])) {
            $obj->setLastName($item['data']['last_name'] ?? '');
        }

        $dto->setData(json_encode($obj->toArray()));

        return $dto;
    }

}