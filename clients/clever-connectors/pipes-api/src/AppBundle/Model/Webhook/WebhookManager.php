<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Webhook;

use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Webhook\Provider\ApiWebhookProvider;
use CleverConnectors\AppBundle\Model\Webhook\Provider\UiWebhookProvider;
use CleverConnectors\AppBundle\Model\Webhook\Provider\WebhookProviderInterface;

/**
 * Class WebhookManager
 *
 * @package CleverConnectors\AppBundle\Model\Webhook
 */
class WebhookManager
{

    /**
     * @var WebhookProviderInterface[]
     */
    private $providers = [];

    /**
     * WebhookManager constructor.
     *
     * @param ApiWebhookProvider $apiWebhookProvider
     * @param UiWebhookProvider  $uiWebhookProvider
     */
    function __construct(ApiWebhookProvider $apiWebhookProvider, UiWebhookProvider $uiWebhookProvider)
    {
        $this->providers[SystemTypeEnum::WEBHOOK]    = $apiWebhookProvider;
        $this->providers[SystemTypeEnum::UI_WEBHOOK] = $uiWebhookProvider;
    }

    /**
     * @param WebhookSystemInterface $system
     * @param string                 $userId
     * @param string                 $token
     * @param bool                   $isUpdate
     */
    public function subscribe(WebhookSystemInterface $system, string $userId, string $token, $isUpdate = FALSE): void
    {
        $provider = $this->getProvider($system);

        $provider->subscribe($system, $userId, $token, $isUpdate);
    }

    /**
     * @param WebhookSystemInterface $system
     * @param string                 $userId
     */
    public function unsubscribe(WebhookSystemInterface $system, string $userId): void
    {
        $provider = $this->getProvider($system);
        $provider->unsubscribe($system, $userId);
    }

    /**
     * @param WebhookSystemInterface $system
     * @param string                 $userId
     * @param string                 $token
     */
    public function update(WebhookSystemInterface $system, string $userId, string $token): void
    {
        $provider = $this->getProvider($system);
        $provider->update($system, $userId, $token);
    }

    /**
     * ------------------------------------- HELPERS ---------------------------------------
     */

    /**
     * @param WebhookSystemInterface $system
     *
     * @return WebhookProviderInterface
     * @throws SystemException
     */
    private function getProvider(WebhookSystemInterface $system): WebhookProviderInterface
    {
        if (array_key_exists($system->getType(), $this->providers)) {
            return $this->providers[$system->getType()];
        }

        throw new SystemException(sprintf('Unknown webhook provider ["%s"]', $system->getType()));
    }

}