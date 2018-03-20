<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 3/20/18
 * Time: 11:10 AM
 */

namespace Hanaboso\PipesFramework\Logs;

use Doctrine\MongoDB\Collection;
use Doctrine\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;

/**
 * Class MongoDbStorage
 *
 * @package Hanaboso\PipesFramework\Logs
 */
class MongoDbStorage
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var string
     */
    private $storageName;

    /**
     * LogsHandler constructor.
     *
     * @param DocumentManager $dm
     * @param string          $storageName
     */
    public function __construct(DocumentManager $dm, string $storageName)
    {
        $this->dm          = $dm;
        $this->storageName = $storageName;
    }

    /**
     * @return Collection
     */
    private function getLogsCollection(): Collection
    {
        return $this->dm->getConnection()
            ->selectDatabase($this->storageName)
            ->selectCollection('Logs');
    }

    /**
     * @return Collection
     */
    private function getNodeCollection(): Collection
    {
        return $this->dm->getConnection()
            ->selectDatabase($this->storageName)
            ->selectCollection('Node');
    }

    /**
     * @param Collection $collection
     * @param string     $limit
     * @param string     $offset
     *
     * @return Query
     */
    private function createLogsQuery(Collection $collection, string $limit, string $offset): Query
    {
        return $collection
            ->createQueryBuilder()
            ->field('pipes.severity')->in([
                'error', 'warning', 'alert', 'critical', 'ERROR', 'WARNING', 'ALERT', 'CRITICAL',
            ])
            ->limit((int) $limit)
            ->skip((int) $offset)
            ->getQuery();
    }

    /**
     * @param Collection $collection
     * @param array      $correlationIds
     *
     * @return Query
     */
    private function createStartingPointQuery(Collection $collection, array $correlationIds): Query
    {
        return $collection
            ->createQueryBuilder()
            ->field('pipes.correlation_id')->in($correlationIds)
            ->field('pipes.type')->equals('starting_point')
            ->getQuery();
    }

    /**
     * @param Collection $collection
     * @param string     $nodeId
     *
     * @return Query
     */
    private function createNodeQuery(Collection $collection, string $nodeId): Query
    {
        return $collection
            ->createQueryBuilder()
            ->field('_id')->equals(new ObjectId($nodeId))
            ->getQuery();
    }

    /**
     * @param string $limit
     * @param string $offset
     *
     * @return Query
     */
    public function getLogsQuery(string $limit, string $offset): Query
    {
        return $this->createLogsQuery($this->getLogsCollection(), $limit, $offset);
    }

    /**
     * @param array $correlationIds
     *
     * @return Query
     */
    public function getStartingPointQuery(array $correlationIds): Query
    {
        return $this->createStartingPointQuery($this->getLogsCollection(), $correlationIds);
    }

    /**
     * @param string $nodeId
     *
     * @return array
     */
    public function getNodeData(string $nodeId): array
    {
        $node = $this->createNodeQuery($this->getNodeCollection(), $nodeId)->getSingleResult();

        if (!is_array($node)) {
            return [];
        }

        return $node;
    }

}