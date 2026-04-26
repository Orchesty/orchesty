<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\PipesFramework\Application\Document\Webhook;

/**
 * Class WebhookRepository
 *
 * @package Hanaboso\PipesFramework\Application\Repository
 *
 * @phpstan-extends DocumentRepository<Webhook>
 */
final class WebhookRepository extends DocumentRepository
{

    /**
     * Live (non soft-deleted) webhooks for a topology.
     *
     * Worker-api stores soft-deletes by stamping `deleted` with an ISODate
     * (active records keep `deleted: false`). The PHP Webhook document doesn't
     * map the `deleted` field, so a plain `findBy(['topology' => $name])`
     * silently returns soft-deleted records too — making the UI report
     * "Subscribed" even after a successful unsubscribe.
     *
     * @return Webhook[]
     */
    public function findActiveByTopology(string $topology): array
    {
        return $this->createQueryBuilder()
            ->field('topology')->equals($topology)
            ->field('deleted')->equals(FALSE)
            ->getQuery()
            ->toArray();
    }

    /**
     * Live (non soft-deleted) webhook for a single (topology, node, name)
     * triple. Used by {@see WebhookConfigManager::isRegistered} to short-
     * circuit duplicate (un)subscribe calls — the previous `findOneBy(...)`
     * lookup did not filter `deleted`, so once a webhook had been registered
     * + unsubscribed once, the manager would forever believe it was still
     * live and reply with `noop: already-subscribed` to every retry.
     */
    public function findActiveOne(string $topology, string $node, string $name): ?Webhook
    {
        $result = $this->createQueryBuilder()
            ->field('topology')->equals($topology)
            ->field('node')->equals($node)
            ->field('name')->equals($name)
            ->field('deleted')->equals(FALSE)
            ->limit(1)
            ->getQuery()
            ->toArray();

        return $result[0] ?? NULL;
    }

}
