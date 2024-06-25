<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFLogsBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\HbPFLogsBundle\Controller\LogsController;
use Hanaboso\PipesFramework\HbPFLogsBundle\Handler\LogsHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class LogsControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFLogsBundle\Controller
 */
#[CoversClass(LogsController::class)]
#[CoversClass(LogsHandler::class)]
final class LogsControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetDataForTableAction(): void
    {
        $this->assertResponseLogged($this->jwt,__DIR__ . '/data/getDataForTableRequest.json');
    }

}
