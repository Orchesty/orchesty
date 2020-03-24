<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Configurator\Model\TopologyGenerator;

use Closure;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Model\TopologyGenerator\TopologyGeneratorBridge;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Repository\NodeRepository;
use Hanaboso\PipesPhpSdk\Database\Repository\TopologyRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class TopologyGeneratorBridgeTest
 *
 * @package PipesFrameworkTests\Unit\Configurator\Model\TopologyGenerator
 */
final class TopologyGeneratorBridgeTest extends KernelTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGenerateTopology(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_POST, $request->getMethod());
                self::assertEquals('http://topology-api/v1/api/topologies/topology', $request->getUri(TRUE));

                return new ResponseDto(200, 'OK', '{}', []);
            }
        )->generateTopology('topology');
    }

    /**
     * @throws Exception
     */
    public function testRunTopology(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_PUT, $request->getMethod());
                self::assertEquals('http://topology-api/v1/api/topologies/topology', $request->getUri(TRUE));
                self::assertEquals('{"action":"start"}', $request->getBody());

                return new ResponseDto(200, 'OK', '', []);
            }
        )->runTopology('topology');
    }

    /**
     * @throws Exception
     */
    public function testStopTopology(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_PUT, $request->getMethod());
                self::assertEquals('http://topology-api/v1/api/topologies/topology', $request->getUri(TRUE));
                self::assertEquals('{"action":"stop"}', $request->getBody());

                return new ResponseDto(200, 'OK', '', []);
            }
        )->stopTopology('topology');
    }

    /**
     * @throws Exception
     */
    public function testDeleteTopology(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_DELETE, $request->getMethod());
                self::assertEquals('http://topology-api/v1/api/topologies/topology', $request->getUri(TRUE));
                self::assertEquals('', $request->getBody());

                return new ResponseDto(200, 'OK', '', []);
            }
        )->deleteTopology('topology');
    }

    /**
     * @throws Exception
     */
    public function testInfoTopology(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_GET, $request->getMethod());
                self::assertEquals('http://topology-api/v1/api/topologies/topology', $request->getUri(TRUE));
                self::assertEquals('', $request->getBody());

                return new ResponseDto(200, 'OK', '', []);
            }
        )->infoTopology('topology');
    }

    /**
     * @throws Exception
     */
    public function testRunTest(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_GET, $request->getMethod());
                self::assertEquals('http://multi-probe/topology/status?topologyId=topology', $request->getUri(TRUE));
                self::assertEquals('', $request->getBody());

                return new ResponseDto(200, 'OK', '{}', []);
            }
        )->runTest('topology');
    }

    /**
     * @throws Exception
     */
    public function testInvalidateTopologyCache(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_POST, $request->getMethod());
                self::assertEquals(
                    'http://starting-point/topologies/topology/invalidate-cache',
                    $request->getUri(TRUE)
                );
                self::assertEquals('', $request->getBody());

                return new ResponseDto(200, 'OK', '{}', []);
            }
        )->invalidateTopologyCache('topology');
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyGenerator\TopologyGeneratorBridge::invalidateTopologyCache
     *
     * @throws Exception
     */
    public function testInvalidateTopologyCacheErr(): void
    {
        self::expectException(CurlException::class);

        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_POST, $request->getMethod());
                self::assertEquals(
                    'http://starting-point/topologies/topology/invalidate-cache',
                    $request->getUri(TRUE)
                );
                self::assertEquals('', $request->getBody());

                return new ResponseDto(400, 'NOT OK', '{}', []);
            }
        )->invalidateTopologyCache('topology');
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyGenerator\TopologyGeneratorBridge::runTest
     *
     * @throws Exception
     */
    public function testRunTestErr(): void
    {
        self::expectException(CurlException::class);

        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_GET, $request->getMethod());
                self::assertEquals('http://multi-probe/topology/status?topologyId=topology', $request->getUri(TRUE));
                self::assertEquals('', $request->getBody());

                return new ResponseDto(400, 'NOT OK', '{}', []);
            }
        )->runTest('topology');
    }

    /**
     * @param Closure $callback
     *
     * @return TopologyGeneratorBridge
     * @throws Exception
     */
    private function getManager(Closure $callback): TopologyGeneratorBridge
    {
        $node = (new Node())->setName('topology-1');
        $this->setProperty($node, 'id', 'test');

        /** @var TopologyRepository|MockObject $nodeRepository */
        $nodeRepository = self::createPartialMock(NodeRepository::class, ['getNodesByTopology']);
        $nodeRepository->method('getNodesByTopology')->willReturn([$node]);

        /** @var DocumentManager|MockObject $documentManager */
        $documentManager = self::createPartialMock(DocumentManager::class, ['getRepository']);
        $documentManager->method('getRepository')->willReturn($nodeRepository);

        /** @var CurlManager|MockObject $curlManager */
        $curlManager = self::createPartialMock(CurlManager::class, ['send']);
        $curlManager->method('send')->willReturnCallback($callback);

        /** @var DatabaseManagerLocator|MockObject $managerLocator */
        $managerLocator = self::createPartialMock(DatabaseManagerLocator::class, ['get', 'getDm']);
        $managerLocator->method('get')->willReturn($documentManager);
        $managerLocator->method('getDm')->willReturn($documentManager);

        return new TopologyGeneratorBridge(
            $managerLocator,
            $curlManager,
            self::$container->get('hbpf.topology.configurator'),
            [
                TopologyGeneratorBridge::STARTING_POINT => 'starting-point',
                TopologyGeneratorBridge::TOPOLOGY_API   => 'topology-api',
                TopologyGeneratorBridge::MULTI_PROBE    => 'multi-probe',
            ]
        );
    }

}
