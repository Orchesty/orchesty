<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Nette\Utils\Json;

/**
 * Class NutshellSyncUpdateContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Mapper
 */
class NutshellSyncUpdateContactMapper implements CustomNodeInterface
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
        if (!isset($data['result']['email']['--primary'])) {
            throw new CleverConnectorsException(
                'Missing required email field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $subscriber = new CMSubscriber();
        $subscriber->setEmail($data['result']['email']['--primary']);

        if (isset($data['result']['name']['givenName'])) {
            $subscriber->setFirstName($data['result']['name']['givenName']);
        }

        if (isset($data['result']['name']['familyName'])) {
            $subscriber->setLastName($data['result']['name']['familyName']);
        }

        if (isset($data['result']['id'])) {
            $subscriber->setForeignId($data['result']['id']);
        }

        return $dto->setData(Json::encode($subscriber->toArray()));
    }

}