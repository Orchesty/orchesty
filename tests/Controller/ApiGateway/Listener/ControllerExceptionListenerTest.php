<?php declare(strict_types=1);

namespace Tests\Controller\ApiGateway\Listener;

use Exception;
use Hanaboso\PipesFramework\ApiGateway\Listener\ControllerExceptionListener;
use Hanaboso\Utils\Exception\EnumException;
use Hanaboso\Utils\System\PipesHeaders;
use PHPUnit\Framework\MockObject\MockObject;
use RabbitMqBundle\Consumer\Callback\Exception\CallbackException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Tests\ControllerTestCaseAbstract;
use Throwable;

/**
 * Class ControllerExceptionListenerTest
 *
 * @package Tests\Controller\ApiGateway\Listener
 */
final class ControllerExceptionListenerTest extends ControllerTestCaseAbstract
{

    /**
     *
     */
    public function testListener(): void
    {
        self::$client->request('GET', '/nodes/oiz5', [], [], []);

        /** @var JsonResponse $response */
        $response = self::$client->getResponse();

        self::assertEquals(400, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testKernelException(): void
    {
        $controller = new ControllerExceptionListener();

        $eventMock = $this->mockEvent(new Exception(''));
        $controller->onKernelException($eventMock);
        self::assertNull($eventMock->getResponse());

        $eventMock = $this->mockEvent(new CallbackException());
        $controller->onKernelException($eventMock);
        self::assertNull($eventMock->getResponse());

        $eventMock = $this->mockEvent(new EnumException());
        $controller->onKernelException($eventMock);
        self::assertInstanceOf(Response::class, $eventMock->getResponse());
        self::assertArrayHasKey(
            PipesHeaders::createKey(PipesHeaders::RESULT_CODE),
            $eventMock->getResponse()->headers->all()
        );
        self::assertEquals(
            1_006,
            $eventMock->getResponse()->headers->get(PipesHeaders::createKey(PipesHeaders::RESULT_CODE))
        );
    }

    /**
     * @param Throwable $exception
     *
     * @return ExceptionEvent
     * @throws Exception
     */
    private function mockEvent(Throwable $exception): ExceptionEvent
    {
        /** @var ExceptionEvent|MockObject $eventMock */
        $eventMock = self::createPartialMock(
            ExceptionEvent::class,
            ['getThrowable']
        );

        $eventMock
            ->method('getThrowable')
            ->willReturn($exception);

        $this->setProperty($eventMock, 'request', new Request());

        return $eventMock;
    }

}
