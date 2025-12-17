<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Configurator\Cron;

use Exception;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Exception\CronException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Cron\CronManager;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class CronManagerTest
 *
 * @package PipesFrameworkTests\Unit\Configurator\Cron
 */
#[CoversClass(CronManager::class)]
final class CronManagerTest extends KernelTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetAll(): void
    {
        $data = $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertSame(CurlManager::METHOD_GET, $request->getMethod());
                self::assertEquals('https://example.com/crons', $request->getUri(TRUE));

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
     * @throws Exception
     */
    public function testUpsert(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertSame(CurlManager::METHOD_PATCH, $request->getMethod());
                self::assertEquals('https://example.com/crons', $request->getUri(TRUE));
                self::assertEquals(
                    [
                        [
                            'node'       => 'node-1',
                            'parameters' => '"key":"value"',
                            'time'       => '1 1 1 1 1',
                            'topology'   => 'topology-1',
                        ],
                    ],
                    Json::decode($request->getBody()),
                );

                return new ResponseDto(200, 'OK', '', []);
            },
        )->upsert($this->getNode());
    }

    /**
     * @throws Exception
     */
    public function testDelete(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertSame(CurlManager::METHOD_DELETE, $request->getMethod());
                self::assertEquals('https://example.com/crons', $request->getUri(TRUE));
                self::assertEquals(
                    [
                        [
                            'node'     => 'node-1',
                            'topology' => 'topology-1',
                        ],
                    ],
                    Json::decode($request->getBody()),
                );

                return new ResponseDto(200, 'OK', '', []);
            },
        )->delete($this->getNode());
    }

    /**
     * @throws Exception
     */
    public function testBatchUpsert(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertSame(CurlManager::METHOD_PATCH, $request->getMethod());
                self::assertEquals('https://example.com/crons', $request->getUri(TRUE));
                self::assertEquals(
                    [
                        [
                            'node'       => 'node-1',
                            'parameters' => '"key":"value"',
                            'time'       => '1 1 1 1 1',
                            'topology'   => 'topology-1',
                        ],
                        [
                            'node'       => 'node-2',
                            'parameters' => '"key":"value"',
                            'time'       => '2 2 2 2 2',
                            'topology'   => 'topology-2',
                        ],
                        [
                            'node'       => 'node-3',
                            'parameters' => '"key":"value"',
                            'time'       => '3 3 3 3 3',
                            'topology'   => 'topology-3',
                        ],
                    ],
                    Json::decode($request->getBody()),
                );

                return new ResponseDto(200, 'OK', '', []);
            },
        )->batchUpsert($this->getNodes(3));
    }

    /**
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
                    'Client error: `GET https://example.com/cron-api/create` resulted in a `406 Not Acceptable` response: Response',
                    CurlException::REQUEST_FAILED,
                );
            },
        )->upsert($this->getNode());
    }

    /**
     * @param callable $callback
     *
     * @return CronManager
     * @throws Exception
     */
    private function getManager(callable $callback): CronManager
    {
        $curlManager = self::createPartialMock(CurlManager::class, ['send']);
        $curlManager->method('send')->willReturnCallback($callback);

        return new CronManager($curlManager, 'https://example.com/');
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
                ->setType(TypeEnum::CRON->value)
                ->setCronParams('"key":"value"')
                ->setCron(sprintf('%s %s %s %s %s', $i, $i, $i, $i, $i));
            $this->setProperty($node, 'id', sprintf('node-%s', $i));
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
