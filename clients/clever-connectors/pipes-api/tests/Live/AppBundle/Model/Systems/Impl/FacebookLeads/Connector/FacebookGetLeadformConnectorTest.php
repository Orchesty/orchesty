<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 12/7/17
 * Time: 5:39 PM
 */

namespace Tests\Live\AppBundle\Model\Systems\Impl\FacebookLeads\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Nette\Utils\Json;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class FacebookGetLeadformConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\FacebookLeads\Connector
 */
class FacebookGetLeadformConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testGetLeadForms(): void
    {
        $this->markTestSkipped();
        $connector = $this->container->get('hbpf.connector.facebook-get-leadform-connector');
        $system = $this->container->get('systems.facebookleads');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            'user_access_token' => 'EAAUmsI0AZCFEBAKdw4uSeW8oBszi8wrs2z1pJbL4nsAIj3PGb5E1wS6rv3VZBZBToiTy7IwQ01ZBOAt03stYZBeM0ObZAsw0LZCYTmNqYb50Oc7v9Kx0ZC9U0PFYR1Tl6uG8vq9XfcmjB2vwFpqnaYOhzDgaHV0YeRgjBZB46d13JNI29CoGQQmv5VxqdSDAvvbiMZAHFgy2o8fgZDZD',
        ];

        $systemInstall = new SystemInstall();
        $systemInstall
            ->setUser('u_123')
            ->setToken('t-456')
            ->setSystem('s_-666')
            ->setSettings($settings);

        $this->persistAndFlush($systemInstall);

        $dtoData = [
            'data' => [
                'system_install' => [
                    '_id'               => $systemInstall->getId(),
                    'user'              => $systemInstall->getUser(),
                    'token'             => $systemInstall->getToken(),
                    'system'            => $systemInstall->getSystem(),
                    'encryptedSettings' => CryptManager::encrypt($settings),
                ],
                'topology'       => ['name' => 'top-name-ever'],
            ],
        ];

        $node = (new Node())
            ->setName('Node')
            ->setTopology($topology->getId());
        $this->persistAndFlush($node);

        $processDto = (new ProcessDto())->setData(Json::encode($dtoData))->setHeaders([
            CMHeaders::createKey(CMHeaders::TOKEN)      => 't-456',
            CMHeaders::createKey(CMHeaders::GUID)       => 'u_123',
            CMHeaders::createKey(CMHeaders::SYSTEM_KEY) => 's_-666',
        ]);


        $pages = $connector->getLeadForms($system, $systemInstall, 'EAAUmsI0AZCFEBANL3u5O4p0lrljnvb3Jz1AH0R1Qu8VJGDUpmhwTWWcZBZC0dH8wkdD4Ynzxp5FFpIYScdBkk78VbptIvkft93SZCbaAoUv3sl4Eej3IBHGL14hT1bGfOefJCyaVLU2WXoUmo0JrCVZCAtaN74EgDSmSo746ocBSuNkZB65etPBtEIkXLNz6JOTbXT7eGciQZDZD');
        $this->assertTrue(is_array($pages));
        $this->assertCount(2, $pages);

    }

}