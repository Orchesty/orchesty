<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Class ZendeskCreatedEventUserMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Mapper
 */
class ZendeskCreatedEventUserMapper extends ZendeskUserMapperAbstract
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

        if (!array_key_exists('user', $data)
            || !array_key_exists('email', $data['user'])
            || empty($data['user']['email'] ?? '')
        ) {
            throw new CleverConnectorsException(
                'Missing required email field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $obj = $this->createSubscriber($dto, $data['user']);

        return $dto->setData(json_encode($obj->toArray()));
    }

}