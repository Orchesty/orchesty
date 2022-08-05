<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFConnectorBundle\Handler;

use Exception;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Handler\ConnectorHandler;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ConnectorHandlerTest
 *
 * @package PipesPhpSdkTests\Integration\HbPFConnectorBundle\Handler
 */
final class ConnectorHandlerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var ConnectorHandler
     */
    private ConnectorHandler $handler;

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Handler\ConnectorHandler::processTest
     *
     * @throws Exception
     */
    public function testProcessTest(): void
    {
        $this->handler->processTest('null');

        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Handler\ConnectorHandler::processAction
     *
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $dto = $this->handler->processAction('null', new Request());

        self::assertEquals('', $dto->getData());
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
