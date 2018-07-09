<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Repository;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Document\Webhook;
use Doctrine\ODM\MongoDB\Cursor;
use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Class WebhookRepository
 *
 * @package CleverConnectors\AppBundle\Repository
 */
class WebhookRepository extends DocumentRepository
{

    /**
     * @param string $userId
     * @param string $systemKey
     * @param string $topologyName
     * @param string $nodeName
     *
     * @return bool
     */
    public function isWebhookRegistred(string $userId, string $systemKey, string $topologyName, string $nodeName): bool
    {
        $result = $this->createQueryBuilder()
            ->field('user')->equals($userId)
            ->field('systemKey')->equals($systemKey)
            ->field('topologyName')->equals($topologyName)
            ->field('nodeName')->equals($nodeName)
            ->field('webhookId')->notEqual(NULL)
            ->field('unsubscribeFailed')->equals(FALSE)
            ->getQuery()->getSingleResult();

        if ($result) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * @param string $topologyName
     *
     * @return array
     */
    public function getWebhooks(string $topologyName): array
    {
        /** @var Cursor|null $webhooks */
        $webhooks = $this->createQueryBuilder()
            ->select(['systemKey'])
            ->field('topologyName')->equals($topologyName)
            ->group(['user' => 1, 'systemKey' => 2], [])
            ->reduce('function (obj, prev) {}')
            ->getQuery()->execute();

        if ($webhooks) {
            return $webhooks->toArray(TRUE);
        }

        return [];
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array|Webhook[]
     */
    public function getWebhooksForUnsubscribe(SystemInstall $systemInstall): array
    {
        /** @var Cursor|null $webhooks */
        $webhooks = $this->createQueryBuilder()
            ->field('user')->equals($systemInstall->getUser())
            ->field('systemKey')->equals($systemInstall->getSystem())
            ->field('webhookId')->notEqual(NULL)
            ->field('unsubscribeFailed')->equals(FALSE)
            ->getQuery()->execute();

        if ($webhooks) {
            return $webhooks->toArray(TRUE);
        }

        return [];
    }

}