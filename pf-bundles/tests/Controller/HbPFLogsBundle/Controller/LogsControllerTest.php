<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFLogsBundle\Controller;

use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class LogsControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFLogsBundle\Controller
 */
final class LogsControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFLogsBundle\Controller\LogsController
     * @covers \Hanaboso\PipesFramework\HbPFLogsBundle\Controller\LogsController::getDataForTableAction
     * @covers \Hanaboso\PipesFramework\HbPFLogsBundle\Handler\LogsHandler
     * @covers \Hanaboso\PipesFramework\HbPFLogsBundle\Handler\LogsHandler::getData
     */
    public function testGetDataForTableAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/getDataForTableRequest.json');
    }

}
