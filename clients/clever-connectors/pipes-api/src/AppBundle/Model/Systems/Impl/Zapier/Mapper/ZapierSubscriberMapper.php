<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 10/31/17
 * Time: 12:30 PM
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Mapper;

use CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CustomerObject\CMSubscriber;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Nette\Utils\Json;

/**
 * Class ZapierSubscriberMapper
 *
 * @package AppBundle\Model\Systems\Impl\Zapier\Mapper
 */
class ZapierSubscriberMapper implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = Json::decode($dto->getData(), TRUE);

        $subscriber = new CMSubscriber();
        $subscriber->setEmail($data['email']);

        if (array_key_exists('first_name', $data)) {
            $subscriber->setFirstName($data['first_name']);
        }

        if (array_key_exists('last_name', $data)) {
            $subscriber->setLastName($data['last_name'] ?? '');
        }

        if (array_key_exists('id', $data)) {
            $subscriber->setForeignId($data['id']);
        }

        return $dto->setData(Json::encode($subscriber->toArray()));
    }

}