<?php declare(strict_types=1);

namespace PortalTests\Controller;

use PortalTests\ControllerTestCaseAbstract;

/**
 * Class PortalControllerTest
 *
 * @package PortalTests\Controller
 */
final class PortalControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\Portal\Controller\PortalController::indexAction
     */
    public function testIndex(): void
    {
        $response = $this->sendGet('/');

        self::assertEquals(200, $response->getStatus());
        self::assertEquals(
            [
                'name'    => 'portal',
                'version' => '1.0.0',
                'status'  => 'OK',
            ],
            $response->getContent(),
        );
    }

}
