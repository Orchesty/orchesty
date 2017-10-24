<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CustomerObject\CMSubscriber;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;

/**
 * Class BasecrmContactDeleteMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Mapper
 */
class BasecrmContactDeleteMapper extends BasecrmContactMapperAbstract
{

    /**
     * @var array
     */
    protected static $event_types = ['deleted'];

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
                || !array_key_exists('id', $item['data'])
                || is_null($item['data']['id'])
            ) {
                throw new CleverConnectorsException(
                    'Missing required id field in item data, BaseCRM - deleteMapper.',
                    CleverConnectorsException::MISSING_DATA
                );
            }

            $obj = new CMSubscriber();
            $obj->setForeignId($item['data']['id'])
                ->setReactivate(FALSE);

            $dto->setData(json_encode($obj->toArray()));
        }

        return $dto;
    }

}