<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Configurator\Model\TopologyGenerator;

use Closure;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Exception;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory;
use Hanaboso\PipesFramework\Configurator\Model\TopologyGenerator\TopologyGeneratorBridge;
use Hanaboso\PipesFramework\Configurator\Repository\ApiTokenRepository;
use Hanaboso\PipesFramework\Configurator\Repository\SdkRepository;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Repository\NodeRepository;
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
            },
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
            },
        )->runTopology('topology');
    }

    /**
     * @throws Exception
     */
    public function testStopTopology(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_GET, $request->getMethod());
                self::assertEquals('http://topology-api/v1/api/topologies/topology/host', $request->getUri(TRUE));
                self::assertEquals('', $request->getBody());

                return new ResponseDto(200, 'OK', '{"host":"http://bridge"}', []);
            },
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_DELETE, $request->getMethod());
                self::assertEquals('http://bridge/clear', $request->getUri(TRUE));
                self::assertEquals('', $request->getBody());

                return new ResponseDto(200, 'OK', '{}', []);
            },
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_PUT, $request->getMethod());
                self::assertEquals('http://topology-api/v1/api/topologies/topology', $request->getUri(TRUE));
                self::assertEquals('{"action":"stop"}', $request->getBody());

                return new ResponseDto(200, 'OK', '', []);
            },
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
            },
        )->deleteTopology('topology');
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
                    'starting-point/topologies/topology/invalidate-cache',
                    $request->getUri(TRUE),
                );
                self::assertEquals('', $request->getBody());

                return new ResponseDto(200, 'OK', '{}', []);
            },
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
                    'starting-point/topologies/topology/invalidate-cache',
                    $request->getUri(TRUE),
                );
                self::assertEquals('', $request->getBody());

                return new ResponseDto(400, 'NOT OK', '{}', []);
            },
        )->invalidateTopologyCache('topology');
    }

    /**
     * @param Closure ...$callbacks
     *
     * @return TopologyGeneratorBridge
     * @throws Exception
     */
    private function getManager(Closure ...$callbacks): TopologyGeneratorBridge
    {
        $node = (new Node())->setName('topology-1');
        $this->setProperty($node, 'id', 'test');

        $nodeRepository = self::createPartialMock(NodeRepository::class, ['getNodesByTopology']);
        $nodeRepository->method('getNodesByTopology')->willReturn([$node]);

        $documentManager = self::createPartialMock(DocumentManager::class, ['getRepository']);
        $documentManager->method('getRepository')->willReturn($nodeRepository);

        $curlManager = self::createPartialMock(CurlManager::class, ['send']);
        $curlManager->method('send')->willReturnCallback(
            static function (RequestDto $requestDto) use ($callbacks): ResponseDto {
                static $i = 0;

                return $callbacks[$i++]($requestDto);
            },
        );

        $managerLocator = self::createPartialMock(DatabaseManagerLocator::class, ['get', 'getDm']);
        $managerLocator->method('get')->willReturn($documentManager);
        $managerLocator->method('getDm')->willReturn($documentManager);

        $sdkRepository = self::createPartialMock(SdkRepository::class, ['findByHost']);
        $sdkRepository->method('findByHost')->willReturn([]);

        $apiTokenRepository = self::createPartialMock(ApiTokenRepository::class, ['findOneBy']);
        $apiTokenRepository->method('findOneBy')->willReturn(NULL);

        $dm = self::createPartialMock(DocumentManager::class, ['getRepository']);
        $dm->method('getRepository')->willReturnCallback(
            static fn(string $className): DocumentRepository => $className === Sdk::class ? $sdkRepository : $apiTokenRepository,
        );
        $configManager = new TopologyConfigFactory(self::getContainer()->getParameter('topology_configs'), $dm);

        return new TopologyGeneratorBridge(
            $managerLocator,
            $curlManager,
            $configManager,
            [
                TopologyGeneratorBridge::STARTING_POINT => 'starting-point',
                TopologyGeneratorBridge::TOPOLOGY_API   => 'topology-api',
            ],
        );
    }

}
