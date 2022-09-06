<?php declare(strict_types=1);

namespace PipesStatusServiceTests\Integration\StatusService;

use Exception;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Pipes\StatusService\StatusService\StatusServiceCallback;
use PipesStatusServiceTests\KernelTestCaseAbstract;
use RabbitMqBundle\Connection\Connection;
use RabbitMqBundle\Utils\Message;

/**
 * Class StatusServiceCallbackTest
 *
 * @package PipesStatusServiceTests\Integration\StatusService
 *
 * @covers  \Pipes\StatusService\StatusService\StatusServiceCallback
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
     * @covers \Hanaboso\PipesPhpSdk\StatusService\StatusServiceCallback::processMessage
     *
     * @throws Exception
     */
    public function testProcessMessage(): void
    {
        $message = Message::create('{"process_id":"1","success":true}');
        $message->setDeliveryTag(1);

        $this->callback->processMessage($message, $this->connection, 1);

        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\StatusService\StatusServiceCallback::processMessage
     *
     * @throws Exception
     */
    public function testProcessMessageFalse(): void
    {
        $message = Message::create('{"process_id":"1","success":false}');
        $message->setDeliveryTag(1);

        $this->callback->processMessage($message, $this->connection, 1);

        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\StatusService\StatusServiceCallback::processMessage
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
     * @covers \Hanaboso\PipesPhpSdk\StatusService\StatusServiceCallback::processMessage
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

        $this->callback   = self::getContainer()->get('hbpf.status_service.status_service_callback');
        $this->connection = self::createMock(Connection::class);
    }

}
