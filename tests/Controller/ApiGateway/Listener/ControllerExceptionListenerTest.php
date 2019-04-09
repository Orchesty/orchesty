<?php declare(strict_types=1);

namespace Tests\Controller\ApiGateway\Listener;

use Exception;
use Hanaboso\CommonsBundle\Exception\EnumException;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesFramework\ApiGateway\Listener\ControllerExceptionListener;
use PHPUnit\Framework\MockObject\MockObject;
use RabbitMqBundle\Consumer\Callback\Exception\CallbackException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
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
        $this->client->request('GET', '/nodes/oiz5', [], [], []);

        /** @var JsonResponse $response */
        $response = $this->client->getResponse();

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
        self::assertInstanceOf(Response::class, $eventMock->getResponse());
        self::assertArrayNotHasKey(
            PipesHeaders::createKey(PipesHeaders::RESULT_CODE),
            $eventMock->getResponse()->headers->all()
        );

        $eventMock = $this->mockEvent(new EnumException());
        $controller->onKernelException($eventMock);
        self::assertInstanceOf(Response::class, $eventMock->getResponse());
        self::assertArrayHasKey(
            PipesHeaders::createKey(PipesHeaders::RESULT_CODE),
            $eventMock->getResponse()->headers->all()
        );
        self::assertEquals(
            1006,
            $eventMock->getResponse()->headers->get(PipesHeaders::createKey(PipesHeaders::RESULT_CODE))
        );
    }

    /**
     * @param Throwable $exception
     *
     * @return GetResponseForExceptionEvent | MockObject
     * @throws Exception
     */
    private function mockEvent(Throwable $exception)
    {
        /** @var GetResponseForExceptionEvent | MockObject $eventMock */
        $eventMock = $this->createPartialMock(
            GetResponseForExceptionEvent::class,
            ['getException']
        );

        $eventMock
            ->method('getException')
            ->will($this->returnValue($exception));

        return $eventMock;
    }

}