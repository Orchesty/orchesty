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
        self::assertSame('1.2', $result->getVersion());
        self::assertSame('msg', $result->getMessage());
        self::assertSame('host', $result->getHost());
        self::assertSame(2_222, $result->getPipes()->getTimestamp());
        self::assertSame('type', $result->getPipes()->getService());
        self::assertSame('host', $result->getPipes()->getHostname());
        self::assertSame('chn', $result->getPipes()->getChannel());
        self::assertSame('ERROR', $result->getPipes()->getSeverity());
        self::assertSame('1', $result->getPipes()->getCorrelationId());
        self::assertSame('2', $result->getPipes()->getTopologyId());
        self::assertSame('TopoName', $result->getPipes()->getTopologyName());
        self::assertSame('3', $result->getPipes()->getNodeId());
        self::assertSame('NodeName', $result->getPipes()->getNodeName());
        self::assertSame('msg', $result->getPipes()->getStacktrace()->getMessage());
        self::assertSame('class', $result->getPipes()->getStacktrace()->getClass());
        self::assertSame('file', $result->getPipes()->getStacktrace()->getFile());
        self::assertSame('trace', $result->getPipes()->getStacktrace()->getTrace());
        self::assertSame('code', $result->getPipes()->getStacktrace()->getCode());
    }

}
