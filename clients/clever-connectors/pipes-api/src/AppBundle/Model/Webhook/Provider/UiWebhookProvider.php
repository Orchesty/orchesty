<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 23.10.17
 * Time: 15:22
 */

namespace CleverConnectors\AppBundle\Model\Webhook\Provider;

use CleverConnectors\AppBundle\Document\Webhook;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSystemInterface;
use CleverConnectors\AppBundle\Repository\WebhookRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Class UiWebhookProvider
 *
 * @package CleverConnectors\AppBundle\Model\Webhook\Provider
 */
class UiWebhookProvider implements WebhookProviderInterface
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var ObjectRepository|WebhookRepository
     */
    private $webhookRepository;

    /**
     * ApiWebhookProvider constructor.
     *
     * @param DocumentManager $dm
     */
    function __construct(DocumentManager $dm)
    {
        $this->dm                = $dm;
        $this->webhookRepository = $dm->getRepository(Webhook::class);
    }

    /**
     * @param WebhookSystemInterface $system
     * @param string                 $userId
     * @param string                 $token
     * @param bool                   $isUpdate
     */
    public function subscribe(WebhookSystemInterface $system, string $userId, string $token, $isUpdate = FALSE): void
    {
        /** @var WebhookSubscribes $sub */
        foreach ($system->getWebhookSubscribes() as $sub) {
            if (!$isUpdate && $this->webhookRepository->isWebhookRegistred(
                    $userId,
                    $system->getKey(),
                    $sub->getTopologyName(),
                    $sub->getNodeName()
                )
            ) {
                continue;
            }

            $doc = new Webhook();
            $doc->setUser($userId)
                ->setNodeName($sub->getNodeName())
                ->setSystemKey($system->getKey())
                ->setTopologyName($sub->getTopologyName())
                ->setApiReq(FALSE);
            $this->dm->persist($doc);
        }
        $this->dm->flush();
    }

    /**
     * @param WebhookSystemInterface $system
     * @param string                 $userId
     */
    public function unsubscribe(WebhookSystemInterface $system, string $userId): void
    {
        /** @var Webhook[] $webhooks */
        $webhooks = $this->webhookRepository->findBy([
            'user'      => $userId,
            'systemKey' => $system->getKey(),
        ]);

        foreach ($webhooks as $webhook) {
            $this->dm->remove($webhook);
        }
        $this->dm->flush();
    }

    /**
     * @param WebhookSystemInterface $system
     * @param string                 $userId
     * @param string                 $token
     */
    public function update(WebhookSystemInterface $system, string $userId, string $token): void
    {
        $this->unsubscribe($system, $userId);
        $this->subscribe($system, $userId, $token);
    }

}