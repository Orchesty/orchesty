<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Mailmunch\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CustomerObject\CMSubscriber;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use function GuzzleHttp\Psr7\parse_query;

/**
 * Class MailmunchCreateEmailMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Mailmunch\Mapper
 */
class MailmunchCreateEmailMapper implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = parse_query($dto->getData(), TRUE);

        if (!array_key_exists('email', $data)) {
            throw new CleverConnectorsException(
                'Missing required email field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $obj = new CMSubscriber();
        $obj->setEmail($data['email']);

        if (array_key_exists('first-name', $data)) {
            $obj->setFirstName($data['first-name']);
        }

        if (array_key_exists('last-name', $data)) {
            $obj->setLastName($data['last-name']);
        }

        return $dto->setData(json_encode($obj->toArray()));
    }

}