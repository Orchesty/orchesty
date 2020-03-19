<?php declare(strict_types=1);

namespace NotificationSenderTests\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\NotificationSender\Controller\StatusController;
use NotificationSenderTests\ControllerTestCaseAbstract;
use RabbitMqBundle\Connection\ConnectionManager;

/**
 * Class StatusControllerTest
 *
 * @package NotificationSenderTests\Controller
 *
 * @covers  \Hanaboso\NotificationSender\Controller\StatusController
 */
final class StatusControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @var StatusController
     */
    private StatusController $controller;

    /**
     * @covers \Hanaboso\NotificationSender\Controller\StatusController::getStatusAction
     */
    public function testGetStatus(): void
    {
        $this->assertResponse(__DIR__ . '/data/StatusControllerTest/getStatusRequest.json');
    }

    /**
     * @covers \Hanaboso\NotificationSender\Controller\StatusController::getStatusAction
     *
     * @throws Exception
     */
    public function testGetStatusNotConnected(): void
    {
        $dm = self::createMock(DocumentManager::class);
        $dm->method('getClient')->willThrowException(new Exception());
        $this->setProperty($this->controller, 'dm', $dm);

        $manager = self::createMock(ConnectionManager::class);
        $manager->method('getConnection')->willThrowException(new Exception());
        $this->setProperty($this->controller, 'manager', $manager);

        $this->assertResponse(__DIR__ . '/data/StatusControllerTest/getStatusNotConnectedRequest.json');
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = self::$container->get('Hanaboso\NotificationSender\Controller\StatusController');
    }

}
