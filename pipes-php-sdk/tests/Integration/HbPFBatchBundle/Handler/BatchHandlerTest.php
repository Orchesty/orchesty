<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFBatchBundle\Handler;

use Exception;
use Hanaboso\PipesPhpSdk\HbPFBatchBundle\Handler\BatchHandler;
use Hanaboso\PipesPhpSdk\Utils\ProcessDtoFactory;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\KernelTestCaseAbstract;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BatchHandlerTest
 *
 * @package PipesPhpSdkTests\Integration\HbPFBatchBundle\Handler
 */
#[CoversClass(BatchHandler::class)]
final class BatchHandlerTest extends KernelTestCaseAbstract
{

    /**
     * @var BatchHandler
     */
    private BatchHandler $handler;

    /**
     * @throws Exception
     */
    public function testProcessTest(): void
    {
        $this->handler->processTest('null');

        self::assertFake();
    }

    /**
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
