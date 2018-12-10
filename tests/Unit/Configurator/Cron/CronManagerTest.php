<?php declare(strict_types=1);

namespace Tests\Unit\Configurator\Cron;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Exception\CronException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Cron\CronManager;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Exception\NodeException;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Nette\Utils\Json;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class CronManagerTest
 *
 * @package Tests\Unit\Configurator\Cron
 */
final class CronManagerTest extends KernelTestCaseAbstract
{

    private const COM1 = 'curl -H "Accept: application/json" -H "Content-Type: application/json" -X POST -d \'{"params":"abc"}\' http://example.com/api/topologies/topology-1/nodes/node-1/run_by_name';
    private const COM2 = 'curl -H "Accept: application/json" -H "Content-Type: application/json" -X POST -d \'{"params":"abc"}\' http://example.com/api/topologies/topology-1/nodes/node-2/run_by_name';
    private const COM3 = 'curl -H "Accept: application/json" -H "Content-Type: application/json" -X POST -d \'{"params":"abc"}\' http://example.com/api/topologies/topology-1/nodes/node-3/run_by_name';

    /**
     * @throws CronException
     * @throws CurlException
     * @throws NodeException
     */
    public function testCreate(): void
    {
        $this->getManager(function (RequestDto $request): ResponseDto {
            $this->assertEquals(CurlManager::METHOD_POST, $request->getMethod());
            $this->assertEquals('http://example.com/cron-api/create', $request->getUri(TRUE));
            $this->assertEquals([
                'hash'    => 'topology-1-node-1',
                'time'    => '1 1 1 1 1',
                'command' => self::COM1,
            ], Json::decode($request->getBody(), TRUE));

            return new ResponseDto(200, 'OK', '', []);
        })->create($this->getNodes());
    }

    /**
     * @throws CronException
     * @throws CurlException
     * @throws NodeException
     */
    public function testUpdate(): void
    {
        $this->getManager(function (RequestDto $request): ResponseDto {
            $this->assertEquals(CurlManager::METHOD_POST, $request->getMethod());
            $this->assertEquals('http://example.com/cron-api/update/topology-1-node-1', $request->getUri(TRUE));
            $this->assertEquals([
                'time'    => '1 1 1 1 1',
                'command' => self::COM1,
            ], Json::decode($request->getBody(), TRUE));

            return new ResponseDto(200, 'OK', '', []);
        })->update($this->getNodes());
    }

    /**
     * @throws CronException
     * @throws CurlException
     * @throws NodeException
     */
    public function testPatch(): void
    {
        $this->getManager(function (RequestDto $request): ResponseDto {
            $this->assertEquals(CurlManager::METHOD_POST, $request->getMethod());
            $this->assertEquals('http://example.com/cron-api/patch/topology-1-node-1', $request->getUri(TRUE));
            $this->assertEquals([
                'time'    => '1 1 1 1 1',
                'command' => self::COM1,
            ], Json::decode($request->getBody(), TRUE));

            return new ResponseDto(200, 'OK', '', []);
        })->patch($this->getNodes());
    }

    /**
     * @throws CronException
     * @throws NodeException
     * @throws Exception
     */
    public function testDelete(): void
    {
        $this->getManager(function (RequestDto $request): ResponseDto {
            $this->assertEquals(CurlManager::METHOD_POST, $request->getMethod());
            $this->assertEquals('http://example.com/cron-api/delete/topology-1-node-1', $request->getUri(TRUE));
            $this->assertEmpty($request->getBody());

            return new ResponseDto(200, 'OK', '', []);
        })->delete($this->getNodes());
    }

    /**
     * @throws CronException
     * @throws CurlException
     * @throws NodeException
     */
    public function testBatchCreate(): void
    {
        $this->getManager(function (RequestDto $request) {
            $this->assertEquals(CurlManager::METHOD_POST, $request->getMethod());
            $this->assertEquals('http://example.com/cron-api/batch_create', $request->getUri(TRUE));
            $this->assertEquals([
                0 => [
                    'hash'    => 'topology-1-node-1',
                    'time'    => '1 1 1 1 1',
                    'command' => self::COM1,
                ],
                1 => [
                    'hash'    => 'topology-1-node-2',
                    'time'    => '2 2 2 2 2',
                    'command' => self::COM2,
                ],
                2 => [
                    'hash'    => 'topology-1-node-3',
                    'time'    => '3 3 3 3 3',
                    'command' => self::COM3,
                ],
            ], Json::decode($request->getBody(), TRUE));

            return new ResponseDto(200, 'OK', '', []);
        })->batchCreate($this->getNodes(3));
    }

    /**
     * @throws CronException
     * @throws CurlException
     * @throws NodeException
     */
    public function testBatchUpdate(): void
    {
        $this->getManager(function (RequestDto $request) {
            $this->assertEquals(CurlManager::METHOD_POST, $request->getMethod());
            $this->assertEquals('http://example.com/cron-api/batch_update', $request->getUri(TRUE));
            $this->assertEquals([
                0 => [
                    'hash'    => 'topology-1-node-1',
                    'time'    => '1 1 1 1 1',
                    'command' => self::COM1,
                ],
                1 => [
                    'hash'    => 'topology-1-node-2',
                    'time'    => '2 2 2 2 2',
                    'command' => self::COM2,
                ],
                2 => [
                    'hash'    => 'topology-1-node-3',
                    'time'    => '3 3 3 3 3',
                    'command' => self::COM3,
                ],
            ], Json::decode($request->getBody(), TRUE));

            return new ResponseDto(200, 'OK', '', []);
        })->batchUpdate($this->getNodes(3));
    }

    /**
     * @throws CronException
     * @throws CurlException
     * @throws NodeException
     */
    public function testBatchPatch(): void
    {
        $this->getManager(function (RequestDto $request) {
            $this->assertEquals(CurlManager::METHOD_POST, $request->getMethod());
            $this->assertEquals('http://example.com/cron-api/batch_patch', $request->getUri(TRUE));
            $this->assertEquals([
                0 => [
                    'hash'    => 'topology-1-node-1',
                    'time'    => '1 1 1 1 1',
                    'command' => self::COM1,
                ],
                1 => [
                    'hash'    => 'topology-1-node-2',
                    'time'    => '2 2 2 2 2',
                    'command' => self::COM2,
                ],
                2 => [
                    'hash'    => 'topology-1-node-3',
                    'time'    => '3 3 3 3 3',
                    'command' => self::COM3,
                ],
            ], Json::decode($request->getBody(), TRUE));

            return new ResponseDto(200, 'OK', '', []);
        })->batchPatch($this->getNodes(3));
    }

    /**
     * @throws CronException
     * @throws CurlException
     * @throws NodeException
     */
    public function testBatchDelete(): void
    {
        $this->getManager(function (RequestDto $request) {
            $this->assertEquals(CurlManager::METHOD_POST, $request->getMethod());
            $this->assertEquals('http://example.com/cron-api/batch_delete', $request->getUri(TRUE));
            $this->assertEquals([
                0 => ['hash' => 'topology-1-node-1'],
                1 => ['hash' => 'topology-1-node-2'],
                2 => ['hash' => 'topology-1-node-3'],
            ], Json::decode($request->getBody(), TRUE));

            return new ResponseDto(200, 'OK', '', []);
        })->batchDelete($this->getNodes(3));
    }

    /**
     * @throws CronException
     * @throws CurlException
     * @throws NodeException
     */
    public function testRequestFail(): void
    {
        $this->expectException(CronException::class);
        $this->expectExceptionCode(CronException::CRON_EXCEPTION);
        $this->expectExceptionMessageRegExp('#Cron API failed: .+#');

        $this->getManager(function (RequestDto $request): void {
            $request;

            throw new CurlException(
                'Client error: `GET http://example.com/cron-api/create` resulted in a `406 Not Acceptable` response: Response',
                CurlException::REQUEST_FAILED
            );
        })->create($this->getNodes());
    }

    /**
     * @param callable $callback
     *
     * @return CronManager
     */
    private function getManager(callable $callback): CronManager
    {
        /** @var TopologyRepository|MockObject $topologyRepository */
        $topologyRepository = $this->createPartialMock(TopologyRepository::class, ['findOneBy']);
        $topologyRepository->method('findOneBy')->willReturn((new Topology())
            ->setName('topology-1')
            ->setVersion(1)
        );

        /** @var DocumentManager|MockObject $documentManager */
        $documentManager = $this->createPartialMock(DocumentManager::class, ['getRepository']);
        $documentManager->method('getRepository')->willReturn($topologyRepository);

        /** @var CurlManager|MockObject $curlManager */
        $curlManager = $this->createPartialMock(CurlManager::class, ['send']);
        $curlManager->method('send')->willReturnCallback($callback);

        return new CronManager($documentManager, $curlManager, 'http://example.com/', 'http://example.com/');
    }

    /**
     * @param int $count
     *
     * @return Node|Node[]
     * @throws Exception
     */
    private function getNodes(int $count = 1)
    {
        /** @var Node[] $nodes */
        $nodes = [];

        for ($i = 1; $i <= $count; $i++) {
            $nodes[] = (new Node())
                ->setName(sprintf('node-%s', $i))
                ->setTopology(sprintf('topology-%s', $i))
                ->setType(TypeEnum::CRON)
                ->setCronParams('"params":"abc"')
                ->setCron(sprintf('%s %s %s %s %s', $i, $i, $i, $i, $i));
        }

        return count($nodes) === 1 ? $nodes[0] : $nodes;
    }

}