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
use Hanaboso\CommonsBundle\Crypt\CryptManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
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
        $result    = NULL;
        $connector = $this->container->get('hbpf.connector.facebook-sync-leadform-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            OAuth2Provider::ACCESS_TOKEN => 'EAAUmsI0AZCFEBAEiZBMvJaJFSb1sKJPAOt0LL48tkw5rk052UfNG26kCxae0JROuuwnpHD4s3lR59h3YKNqs1tfz6WwyNPARYPAkwb2BUKqIlqxSCS0GJXPNIxGT9bOsZCB23XCJ1v9moe1xLobXqvX4vqoKkHQSyrqoxYZAGscTYuf1HM3ZCT6Jnpxm6sY1Dc7ClrQRR9gZDZD',
            'form_id'                    => '505108016512972',
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