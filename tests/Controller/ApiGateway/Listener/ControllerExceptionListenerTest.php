<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\ApiGateway\Listener;

use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\ApiGateway\Exception\LicenseException;
use Hanaboso\PipesFramework\ApiGateway\Listener\ControllerExceptionListener;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use Hanaboso\Utils\Exception\EnumException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\System\PipesHeaders;
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
        $this->client->request('GET', '/nodes/oiz5', [], [], [self::$AUTHORIZATION => $this->jwt]);

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
            PipesHeaders::RESULT_CODE,
            $eventMock->getResponse()->headers->all(),
        );
        self::assertEquals(
            1_006,
            $eventMock->getResponse()->headers->get(PipesHeaders::RESULT_CODE),
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Listener\ControllerExceptionListener::getSubscribedEvents
     */
    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [KernelEvents::EXCEPTION => 'onKernelException'],
            ControllerExceptionListener::getSubscribedEvents(),
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Listener\ControllerExceptionListener::onKernelException
     *
     * @throws Exception
     */
    public function testConnectorException(): void
    {
        $controller = new ControllerExceptionListener();

        $eventMock = $this->mockEvent(new ConnectorException('', 0, NULL, new ProcessDto()));
        $controller->onKernelException($eventMock);

        $response = $eventMock->getResponse();
        if ($response) {
            self::assertEquals(
                1_006,
                $response->headers->get(PipesHeaders::RESULT_CODE),
            );
        }
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Listener\ControllerExceptionListener::onKernelException
     *
     * @throws Exception
     */
    public function testLicenseException(): void
    {
        $controller = new ControllerExceptionListener();

        $eventMock = $this->mockEvent(new LicenseException('', 0));
        $controller->onKernelException($eventMock);

        $response = $eventMock->getResponse();
        if ($response) {
            self::assertEquals(
                1_006,
                $response->headers->get(PipesHeaders::RESULT_CODE),
            );

            self::assertEquals(401, $response->getStatusCode());
        }
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Listener\ControllerExceptionListener::onKernelException
     *
     * @throws Exception
     */
    public function testSecurityManagerException(): void
    {
        $controller = new ControllerExceptionListener();

        $eventMock = $this->mockEvent(new SecurityManagerException('', 0));
        $controller->onKernelException($eventMock);

        $response = $eventMock->getResponse();
        if ($response) {
            self::assertEquals(
                1_006,
                $response->headers->get(PipesHeaders::RESULT_CODE),
            );

            self::assertEquals(400, $response->getStatusCode());
        }
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Listener\ControllerExceptionListener::onKernelException
     *
     * @throws Exception
     */
    public function testPipesFrameworkException(): void
    {
        $controller = new ControllerExceptionListener();

        $eventMock = $this->mockEvent(new PipesFrameworkException('', 0));
        $controller->onKernelException($eventMock);

        $response = $eventMock->getResponse();
        if ($response) {
            self::assertEquals(
                1_006,
                $response->headers->get(PipesHeaders::RESULT_CODE),
            );

            self::assertEquals(500, $response->getStatusCode());
        }
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Listener\ControllerExceptionListener::onKernelException
     *
     * @throws Exception
     */
    public function testMongoDbException(): void
    {
        $controller = new ControllerExceptionListener();

        $eventMock = $this->mockEvent(new MongoDBException('', 0));
        $controller->onKernelException($eventMock);

        $response = $eventMock->getResponse();
        if ($response) {
            self::assertEquals(
                1_006,
                $response->headers->get(PipesHeaders::RESULT_CODE),
            );

            self::assertEquals(500, $response->getStatusCode());
        }
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Listener\ControllerExceptionListener::onKernelException
     *
     * @throws Exception
     */
    public function testUserManagerException(): void
    {
        $controller = new ControllerExceptionListener();

        $eventMock = $this->mockEvent(new UserManagerException('', 0));
        $controller->onKernelException($eventMock);

        $response = $eventMock->getResponse();
        if ($response) {
            self::assertEquals(
                1_006,
                $response->headers->get(PipesHeaders::RESULT_CODE),
            );

            self::assertEquals(500, $response->getStatusCode());
        }
    }

    /**
     * @param Throwable $exception
     *
     * @return ExceptionEvent
     * @throws Exception
     */
    private function mockEvent(Throwable $exception): ExceptionEvent
    {
        $eventMock = self::createPartialMock(ExceptionEvent::class, ['getThrowable']);
        $eventMock->method('getThrowable')->willReturn($exception);

        $this->setProperty($eventMock, 'request', new Request());

        return $eventMock;
    }

}
