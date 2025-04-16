<?php declare(strict_types=1);

namespace DemoTests\Controller;

use Demo\Controller\DefaultController;
use DemoTests\KernelTestCaseAbstract;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class DefaultControllerTest
 *
 * @package DemoTests\Controller
 */
#[CoversClass(DefaultController::class)]
final class DefaultControllerTest extends KernelTestCaseAbstract
{

    /**
     * @return void
     */
    public function testIndex(): void
    {
        $controller = new DefaultController();
        $response   = $controller->indexAction();
        self::assertSame(200, $response->getStatusCode());
    }

}
