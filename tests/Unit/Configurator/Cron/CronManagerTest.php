<?php declare(strict_types=1);

namespace Tests\Unit\Configurator\Cron;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Document\Node;
use Hanaboso\CommonsBundle\Document\Topology;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Exception\CronException;
use Hanaboso\CommonsBundle\Repository\TopologyRepository;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Cron\CronManager;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class CronManagerTest
 *
 * @package Tests\Unit\Configurator\Cron
 */
final class CronManagerTest extends KernelTestCaseAbstract
{

    private const COM1 = 'curl -H "Accept: application/json" -H "Content-Type: application/json" -X POST -d \'{"params":"abc"}\' http://example.com/topologies/topology-1/nodes/node-1/run-by-name';
    private const COM2 = 'curl -H "Accept: application/json" -H "Content-Type: application/json" -X POST -d \'{"params":"abc"}\' http://example.com/topologies/topology-1/nodes/node-2/run-by-name';
    private const COM3 = 'curl -H "Accept: application/json" -H "Content-Type: application/json" -X POST -d \'{"params":"abc"}\' http://example.com/topologies/topology-1/nodes/node-3/run-by-name';

    /**
     * @throws Exception
     */
    public function testGetAll(): void
    {
        $data = $this->getManager(function (RequestDto $request): ResponseDto {
            self::assertEquals(CurlManager::METHOD_GET, $request->getMethod());
            self::assertEquals('http://example.com/cron-api/get_all', $request->getUri(TRUE));

            return new ResponseDto(200, 'OK', '[{"name":"Name", "time":"*/1 * * * *"}]', []);
        })->getAll();

        self::assertEquals([
            [
                'name' => 'Name',
                'time' => '*/1 * * * *',
            ],
        ], json_decode($data->getBody(), TRUE, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * @throws Exception
     */
    public function testCreate(): void
    {
        $this->getManager(function (RequestDto $request): ResponseDto {
            self::assertEquals(CurlManager::METHOD_POST, $request->getMethod());
            self::assertEquals('http://example.com/cron-api/create', $request->getUri(TRUE));
            self::assertEquals([
                'topology' => 'topology-1',
                'node'     => 'node-1',
                'time'     => '1 1 1 1 1',
                'command'  => self::COM1,
            ], json_decode($request->getBody(), TRUE));

            return new ResponseDto(200, 'OK', '', []);
        })->create($this->getNode());
    }

    /**
     * @throws Exception
     */
    public function testUpdate(): void
    {
        $this->getManager(function (RequestDto $request): ResponseDto {
            self::assertEquals(CurlManager::METHOD_POST, $request->getMethod());
            self::assertEquals('http://example.com/cron-api/update/topology-1/node-1', $request->getUri(TRUE));
            self::assertEquals([
                'time'    => '1 1 1 1 1',
                'command' => self::COM1,
            ], json_decode($request->getBody(), TRUE));

            return new ResponseDto(200, 'OK', '', []);
        })->update($this->getNode());
    }

    /**
     * @throws Exception
     */
    public function testPatch(): void
    {
        $this->getManager(function (RequestDto $request): ResponseDto {
            self::assertEquals(CurlManager::METHOD_POST, $request->getMethod());
            self::assertEquals('http://example.com/cron-api/patch/topology-1/node-1', $request->getUri(TRUE));
            self::assertEquals([
                'time'    => '1 1 1 1 1',
                'command' => self::COM1,
            ], json_decode($request->getBody(), TRUE));

            return new ResponseDto(200, 'OK', '', []);
        })->patch($this->getNode());
    }

    /**
     * @throws Exception
     */
    public function testDelete(): void
    {
        $this->getManager(function (RequestDto $request): ResponseDto {
            self::assertEquals(CurlManager::METHOD_POST, $request->getMethod());
            self::assertEquals('http://example.com/cron-api/delete/topology-1/node-1', $request->getUri(TRUE));
            self::assertEmpty($request->getBody());

            return new ResponseDto(200, 'OK', '', []);
        })->delete($this->getNode());
    }

    /**
     * @throws Exception
     */
    public function testBatchCreate(): void
    {
        $this->getManager(function (RequestDto $request) {
            self::assertEquals(CurlManager::METHOD_POST, $request->getMethod());
            self::assertEquals('http://example.com/cron-api/batch_create', $request->getUri(TRUE));
            self::assertEquals([
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
            ], json_decode($request->getBody(), TRUE));

            return new ResponseDto(200, 'OK', '', []);
        })->batchCreate($this->getNodes(3));
    }

    /**
     * @throws Exception
     */
    public function testBatchUpdate(): void
    {
        $this->getManager(function (RequestDto $request) {
            self::assertEquals(CurlManager::METHOD_POST, $request->getMethod());
            self::assertEquals('http://example.com/cron-api/batch_update', $request->getUri(TRUE));
            self::assertEquals([
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
            ], json_decode($request->getBody(), TRUE));

            return new ResponseDto(200, 'OK', '', []);
        })->batchUpdate($this->getNodes(3));
    }

    /**
     * @throws Exception
     */
    public function testBatchPatch(): void
    {
        $this->getManager(function (RequestDto $request) {
            self::assertEquals(CurlManager::METHOD_POST, $request->getMethod());
            self::assertEquals('http://example.com/cron-api/batch_patch', $request->getUri(TRUE));
            self::assertEquals([
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
            ], json_decode($request->getBody(), TRUE));

            return new ResponseDto(200, 'OK', '', []);
        })->batchPatch($this->getNodes(3));
    }

    /**
     * @throws Exception
     */
    public function testBatchDelete(): void
    {
        $this->getManager(function (RequestDto $request) {
            self::assertEquals(CurlManager::METHOD_POST, $request->getMethod());
            self::assertEquals('http://example.com/cron-api/batch_delete', $request->getUri(TRUE));
            self::assertEquals([
                ['topology' => 'topology-1', 'node' => 'node-1'],
                ['topology' => 'topology-1', 'node' => 'node-2'],
                ['topology' => 'topology-1', 'node' => 'node-3'],
            ], json_decode($request->getBody(), TRUE));

            return new ResponseDto(200, 'OK', '', []);
        })->batchDelete($this->getNodes(3));
    }

    /**
     * @throws Exception
     */
    public function testRequestFail(): void
    {
        self::expectException(CronException::class);
        self::expectExceptionCode(CronException::CRON_EXCEPTION);
        self::expectExceptionMessageRegExp('#Cron API failed: .+#');

        $this->getManager(function (RequestDto $request): void {
            $request;

            throw new CurlException(
                'Client error: `GET http://example.com/cron-api/create` resulted in a `406 Not Acceptable` response: Response',
                CurlException::REQUEST_FAILED
            );
        })->create($this->getNode());
    }

    /**
     * @param callable $callback
     *
     * @return CronManager
     * @throws Exception
     */
    private function getManager(callable $callback): CronManager
    {
        /** @var TopologyRepository|MockObject $topologyRepository */
        $topologyRepository = self::createPartialMock(TopologyRepository::class, ['findOneBy']);
        $topologyRepository->method('findOneBy')->willReturn((new Topology())
            ->setName('topology-1')
            ->setVersion(1)
        );

        /** @var DocumentManager|MockObject $documentManager */
        $documentManager = self::createPartialMock(DocumentManager::class, ['getRepository']);
        $documentManager->method('getRepository')->willReturn($topologyRepository);

        /** @var CurlManager|MockObject $curlManager */
        $curlManager = self::createPartialMock(CurlManager::class, ['send']);
        $curlManager->method('send')->willReturnCallback($callback);

        return new CronManager($documentManager, $curlManager, 'http://example.com/', 'http://example.com/');
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
            $nodes[] = (new Node())
                ->setName(sprintf('node-%s', $i))
                ->setTopology(sprintf('topology-%s', $i))
                ->setType(TypeEnum::CRON)
                ->setCronParams('"params":"abc"')
                ->setCron(sprintf('%s %s %s %s %s', $i, $i, $i, $i, $i));
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
