<?php declare(strict_types=1);

namespace Tests\Controller;

use Hanaboso\Portal\Model\Installer\Installer;
use Tests\ControllerTestCaseAbstract;

/**
 * Class PortalControllerTest
 *
 * @package Tests\Controller
 */
final class InstallerControllerTest extends ControllerTestCaseAbstract
{

    /**
     *
     */
    public function testInstaller(): void
    {
        $response = $this->sendPost('/installer', [
            'first'  => Installer::LOGSTASH,
            'second' => Installer::INFLUXDB,
            'third'  => FALSE,
        ]);

        $this->assertEquals(200, $response->getStatus());
        //        $this->assertEquals([
        //            'name'    => 'portal',
        //            'version' => '1.0.0',
        //            'status'  => 'OK',
        //        ],
        //            $response->getContent()
        //        );
    }

}
