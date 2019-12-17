<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\CommonsBundle\Database\Document\Node;
use Hanaboso\MongoDataGrid\GridRequestDto;
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
    protected const CORRELATION_ID   = 'correlation_id';
    protected const TOPOLOGY_ID      = 'topology_id';
    protected const TOPOLOGY_NAME    = 'topology_name';
    protected const NODE_ID          = 'node_id';
    protected const NODE_NAME        = 'node_name';
    protected const TIMESTAMP_PREFIX = '@timestamp';
    protected const TIMESTAMP        = 'timestamp';
    protected const PIPES            = 'pipes';
    protected const SEVERITY         = 'severity';
    protected const MESSAGE          = 'message';
    protected const TYPE             = 'type';
    protected const LIMIT            = 1000;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var StartingPointsFilter
     */
    private $startingPointsFilter;

    /**
     * LogsAbstract constructor.
     *
     * @param DocumentManager      $dm
     * @param StartingPointsFilter $startingPointsFilter
     */
    public function __construct(DocumentManager $dm, StartingPointsFilter $startingPointsFilter)
    {
        $this->dm                   = $dm;
        $this->startingPointsFilter = $startingPointsFilter;
    }

    /**
     * @param GridRequestDto $dto
     * @param mixed[]        $result
     *
     * @return mixed[]
     * @throws MongoDBException
     * @throws Exception
     */
    protected function processStartingPoints(GridRequestDto $dto, array $result): array
    {
        $data        = $this->startingPointsFilter->getData($dto)->toArray();
        $innerResult = [];

        foreach ($data as $item) {
            $innerResult[$item[self::PIPES][self::CORRELATION_ID]] = $item;
        }

        foreach ($result as $key => $item) {
            $correlationId = $this->getNonEmptyValue($item, self::CORRELATION_ID);
            $nodeId        = $this->getNonEmptyValue($item, self::NODE_ID);

            if (is_array($correlationId)) {
                throw new LockException('Bad data format.');
            }

            if (is_array($nodeId)) {
                throw new LockException('Bad data format.');
            }

            if ($correlationId && $this->getNonEmptyValue($innerResult, $correlationId)) {
                $result[$key][self::TOPOLOGY_ID]   = $innerResult[$correlationId][self::PIPES][self::TOPOLOGY_ID];
                $result[$key][self::TOPOLOGY_NAME] = $innerResult[$correlationId][self::PIPES][self::TOPOLOGY_NAME];
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
            /** @var Node|NULL $node */
            $node = $this->dm->getRepository(Node::class)->findOneBy(['_id' => new ObjectId(explode('-', $nodeId)[0])]);

            return $node ? $node->getName() : '';
        } catch (InvalidArgumentException $e) {
            return '';
        }
    }

    /**
     * @param mixed[] $data
     * @param string  $property
     *
     * @return mixed[]|string|NULL
     */
    protected function getNonEmptyValue(array $data, string $property)
    {
        return array_key_exists($property, $data) && $data[$property] !== '' ? $data[$property] : NULL;
    }

}