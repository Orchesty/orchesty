<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use CleverConnectors\AppBundle\Utils\HeadersUtils;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class BasecrmContactMapperAbstact
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Mapper
 */
abstract class BasecrmContactMapperAbstract implements CustomNodeInterface
{

    /**
     * @var array
     */
    protected static $event_types = [];

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        if ($this->checkEventType($data, $dto)) {
            $obj = $this->getSubscriber($data);
            $dto->setData(json_encode($obj->toArray()));
        }

        return $dto;
    }

    /**
     * @param array      $data
     * @param ProcessDto $dto
     *
     * @return bool
     */
    protected function checkEventType(array $data, ProcessDto $dto): bool
    {
        if (array_key_exists('sync', $data['meta'])
            && !in_array($data['meta']['sync']['event_type'], static::$event_types)
        ) {
            HeadersUtils::setStopHeaderToDto($dto, 'Event type does not match mapper type, BaseCRM.');

            return FALSE;
        }

        return TRUE;
    }

    /**
     * @param array $data
     *
     * @return CMSubscriber
     * @throws CleverConnectorsException
     */
    protected function getSubscriber(array $data): CMSubscriber
    {
        if (!array_key_exists('data', $data)
            || !array_key_exists('email', $data['data'])
            || is_null($data['data']['email'])
            || is_null($data['data']['id'])
        ) {
            throw new CleverConnectorsException(
                'Missing required email or id field in item data, BaseCRM.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $obj = new CMSubscriber();
        $obj->setEmail($data['data']['email'])
            ->setForeignId($data['data']['id']);

        if (array_key_exists('first_name', $data['data'])) {
            $obj->setFirstName($data['data']['first_name'] ?? '');
        }

        if (array_key_exists('last_name', $data['data'])) {
            $obj->setLastName($data['data']['last_name'] ?? '');
        }

        return $obj;
    }

}