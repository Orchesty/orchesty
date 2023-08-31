<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Configurator\Cron;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Exception\CronException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Cron\CronManager;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\PipesPhpSdk\Database\Repository\TopologyRepository;
use Hanaboso\Utils\String\Json;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class CronManagerTest
 *
 * @package PipesFrameworkTests\Unit\Configurator\Cron
 */
final class CronManagerTest extends KernelTestCaseAbstract
{

    private const COM1 = 'echo "[CRON] [$(date +"%Y-%m-%dT%TZ")] Requesting http://example.com/topologies/test/nodes/id-1/run: $(curl -H "orchesty-api-key: 123" -H "Accept: application/json" -H "Content-Type: application/json" -X POST -d "{"params":"abc"}" http://example.com/topologies/test/nodes/id-1/run)" &> /proc/1/fd/1';
    private const COM2 = 'echo "[CRON] [$(date +"%Y-%m-%dT%TZ")] Requesting http://example.com/topologies/test/nodes/id-2/run: $(curl -H "orchesty-api-key: 123" -H "Accept: application/json" -H "Content-Type: application/json" -X POST -d "{"params":"abc"}" http://example.com/topologies/test/nodes/id-2/run)" &> /proc/1/fd/1';
    private const COM3 = 'echo "[CRON] [$(date +"%Y-%m-%dT%TZ")] Requesting http://example.com/topologies/test/nodes/id-3/run: $(curl -H "orchesty-api-key: 123" -H "Accept: application/json" -H "Content-Type: application/json" -X POST -d "{"params":"abc"}" http://example.com/topologies/test/nodes/id-3/run)" &> /proc/1/fd/1';

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getAll
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::sendAndProcessRequest
     *
     * @throws Exception
     */
    public function testGetAll(): void
    {
        $data = $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_GET, $request->getMethod());
                self::assertEquals('http://example.com/crons', $request->getUri(TRUE));

                return new ResponseDto(200, 'OK', '[{"name":"Name", "time":"*/1 * * * *"}]', []);
            },
        )->getAll();

        self::assertEquals(
            [
                [
                    'name' => 'Name',
                    'time' => '*/1 * * * *',
                ],
            ],
            Json::decode($data->getBody()),
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::create
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getUrl
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getCommand
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getTopologyAndNode
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::sendAndProcessRequest
     *
     * @throws Exception
     */
    public function testCreate(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_POST, $request->getMethod());
                self::assertEquals('http://example.com/crons', $request->getUri(TRUE));
                self::assertEquals(
                    [
                        'topology' => 'topology-1',
                        'node'     => 'node-1',
                        'time'     => '1 1 1 1 1',
                        'command'  => self::COM1,
                    ],
                    Json::decode($request->getBody()),
                );

                return new ResponseDto(200, 'OK', '', []);
            },
        )->create($this->getNode());
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::update
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getUrl
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getHash
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::sendAndProcessRequest
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getCommand
     *
     * @throws Exception
     */
    public function testUpdate(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_PUT, $request->getMethod());
                self::assertEquals('http://example.com/crons/test/id-1', $request->getUri(TRUE));
                self::assertEquals(
                    [
                        'time'    => '1 1 1 1 1',
                        'command' => self::COM1,
                    ],
                    Json::decode($request->getBody()),
                );

                return new ResponseDto(200, 'OK', '', []);
            },
        )->update($this->getNode());
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::patch
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getHash
     * @covers \Hanaboso\PipesFramework\Configurator\Utils\CronUtils::getHash
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getCommand
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::sendAndProcessRequest
     *
     * @throws Exception
     */
    public function testPatch(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_PATCH, $request->getMethod());
                self::assertEquals('http://example.com/crons/test/id-1', $request->getUri(TRUE));
                self::assertEquals(
                    [
                        'time'    => '1 1 1 1 1',
                        'command' => self::COM1,
                    ],
                    Json::decode($request->getBody()),
                );

                return new ResponseDto(200, 'OK', '', []);
            },
        )->patch($this->getNode());
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::patch
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getHash
     * @covers \Hanaboso\PipesFramework\Configurator\Utils\CronUtils::getHash
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getCommand
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::sendAndProcessRequest
     *
     * @throws Exception
     */
    public function testPatchEmpty(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_PATCH, $request->getMethod());
                self::assertEquals('http://example.com/crons/test/id-1', $request->getUri(TRUE));
                self::assertEquals('{}', $request->getBody());

                return new ResponseDto(200, 'OK', '', []);
            },
        )->patch($this->getNode(), TRUE);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::delete
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getUrl
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getHash
     * @covers \Hanaboso\PipesFramework\Configurator\Utils\CronUtils::getHash
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::sendAndProcessRequest
     *
     * @throws Exception
     */
    public function testDelete(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_DELETE, $request->getMethod());
                self::assertEquals('http://example.com/crons/test/id-1', $request->getUri(TRUE));
                self::assertEmpty($request->getBody());

                return new ResponseDto(200, 'OK', '', []);
            },
        )->delete($this->getNode());
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::batchCreate
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::processNodes
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getCommand
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getTopologyAndNode
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::sendAndProcessRequest
     *
     * @throws Exception
     */
    public function testBatchCreate(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_POST, $request->getMethod());
                self::assertEquals('http://example.com/crons-batches', $request->getUri(TRUE));
                self::assertEquals(
                    [
                        [
                            'topology' => 'topology-1',
                            'node'     => 'node-1',
                            'time'     => '1 1 1 1 1',
                            'command'  => self::COM1,
                        ], [
                            'topology' => 'topology-1',
                            'node'     => 'node-2',
                            'time'     => '2 2 2 2 2',
                            'command'  => self::COM2,
                        ], [
                            'topology' => 'topology-1',
                            'node'     => 'node-3',
                            'time'     => '3 3 3 3 3',
                            'command'  => self::COM3,
                        ],
                    ],
                    Json::decode($request->getBody()),
                );

                return new ResponseDto(200, 'OK', '', []);
            },
        )->batchCreate($this->getNodes(3));
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::batchUpdate
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::processNodes
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getTopologyAndNode
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::sendAndProcessRequest
     *
     * @throws Exception
     */
    public function testBatchUpdate(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_PUT, $request->getMethod());
                self::assertEquals('http://example.com/crons-batches', $request->getUri(TRUE));
                self::assertEquals(
                    [
                        [
                            'topology' => 'topology-1',
                            'node'     => 'node-1',
                            'time'     => '1 1 1 1 1',
                            'command'  => self::COM1,
                        ], [
                            'topology' => 'topology-1',
                            'node'     => 'node-2',
                            'time'     => '2 2 2 2 2',
                            'command'  => self::COM2,
                        ], [
                            'topology' => 'topology-1',
                            'node'     => 'node-3',
                            'time'     => '3 3 3 3 3',
                            'command'  => self::COM3,
                        ],
                    ],
                    Json::decode($request->getBody()),
                );

                return new ResponseDto(200, 'OK', '', []);
            },
        )->batchUpdate($this->getNodes(3));
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::batchPatch
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::sendAndProcessRequest
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::processNodes
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getTopologyAndNode
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getCommand
     *
     * @throws Exception
     */
    public function testBatchPatch(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_PATCH, $request->getMethod());
                self::assertEquals('http://example.com/crons-batches', $request->getUri(TRUE));
                self::assertEquals(
                    [
                        [
                            'topology' => 'topology-1',
                            'node'     => 'node-1',
                            'time'     => '1 1 1 1 1',
                            'command'  => self::COM1,
                        ], [
                            'topology' => 'topology-1',
                            'node'     => 'node-2',
                            'time'     => '2 2 2 2 2',
                            'command'  => self::COM2,
                        ], [
                            'topology' => 'topology-1',
                            'node'     => 'node-3',
                            'time'     => '3 3 3 3 3',
                            'command'  => self::COM3,
                        ],
                    ],
                    Json::decode($request->getBody()),
                );

                return new ResponseDto(200, 'OK', '', []);
            },
        )->batchPatch($this->getNodes(3));
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::batchPatch
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::sendAndProcessRequest
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::processNodes
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getTopologyAndNode
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getCommand
     *
     * @throws Exception
     */
    public function testBatchPatchEmpty(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_PATCH, $request->getMethod());
                self::assertEquals('http://example.com/crons-batches', $request->getUri(TRUE));
                self::assertEquals(
                    [
                        [
                            'topology' => 'topology-1',
                            'node'     => 'node-1',
                        ], [
                            'topology' => 'topology-1',
                            'node'     => 'node-2',
                        ], [
                            'topology' => 'topology-1',
                            'node'     => 'node-3',
                        ],
                    ],
                    Json::decode($request->getBody()),
                );

                return new ResponseDto(200, 'OK', '', []);
            },
        )->batchPatch($this->getNodes(3), TRUE);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::batchDelete
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::sendAndProcessRequest
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::processNodes
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getCommand
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getTopologyAndNode
     *
     * @throws Exception
     */
    public function testBatchDelete(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_DELETE, $request->getMethod());
                self::assertEquals('http://example.com/crons-batches', $request->getUri(TRUE));
                self::assertEquals(
                    [
                        ['topology' => 'topology-1', 'node' => 'node-1'],
                        ['topology' => 'topology-1', 'node' => 'node-2'],
                        ['topology' => 'topology-1', 'node' => 'node-3'],
                    ],
                    Json::decode($request->getBody()),
                );

                return new ResponseDto(200, 'OK', '', []);
            },
        )->batchDelete($this->getNodes(3));
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::create
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getCommand
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::getTopologyAndNode
     * @covers \Hanaboso\PipesFramework\Configurator\Cron\CronManager::sendAndProcessRequest
     *
     * @throws Exception
     */
    public function testRequestFail(): void
    {
        self::expectException(CronException::class);
        self::expectExceptionCode(CronException::CRON_EXCEPTION);
        self::expectExceptionMessageMatches('#Cron API failed: .+#');

        $this->getManager(
            static function (): void {
                throw new CurlException(
                    'Client error: `GET http://example.com/cron-api/create` resulted in a `406 Not Acceptable` response: Response',
                    CurlException::REQUEST_FAILED,
                );
            },
        )->create($this->getNode());
    }

    /**
     * @param callable $callback
     *
     * @return CronManager
     * @throws Exception
     */
    private function getManager(callable $callback): CronManager
    {
        $topology = (new Topology())
            ->setName('topology-1')
            ->setVersion(1);

        $this->setProperty($topology, 'id', 'test');

        $topologyRepository = self::createPartialMock(TopologyRepository::class, ['findOneBy']);
        $topologyRepository->method('findOneBy')->willReturn($topology);

        $documentManager = self::createPartialMock(DocumentManager::class, ['getRepository']);
        $documentManager->method('getRepository')->willReturn($topologyRepository);

        $curlManager = self::createPartialMock(CurlManager::class, ['send']);
        $curlManager->method('send')->willReturnCallback($callback);

        return new CronManager($documentManager, $curlManager, 'http://example.com/', 'http://example.com/', '123');
    }

    /**
     * @param int $count
     *
     * @return Node[]
     * @throws Exception
     */
    private function getNodes(int $count = 1): array
    {

        $nodes = [];

        for ($i = 1; $i <= $count; $i++) {
            $node = (new Node())
                ->setName(sprintf('node-%s', $i))
                ->setTopology(sprintf('topology-%s', $i))
                ->setType(TypeEnum::CRON)
                ->setCronParams('"params":"abc"')
                ->setCron(sprintf('%s %s %s %s %s', $i, $i, $i, $i, $i));
            $this->setProperty($node, 'id', sprintf('id-%s', $i));
            $nodes[] = $node;
        }

        return $nodes;
    }

    /**
     * @return Node
     * @throws Exception
     */
    private function getNode(): Node
    {
        return $this->getNodes()[0];
    }

}
