<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Configurator\StatusService;

use Exception;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\PipesFramework\Configurator\StatusService\StatusServiceCallback;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use PipesFrameworkTests\KernelTestCaseAbstract;
use RabbitMqBundle\Connection\Connection;
use RabbitMqBundle\Utils\Message;

/**
 * Class StatusServiceCallbackTest
 *
 * @package PipesFrameworkTests\Integration\Configurator\StatusService
 *
 * @covers  \Hanaboso\PipesFramework\Configurator\StatusService\StatusServiceCallback
 */
final class StatusServiceCallbackTest extends KernelTestCaseAbstract
{

    use CustomAssertTrait;

    /**
     * @var StatusServiceCallback
     */
    private StatusServiceCallback $callback;

    /**
     * @var Connection
     */
    private Connection $connection;

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\StatusService\StatusServiceCallback::processMessage
     *
     * @throws Exception
     */
    public function testProcessMessage(): void
    {
        $message = Message::create('{"process_id":"1","success":true}');
        // phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps
        $message->delivery_info = ['delivery_tag' => ''];
        // phpcs:enable

        $this->callback->processMessage($message, $this->connection, 1);

        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\StatusService\StatusServiceCallback::processMessage
     *
     * @throws Exception
     */
    public function testProcessMessageMissingProcessId(): void
    {
        self::expectException(PipesFrameworkException::class);
        self::expectExceptionCode(PipesFrameworkException::REQUIRED_PARAMETER_NOT_FOUND);

        $this->callback->processMessage(Message::create('{}'), $this->connection, 1);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\StatusService\StatusServiceCallback::processMessage
     *
     * @throws Exception
     */
    public function testProcessMessageMissingSuccess(): void
    {
        self::expectException(PipesFrameworkException::class);
        self::expectExceptionCode(PipesFrameworkException::REQUIRED_PARAMETER_NOT_FOUND);

        $this->callback->processMessage(Message::create('{"process_id":"1"}'), $this->connection, 1);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->callback   = self::$container->get(
            'hanaboso.pipes_framework.commons.status_service.status_service_callback'
        );
        $this->connection = self::$container->get('rabbit_mq.connection_manager')->getConnection();
        $this->connection->createChannel();
    }

}
