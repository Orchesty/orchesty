<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Mapper;

use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class ZendeskUpdatedUserMapperAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Mapper
 */
abstract class ZendeskUpdatedUserMapperAbstract implements CustomNodeInterface
{

    /**
     * @param array $data
     *
     * @return CMSubscriber
     */
    protected function createSubscriber(array $data): CMSubscriber
    {
        $obj = new CMSubscriber();
        $obj->setEmail($data['email']);

        if (array_key_exists('name', $data)) {
            $name  = $data['name'];
            $first = '';
            $last  = $name;

            $len = strpos($name, ' ');
            if ($len !== FALSE) {
                $first = substr($name, 0, $len);
                $last  = substr($name, $len + 1);
            }

            $obj->setFirstName($first);
            $obj->setLastName($last);
        }

        if (array_key_exists('id', $data)) {
            $obj->setForeignId($data['id']);
        }

        return $obj;
    }

}