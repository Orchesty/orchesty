<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\CMGetListSubscribersSocialConnector;

/**
 * Class FacebookaudienceGetCMListSubscribers
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
class FacebookaudienceGetCMListSubscribers extends CMGetListSubscribersSocialConnector
{

    /**
     * @param string $email
     *
     * @return string
     */
    protected function subscriberCallback(string $email): string
    {
        return hash('sha256', $email);
    }

}