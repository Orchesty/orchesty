<?php declare(strict_types=1);

namespace Tests\Controller;

use Hanaboso\Portal\Model\Installer\Installer;
use Tests\ControllerTestCaseAbstract;

/**
 * Class InstallerControllerTest
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
        $response1 = $this->sendPost(
            '/installer',
            [
                'logs'     => Installer::LOGSTASH,
                'metrics'  => Installer::INFLUXDB,
                'database' => FALSE,
            ]
        );

        $response2 = $this->sendPost(
            '/installer',
            [
                'logs'     => Installer::ELASTICSEARCH,
                'metrics'  => Installer::MONGO,
                'database' => TRUE,
            ]
        );

        $this->assertEquals(200, $response1->getStatus());
        $this->assertEquals('attachment; filename=docker-compose.yml', $response1->getContent()['message']);

        $this->assertEquals('attachment; filename=docker-compose.yml', $response2->getContent()['message']);
        $this->assertEquals(200, $response2->getStatus());

        $response3 = $this->sendPost(
            '/installer',
            [
                'logs'     => 'xx',
                'metrics'  => 'yy',
                'database' => TRUE,
            ]
        );

        $this->assertEquals(500, $response3->getStatus());
        $this->assertEquals('Insert correct value to log', $response3->getContent()['message']);
    }

}
