<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Controller\HbPFBatchBundle\Controller;

use Exception;
use Hanaboso\CommonsBundle\Process\BatchProcessDto;
use Hanaboso\PipesPhpSdk\HbPFBatchBundle\Controller\BatchController;
use Hanaboso\PipesPhpSdk\HbPFBatchBundle\Handler\BatchHandler;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\ControllerTestCaseAbstract;

/**
 * Class BatchControllerTest
 *
 * @package PipesPhpSdkTests\Controller\HbPFBatchBundle\Controller
 *
 * @covers  \Hanaboso\PipesPhpSdk\HbPFBatchBundle\Controller\BatchController
 */
#[CoversClass(BatchController::class)]
final class BatchControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testProcessActionActionErr(): void
    {
        $this->client->request('POST', '/batch/magento/action', [], [], [], '{}');

        $response = $this->client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testProcessActionActionErr2(): void
    {
        $handler = self::createPartialMock(BatchHandler::class, ['getBatches']);
        $handler->expects(self::any())->method('getBatches')->willThrowException(new Exception());

        self::getContainer()->set('hbpf.handler.batch', $handler);

        $this->client->request('POST', '/batch/magento/action', [], [], [], '{}');

        $response = $this->client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testProcessActionTestActionErr(): void
    {
        $response = $this->sendGet('/batch/magento/action/test');
        self::assertEquals(500, $response->status);
    }

    /**
     * @throws Exception
     */
    public function testListOfConnectorsAction(): void
    {
        $handler = self::createPartialMock(BatchHandler::class, ['getBatches']);
        $handler->expects(self::any())->method('getBatches')->willThrowException(new Exception());

        self::getContainer()->set('hbpf.handler.batch', $handler);

        $response = $this->sendGet('/batch/list');
        self::assertEquals(500, $response->status);
    }

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->mockHandler('processAction');

        $this->client->request('POST', '/batch/magento/action', [], [], [], '{}');

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(
            [
                'body'    => '[{"body":"{\"test\":\"test\"}","headers":{"limiter-key":null,"user":null}}]',
                'headers' => ['result-code' => 0, 'result-message' => '', 'result-detail' => ''],
            ],
            Json::decode((string) $response->getContent()),
        );
    }

    /**
     * @param string $method
     *
     * @throws Exception
     */
    private function mockHandler(string $method): void
    {
        $handler = $this->getMockBuilder(BatchHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['processAction', 'processTest'])
            ->getMock();

        $dto = new BatchProcessDto();
        $dto
            ->setItemList([['test' => 'test']])
            ->setHeaders([]);
        $handler->method($method)->willReturn($dto);

        self::getContainer()->set('hbpf.handler.batch', $handler);
    }

}
