<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Controller;

use CleverConnectors\AppBundle\Controller\CMEventsController;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Handler\CMEventsHandler;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\HttpFoundation\Request;
use Tests\ControllerTestCaseAbstract;

/**
 * Class CMEventsControllerTest
 *
 * @package Tests\Integration\AppBundle\Controller
 */
final class CMEventsControllerTest extends ControllerTestCaseAbstract
{

    /**
     *
     */
    public function testCorrect(): void
    {
        /** @var CMEventsHandler|PHPUnit_Framework_MockObject_MockObject $handler */
        $handler = $this->createMock(CMEventsHandler::class);
        $handler->expects($this->at(0))
            ->method('createEvent')->willReturn('');
        $handler->expects($this->at(1))
            ->method('unsubscribeEvent')->willReturn('');
        $handler->expects($this->at(2))
            ->method('hardBounceEvent')->willReturn('');

        $controller = new CMEventsController($handler);
        $req        = new Request();

        $res = $controller->createAction($req, 'usr');
        self::assertEquals(200, $res->getStatusCode());

        $res = $controller->unsubscribeAction($req, 'usr');
        self::assertEquals(200, $res->getStatusCode());

        $res = $controller->hardBounceAction($req, 'usr');
        self::assertEquals(200, $res->getStatusCode());
    }

    /**
     *
     */
    public function testCreateActionEnumException(): void
    {
        $controller = $this->mockController('createEvent', CleverConnectorsException::INVALID_ENUM_VALUE);

        $res = $controller->createAction(new Request(), 'usr');
        self::assertEquals(400, $res->getStatusCode());
    }

    /**
     *
     */
    public function testUnsubscribeActionEnumException(): void
    {
        $controller = $this->mockController('unsubscribeEvent', CleverConnectorsException::INVALID_ENUM_VALUE);

        $res = $controller->unsubscribeAction(new Request(), 'usr');
        self::assertEquals(400, $res->getStatusCode());
    }

    /**
     *
     */
    public function testHardBounceEnumException(): void
    {
        $controller = $this->mockController('hardBounceEvent', CleverConnectorsException::INVALID_ENUM_VALUE);

        $res = $controller->hardBounceAction(new Request(), 'usr');
        self::assertEquals(400, $res->getStatusCode());
    }

    /**
     *
     */
    public function testCreateActionTopologyException(): void
    {
        $controller = $this->mockController('createEvent', CleverConnectorsException::TOPOLOGY_NOT_FOUND);

        $res = $controller->createAction(new Request(), 'usr');
        self::assertEquals(404, $res->getStatusCode());
    }

    /**
     *
     */
    public function testUnsubscribeActionTopologyException(): void
    {
        $controller = $this->mockController('unsubscribeEvent', CleverConnectorsException::TOPOLOGY_NOT_FOUND);

        $res = $controller->unsubscribeAction(new Request(), 'usr');
        self::assertEquals(404, $res->getStatusCode());
    }

    /**
     *
     */
    public function testHardBounceActionTopologyException(): void
    {
        $controller = $this->mockController('hardBounceEvent', CleverConnectorsException::TOPOLOGY_NOT_FOUND);

        $res = $controller->hardBounceAction(new Request(), 'usr');
        self::assertEquals(404, $res->getStatusCode());
    }

    /**
     * @param string $method
     * @param int    $code
     *
     * @return CMEventsController
     * @throws CleverConnectorsException
     */
    private function mockController(string $method, int $code): CMEventsController
    {
        /** @var CMEventsHandler|PHPUnit_Framework_MockObject_MockObject $handler */
        $handler = $this->createMock(CMEventsHandler::class);
        $handler->expects($this->at(0))
            ->method($method)->will($this->throwException(new CleverConnectorsException('exception', $code)));

        return new CMEventsController($handler);
    }

}