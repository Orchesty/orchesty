<?php declare(strict_types=1);

namespace Tests\Controller;

use Tests\ControllerTestCaseAbstract;

/**
 * Class PortalControllerTest
 *
 * @package Tests\Controller
 */
final class PortalControllerTest extends ControllerTestCaseAbstract
{

    /**
     *
     */
    public function testIndex(): void
    {
        $response = $this->sendGet('/');

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals(
            [
                'name'    => 'portal',
                'version' => '1.0.0',
                'status'  => 'OK',
            ],
            $response->getContent()
        );
    }

}
