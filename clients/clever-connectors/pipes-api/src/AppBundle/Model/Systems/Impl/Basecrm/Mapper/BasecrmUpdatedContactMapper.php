<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CustomerObject\CMSubscriber;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;

/**
 * Class BasecrmUpdatedContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Mapper
 */
class BasecrmUpdatedContactMapper extends BasecrmContactMapperAbstract
{

    /**
     * @var array
     */
    protected static $event_types = ['created', 'updated'];

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $item = json_decode($dto->getData(), TRUE);

        if ($this->checkEventType($item, $dto)) {
            if (!array_key_exists('data', $item)
                || !array_key_exists('email', $item['data'])
                || is_null($item['data']['email'])
                || is_null($item['data']['id'])
            ) {
                throw new CleverConnectorsException(
                    'Missing required email or id field in item data, BaseCRM - updatedMapper.',
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
        }

        return $dto;
    }

}