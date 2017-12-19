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
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class FacebookCreatedLeadformConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\FacebookLeads\Connector
 */
class FacebookCreatedLeadformConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $this->markTestSkipped();
        $result    = NULL;
        $connector = $this->container->get('hbpf.connector.facebook-created-leadform-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            OAuth2Provider::ACCESS_TOKEN => 'EAAUmsI0AZCFEBAJx7txMNeZBtkZAlhUNckltZCX54EGlZBMrZAe5pPQqOyE7wjxikAboUDp0QHMKlPS5ZCR5mOTqajZBRervKrsa5T0TcQbKzFu8wZBxkwowCsKE59uGqPbHc4t996XvMjsz5MbXjeygpWbi2gzkZBzYZBIW9w3CBxR1BhtiUwaYf2vFckUHg78fZCJyfpwoJArwwAZDZD',
            'form_id'           => '505108016512972',
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
            CMHeaders::createKey(CMHeaders::TOKEN)         => 't-456',
            CMHeaders::createKey(CMHeaders::GUID)          => 'u_123',
            CMHeaders::createKey(CMHeaders::SYSTEM_KEY)    => 's_-666',
            CMHeaders::createKey(CMHeaders::TOPOLOGY_NAME) => 'top-name',
            CMHeaders::createKey(CMHeaders::NODE_NAME)     => 'node-name',
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