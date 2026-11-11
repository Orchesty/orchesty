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

    protected const string ID                   = 'id';
    protected const string _ID                  = '_id';
    protected const string CORRELATIONID        = 'correlation_id';
    protected const string CORRELATION_ID       = 'correlation_id';
    protected const string TOPOLOGYID           = 'topology_id';
    protected const string TOPOLOGY_ID          = 'topology_id';
    protected const string TOPOLOGYNAME         = 'topologyName';
    protected const string TOPOLOGY_NAME        = 'topology_name';
    protected const string TOPOLOGY_DESCRIPTION = 'topology_description';
    protected const string NODEID               = 'node_id';
    protected const string NODE_ID              = 'node_id';
    protected const string NODENAME             = 'nodeName';
    protected const string NODE_NAME            = 'node_name';
    protected const string TIMESTAMP_PREFIX     = '@timestamp';
    protected const string TIMESTAMP            = 'timestamp';
    protected const string PIPES                = 'pipes';
    protected const string SEVERITY             = 'severity';
    protected const string LEVEL                = 'severity';
    protected const string MESSAGE              = 'message';
    protected const string SERVICE              = 'service';
    protected const int LIMIT                   = 1_000;

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
     * @throws Exception
     * @throws MongoDBException
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
                $topology = $this->getTopology($topologyId);
                if($topology !== NULL){
                    $result[$key][self::TOPOLOGY_NAME]        = $topology->getName();
                    $result[$key][self::TOPOLOGY_DESCRIPTION] = $topology->getDescr();
                }else {
                    $result[$key][self::TOPOLOGY_NAME]        = '';
                    $result[$key][self::TOPOLOGY_DESCRIPTION] = '';
                }
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
     * @return ?Topology
     */
    protected function getTopology(string $topologyId): ?Topology
    {
        try {
            /** @var Topology|null $topology */
            $topology = $this->dm->getRepository(Topology::class)->findOneBy(
                [self::ID => new ObjectId(explode('-', $topologyId)[0])],
            );

            return $topology;
        } catch (InvalidArgumentException $e) {
            $e;

            return NULL;
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
