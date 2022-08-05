<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Controller\HbPFBatchBundle\Controller;

use Exception;
use Hanaboso\CommonsBundle\Process\BatchProcessDto;
use Hanaboso\PipesPhpSdk\HbPFBatchBundle\Handler\BatchHandler;
use Hanaboso\Utils\String\Json;
use PipesPhpSdkTests\ControllerTestCaseAbstract;

/**
 * Class BatchControllerTest
 *
 * @package PipesPhpSdkTests\Controller\HbPFBatchBundle\Controller
 *
 * @covers \Hanaboso\PipesPhpSdk\HbPFBatchBundle\Controller\BatchController
 */
final class BatchControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFBatchBundle\Controller\BatchController::processActionAction
     *
     * @throws Exception
     */
    public function testProcessActionActionErr(): void
    {
        $this->client->request('POST', '/batch/magento/action', [], [], [], '{}');

        $response = $this->client->getResponse();
        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFBatchBundle\Controller\BatchController::processActionAction
     *
     * @throws Exception
     */
    public function testProcessActionActionErr2(): void
    {
        $handler = self::createPartialMock(BatchHandler::class, ['getBeaches']);
        $handler->expects(self::any())->method('getBeaches')->willThrowException(new Exception());

        self::getContainer()->set('hbpf.handler.batch', $handler);

        $this->client->request('POST', '/batch/magento/action', [], [], [], '{}');

        $response = $this->client->getResponse();
        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFBatchBundle\Controller\BatchController::processActionTestAction
     *
     * @throws Exception
     */
    public function testProcessActionTestActionErr(): void
    {
        $response = $this->sendGet('/batch/magento/action/test');
        self::assertEquals(500, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFBatchBundle\Controller\BatchController::listOfConnectorsAction
     *
     * @throws Exception
     */
    public function testListOfConnectorsAction(): void
    {
        $handler = self::createPartialMock(BatchHandler::class, ['getBeaches']);
        $handler->expects(self::any())->method('getBeaches')->willThrowException(new Exception());

        self::getContainer()->set('hbpf.handler.batch', $handler);

        $response = $this->sendGet('/batch/list');
        self::assertEquals(500, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFBatchBundle\Controller\BatchController::processActionAction
     *
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->mockHandler('processAction');

        $this->client->request('POST', '/batch/magento/action', [], [], [], '{}');

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(
            [['body' => '{"test":"test"}', 'headers' => []]],
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
