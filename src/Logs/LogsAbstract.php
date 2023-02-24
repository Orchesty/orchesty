<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use MongoDB\BSON\ObjectId;
use MongoDB\Driver\Exception\InvalidArgumentException;

/**
 * Class LogsAbstract
 *
 * @package Hanaboso\PipesFramework\Logs
 */
abstract class LogsAbstract implements LogsInterface
{

    protected const ID               = 'id';
    protected const _ID              = '_id';
    protected const CORRELATIONID    = 'correlation_id';
    protected const CORRELATION_ID   = 'correlation_id';
    protected const TOPOLOGYID       = 'topology_id';
    protected const TOPOLOGY_ID      = 'topology_id';
    protected const TOPOLOGYNAME     = 'topologyName';
    protected const TOPOLOGY_NAME    = 'topology_name';
    protected const NODEID           = 'node_id';
    protected const NODE_ID          = 'node_id';
    protected const NODENAME         = 'nodeName';
    protected const NODE_NAME        = 'node_name';
    protected const TIMESTAMP_PREFIX = '@timestamp';
    protected const TIMESTAMP        = 'timestamp';
    protected const PIPES            = 'pipes';
    protected const SEVERITY         = 'severity';
    protected const LEVEL            = 'severity';
    protected const MESSAGE          = 'message';
    protected const SERVICE          = 'service';
    protected const LIMIT            = 1_000;

    /**
     * LogsAbstract constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(private DocumentManager $dm)
    {
    }

    /**
     * @param mixed[] $result
     *
     * @return mixed[]
     * @throws MongoDBException
     * @throws Exception
     */
    protected function processStartingPoints(array $result): array
    {
        foreach ($result as $key => $item) {
            $correlationId = $this->getNonEmptyValue($item, self::CORRELATIONID);
            $nodeId        = $this->getNonEmptyValue($item, self::NODE_ID);
            $topologyId    = $this->getNonEmptyValue($item, self::TOPOLOGY_ID);

            if (is_array($correlationId) || is_array($topologyId) || is_array($nodeId)) {
                throw new LockException('Bad data format.');
            }

            if ($topologyId){
                $result[$key][self::TOPOLOGY_NAME] = $this->getTopologyName($topologyId);
            }

            if ($nodeId) {
                $result[$key][self::NODE_NAME] = $this->getNodeName($nodeId);
            }
        }

        return $result;
    }

    /**
     * @param string $nodeId
     *
     * @return string
     */
    protected function getNodeName(string $nodeId): string
    {
        try {
            /** @var Node|null $node */
            $node = $this->dm->getRepository(Node::class)->findOneBy(
                [self::ID => new ObjectId(explode('-', $nodeId)[0])],
            );

            return $node ? $node->getName() : '';
        } catch (InvalidArgumentException $e) {
            $e;

            return '';
        }
    }

    /**
     * @param string $topologyId
     *
     * @return string
     */
    protected function getTopologyName(string $topologyId): string
    {
        try {
            /** @var Topology|null $topology */
            $topology = $this->dm->getRepository(Topology::class)->findOneBy(
                [self::ID => new ObjectId(explode('-', $topologyId)[0])],
            );

            return $topology ? $topology->getName() : '';
        } catch (InvalidArgumentException $e) {
            $e;

            return '';
        }
    }

    /**
     * @param mixed[] $data
     * @param string  $property
     *
     * @return mixed[]|string|null
     */
    protected function getNonEmptyValue(array $data, string $property): array|string|NULL
    {
        return array_key_exists($property, $data) && $data[$property] !== '' ? $data[$property] : NULL;
    }

}
