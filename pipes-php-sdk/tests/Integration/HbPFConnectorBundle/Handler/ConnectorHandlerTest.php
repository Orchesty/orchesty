<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFConnectorBundle\Handler;

use Exception;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Handler\ConnectorHandler;
use Hanaboso\PipesPhpSdk\Utils\ProcessDtoFactory;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\KernelTestCaseAbstract;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ConnectorHandlerTest
 *
 * @package PipesPhpSdkTests\Integration\HbPFConnectorBundle\Handler
 */
#[CoversClass(ConnectorHandler::class)]
final class ConnectorHandlerTest extends KernelTestCaseAbstract
{

    /**
     * @var ConnectorHandler
     */
    private ConnectorHandler $handler;

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

        self::assertSame('', $dto->getData());
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = self::getContainer()->get('hbpf.handler.connector');
    }

}
