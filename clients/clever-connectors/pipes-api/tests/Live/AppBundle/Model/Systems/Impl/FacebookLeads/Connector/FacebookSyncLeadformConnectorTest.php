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
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class FacebookSyncLeadformConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\FacebookLeads\Connector
 */
class FacebookSyncLeadformConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $this->markTestSkipped();
        $result = NULL;
        $connector = $this->container->get('hbpf.connector.facebook-sync-leadform-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            'user_access_token' => 'EAAUmsI0AZCFEBAKdw4uSeW8oBszi8wrs2z1pJbL4nsAIj3PGb5E1wS6rv3VZBZBToiTy7IwQ01ZBOAt03stYZBeM0ObZAsw0LZCYTmNqYb50Oc7v9Kx0ZC9U0PFYR1Tl6uG8vq9XfcmjB2vwFpqnaYOhzDgaHV0YeRgjBZB46d13JNI29CoGQQmv5VxqdSDAvvbiMZAHFgy2o8fgZDZD',
            'page_access_token' => 'EAAUmsI0AZCFEBAGITZBIgsTcXEJMnyUVDOLB9oY5nkSVXUUKzOiVpwL6T1bBZC5QZAdHfnbwnl94oksoKLp7N4PZAtKd2DgFTIs3PHj7caTet93NMCaNf2SkGFdltkZCAwKe0tzADPZCHfgFsM84gZCK7goBoWIyAkbsRwMAeP9YvHHD0zg3Kc4AXJ8FGPdPB6wZD',
            'form_id' => '505108016512972',
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

        $loop = Factory::create();

        $process = $connector->processBatch($processDto, $loop,
            function (SuccessMessage $message) use (&$result): void {
                $result = $message;
            });
        $process->done();

        $loop->run();

        $this->assertInstanceOf(SuccessMessage::class, $result);

    }

}