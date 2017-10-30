<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 30.10.17
 * Time: 15:15
 */

namespace CleverConnectors\AppBundle\Model\Requester;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;

/**
 * Trait RequesterTrait
 *
 * @package CleverConnectors\AppBundle\Model\Requester
 */
trait RequesterTrait
{

    /**
     * @param array $data
     *
     * @return WebhookSubscribes
     * @throws CleverConnectorsException
     */
    public function getWebhookSubscribe(array $data): WebhookSubscribes
    {
        /** @var WebhookSubscribes $subscription */
        $subscription = $this->getKey($data, RequesterInterface::OBJECT);

        return $subscription;
    }

    /**
     * @param array $data
     *
     * @return string
     * @throws CleverConnectorsException
     */
    public function getWebhookUrl(array $data): string
    {
        /** @var string $url */
        $url = $this->getKey($data, RequesterInterface::WEBHOOK_URL);

        return $url;
    }

    /**
     * @param array $data
     *
     * @return string
     * @throws CleverConnectorsException
     */
    public function getWebhookId(array $data): string
    {
        /** @var string $url */
        $url = $this->getKey($data, RequesterInterface::WEBHOOK_ID);

        return $url;
    }

    /**
     * ----------------------------------------- HELPERS -----------------------------------
     */

    /**
     * @param array  $data
     * @param string $key
     *
     * @return mixed
     * @throws CleverConnectorsException
     */
    private function getKey(array $data, string $key)
    {
        if (!isset($data[$key])) {
            throw new CleverConnectorsException(
                sprintf('Missing "%s" key in data.', $key),
                CleverConnectorsException::MISSING_DATA
            );
        }

        return $data[$key];
    }

}