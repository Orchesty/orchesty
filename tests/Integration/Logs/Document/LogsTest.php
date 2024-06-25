<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Logs\Document;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Exception;
use Hanaboso\PipesFramework\Logs\Document\Logs;
use Hanaboso\PipesFramework\Logs\Document\Pipes;
use Hanaboso\PipesFramework\Logs\Document\Stacktrace;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class LogsTest
 *
 * @package PipesFrameworkTests\Integration\Logs\Document
 */
#[CoversClass(Logs::class)]
#[CoversClass(Pipes::class)]
#[CoversClass(Stacktrace::class)]
final class LogsTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testDocument(): void
    {
        $this->dm->createQueryBuilder(Logs::class)
            ->insert()
            ->setNewObj(
                [
                    'host'      => 'host',
                    'message'   => 'msg',
                    'pipes'     => [
                        'channel'        => 'chn',
                        'correlation_id' => '1',
                        'hostname'       => 'host',
                        'nodeName'      => 'NodeName',
                        'node_id'        => '3',
                        'service'        => 'type',
                        'severity'       => 'ERROR',
                        'stacktrace'     => [
                            'class'   => 'class',
                            'code'    => 'code',
                            'file'    => 'file',
                            'message' => 'msg',
                            'trace'   => 'trace',
                        ],
                        'timestamp'      => 2_222,
                        'topologyName'  => 'TopoName',
                        'topology_id'    => '2',
                    ],
                    'timestamp' => '1111',
                    'version'   => '1.2',

                ],
            )
            ->getQuery()
            ->execute();

        /** @var DocumentRepository<Logs> $repository */
        $repository = $this->dm->getRepository(Logs::class);
        /** @var Logs $result */
        $result = $repository->findAll()[0];
        self::assertEquals('1111', $result->getTimestamp()->getTimestamp());
        self::assertEquals('1.2', $result->getVersion());
        self::assertEquals('msg', $result->getMessage());
        self::assertEquals('host', $result->getHost());
        self::assertEquals(2_222, $result->getPipes()->getTimestamp());
        self::assertEquals('type', $result->getPipes()->getService());
        self::assertEquals('host', $result->getPipes()->getHostname());
        self::assertEquals('chn', $result->getPipes()->getChannel());
        self::assertEquals('ERROR', $result->getPipes()->getSeverity());
        self::assertEquals('1', $result->getPipes()->getCorrelationId());
        self::assertEquals('2', $result->getPipes()->getTopologyId());
        self::assertEquals('TopoName', $result->getPipes()->getTopologyName());
        self::assertEquals('3', $result->getPipes()->getNodeId());
        self::assertEquals('NodeName', $result->getPipes()->getNodeName());
        self::assertEquals('msg', $result->getPipes()->getStacktrace()->getMessage());
        self::assertEquals('class', $result->getPipes()->getStacktrace()->getClass());
        self::assertEquals('file', $result->getPipes()->getStacktrace()->getFile());
        self::assertEquals('trace', $result->getPipes()->getStacktrace()->getTrace());
        self::assertEquals('code', $result->getPipes()->getStacktrace()->getCode());
    }

}
