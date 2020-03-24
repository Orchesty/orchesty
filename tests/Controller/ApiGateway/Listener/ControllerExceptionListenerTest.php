<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\ApiGateway\Listener;

use Exception;
use Hanaboso\PipesFramework\ApiGateway\Listener\ControllerExceptionListener;
use Hanaboso\Utils\Exception\EnumException;
use Hanaboso\Utils\System\PipesHeaders;
use PHPUnit\Framework\MockObject\MockObject;
use PipesFrameworkTests\ControllerTestCaseAbstract;
use RabbitMqBundle\Consumer\Callback\Exception\CallbackException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

/**
 * Class ControllerExceptionListenerTest
 *
 * @package PipesFrameworkTests\Controller\ApiGateway\Listener
 *
 * @covers  \Hanaboso\PipesFramework\ApiGateway\Listener\ControllerExceptionListener
 */
final class ControllerExceptionListenerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Listener\ControllerExceptionListener::onKernelException
     */
    public function testListener(): void
    {
        $this->client->request('GET', '/nodes/oiz5', [], [], []);

        /** @var JsonResponse $response */
        $response = $this->client->getResponse();

        self::assertEquals(404, $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Listener\ControllerExceptionListener::onKernelException
     *
     * @throws Exception
     */
    public function testKernelException(): void
    {
        $controller = new ControllerExceptionListener();
        $controller->setExceptionClasses([EnumException::class]);

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
     * @covers \Hanaboso\PipesFramework\ApiGateway\Listener\ControllerExceptionListener::getSubscribedEvents
     */
    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [KernelEvents::EXCEPTION => 'onKernelException'],
            ControllerExceptionListener::getSubscribedEvents()
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
        $eventMock = self::createPartialMock(ExceptionEvent::class, ['getThrowable']);
        $eventMock->method('getThrowable')->willReturn($exception);

        $this->setProperty($eventMock, 'request', new Request());

        return $eventMock;
    }

}
