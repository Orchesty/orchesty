<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFBatchBundle\Handler;

use Exception;
use Hanaboso\PipesPhpSdk\HbPFBatchBundle\Handler\BatchHandler;
use Hanaboso\PipesPhpSdk\Utils\ProcessDtoFactory;
use Hanaboso\Utils\String\Json;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BatchHandlerTest
 *
 * @package PipesPhpSdkTests\Integration\HbPFBatchBundle\Handler
 *
 * @covers \Hanaboso\PipesPhpSdk\HbPFBatchBundle\Handler\BatchHandler
 */
final class BatchHandlerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var BatchHandler
     */
    private BatchHandler $handler;

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFBatchBundle\Handler\BatchHandler::processTest
     *
     * @throws Exception
     */
    public function testProcessTest(): void
    {
        $this->handler->processTest('null');

        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFBatchBundle\Handler\BatchHandler::processAction
     *
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $dto = $this->handler->processAction(
            'null',
            new Request(content: Json::encode([ProcessDtoFactory::BODY => '', ProcessDtoFactory::HEADERS => []])),
        );

        self::assertEquals('[]', $dto->getBridgeData());
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = self::getContainer()->get('hbpf.handler.batch');
    }

}
