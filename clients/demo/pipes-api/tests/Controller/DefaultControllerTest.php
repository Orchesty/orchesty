<?php declare(strict_types=1);

namespace DemoTests\Controller;

use Demo\Controller\DefaultController;
use DemoTests\KernelTestCaseAbstract;

/**
 * Class DefaultControllerTest
 *
 * @package DemoTests\Controller
 */
final class DefaultControllerTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Demo\Controller\DefaultController::indexAction
     */
    public function testIndex(): void
    {
        $controller = new DefaultController();
        $response   = $controller->indexAction();
        self::assertEquals(200, $response->getStatusCode());
    }

}
