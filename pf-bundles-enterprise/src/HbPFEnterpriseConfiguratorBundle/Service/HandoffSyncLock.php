<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Service;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\UserBundle\Document\User;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Driver\Exception\BulkWriteException;

/**
 * Class HandoffSyncLock
 *
 * Best-effort distributed lock used during cloud session handoff.
 *
 * Stops parallel handoff requests for the same instance from triggering
 * concurrent CloudUserSyncHandler::syncUsers() pulls. Implementation is
 * deliberately simple: a single Mongo collection (handoff_sync_locks)
 * keyed by instanceId with an expireAt timestamp. Stale locks are cleaned
 * up lazily on every acquire() call, so no TTL index is required.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Service
 */
final class HandoffSyncLock
{

    private const string COLLECTION = 'handoff_sync_locks';

    /**
     * HandoffSyncLock constructor.
     *
     * @param DocumentManager $dm
     * @param int             $ttlSeconds
     */
    public function __construct(private readonly DocumentManager $dm, private readonly int $ttlSeconds = 30)
    {
    }

    /**
     * Acquire the lock for the given instance.
     *
     * Returns TRUE if the caller now holds the lock, FALSE when another
     * handoff already holds a non-expired lock for the same instance.
     *
     * @param string $instanceId
     *
     * @return bool
     */
    public function acquire(string $instanceId): bool
    {
        if ($instanceId === '') {
            return FALSE;
        }

        $collection = $this->collection();

        // Lazily evict an expired lock for this key, so a stale entry from a
        // crashed previous holder can be reclaimed without a TTL index.
        $collection->deleteOne([
            'expireAt' => ['$lte' => $this->now()],
            '_id'      => $instanceId,
        ]);

        try {
            $collection->insertOne([
                'expireAt' => new UTCDateTime((time() + $this->ttlSeconds) * 1_000),
                '_id'      => $instanceId,
            ]);
        } catch (BulkWriteException) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Release the lock for the given instance.
     *
     * @param string $instanceId
     */
    public function release(string $instanceId): void
    {
        if ($instanceId === '') {
            return;
        }

        $this->collection()->deleteOne(['_id' => $instanceId]);
    }

    /**
     * @return Collection
     */
    private function collection(): Collection
    {
        // Reuse the default user database; the lock collection lives next to
        // the User collection so it shares connection pooling and credentials.
        return $this->dm->getDocumentDatabase(User::class)->selectCollection(self::COLLECTION);
    }

    /**
     * @return UTCDateTime
     */
    private function now(): UTCDateTime
    {
        return new UTCDateTime();
    }

}
