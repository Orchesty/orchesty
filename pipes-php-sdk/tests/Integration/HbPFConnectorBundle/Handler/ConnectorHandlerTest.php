<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFConnectorBundle\Handler;

use Exception;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
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
    private $handler;

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Handler\ConnectorHandler::processEvent
     *
     * @throws ConnectorException
     */
    public function testProcessEvent(): void
    {
        $dto = $this->handler->processEvent('null', new Request());

        self::assertEquals('', $dto->getData());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Handler\ConnectorHandler::processTest
     *
     * @throws ConnectorException
     */
    public function testProcessTest(): void
    {
        $this->handler->processTest('null');

        self::assertTrue(TRUE);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Handler\ConnectorHandler::processAction
     *
     * @throws ConnectorException
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

        $this->handler = self::$container->get('hbpf.handler.connector');
    }

}
