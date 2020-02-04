<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Logs\Document;

use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\PipesFramework\Logs\Document\Logs;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class LogsTest
 *
 * @package PipesFrameworkTests\Integration\Logs\Document
 */
final class LogsTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Logs\Document\Logs::getTimestamp
     * @covers \Hanaboso\PipesFramework\Logs\Document\Logs::getPipes
     * @covers \Hanaboso\PipesFramework\Logs\Document\Logs::getMessage
     * @covers \Hanaboso\PipesFramework\Logs\Document\Logs::getHost
     * @covers \Hanaboso\PipesFramework\Logs\Document\Logs::getVersion
     * @covers \Hanaboso\PipesFramework\Logs\Document\Pipes::getTimestamp
     * @covers \Hanaboso\PipesFramework\Logs\Document\Pipes::getType
     * @covers \Hanaboso\PipesFramework\Logs\Document\Pipes::getHostname
     * @covers \Hanaboso\PipesFramework\Logs\Document\Pipes::getChannel
     * @covers \Hanaboso\PipesFramework\Logs\Document\Pipes::getSeverity
     * @covers \Hanaboso\PipesFramework\Logs\Document\Pipes::getCorrelationId
     * @covers \Hanaboso\PipesFramework\Logs\Document\Pipes::getTopologyId
     * @covers \Hanaboso\PipesFramework\Logs\Document\Pipes::getTopologyName
     * @covers \Hanaboso\PipesFramework\Logs\Document\Pipes::getNodeId
     * @covers \Hanaboso\PipesFramework\Logs\Document\Pipes::getNodeName
     * @covers \Hanaboso\PipesFramework\Logs\Document\Pipes::getStacktrace
     * @covers \Hanaboso\PipesFramework\Logs\Document\Stacktrace::getMessage
     * @covers \Hanaboso\PipesFramework\Logs\Document\Stacktrace::getClass
     * @covers \Hanaboso\PipesFramework\Logs\Document\Stacktrace::getFile
     * @covers \Hanaboso\PipesFramework\Logs\Document\Stacktrace::getTrace
     * @covers \Hanaboso\PipesFramework\Logs\Document\Stacktrace::getCode
     * @throws MongoDBException
     */
    public function testDocument(): void
    {
        $this->dm->createQueryBuilder(Logs::class)
            ->insert()
            ->setNewObj(
                [
                    'timestamp' => '1111',
                    'version'   => '1.2',
                    'message'   => 'msg',
                    'host'      => 'host',
                    'pipes'     => [
                        'timestamp'      => '2222',
                        'type'           => 'type',
                        'hostname'       => 'host',
                        'channel'        => 'chn',
                        'severity'       => 'ERROR',
                        'correlation_id' => '1',
                        'topology_id'    => '2',
                        'topology_name'  => 'TopoName',
                        'node_id'        => '3',
                        'node_name'      => 'NodeName',
                        'stacktrace'     => [
                            'message' => 'msg',
                            'class'   => 'class',
                            'file'    => 'file',
                            'trace'   => 'trace',
                            'code'    => 'code',
                        ],
                    ],
                ]
            )
            ->getQuery()
            ->execute();

        /** @var DocumentRepository<Logs>  $repository */
        $repository = $this->dm->getRepository(Logs::class);
        /** @var Logs $result */
        $result = $repository->findAll()[0];
        self::assertEquals('1111', $result->getTimestamp()->getTimestamp());
        self::assertEquals('1.2', $result->getVersion());
        self::assertEquals('msg', $result->getMessage());
        self::assertEquals('host', $result->getHost());
        self::assertEquals('2222', $result->getPipes()->getTimestamp()->getTimestamp());
        self::assertEquals('type', $result->getPipes()->getType());
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
