<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems\Impl\SalesForce;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class SalesForceDeleteConnectorTest
 *
 * @package Tests\Integration\AppBundle\Model\Systems\Impl\SalesForce
 */
class SalesForceDeleteConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $this->markTestSkipped();
        $connector = $this->container->get('hbpf.connector.salesforce-delete-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            'access_token' => '00D1I000001WyE7!ARAAQIObuJ1gc6YcwxSsoolt9VvRJxyUeJZVP3ozOlKh9mQ8hxwgYNamy6_01wgynh4Lx.xqhHgZVGolJ4bm7lm2rkvsB9ZN',
            'instance_url' => 'https://na73.salesforce.com/',
        ];

        $system = new SystemInstall();
        $system
            ->setUser('u_123')
            ->setToken('t-456')
            ->setSystem('s_-879')
            ->setSettings($settings);
        $this->persistAndFlush($system);

        $node = (new Node())
            ->setName('Node')
            ->setTopology($topology->getId());
        $this->persistAndFlush($node);

        $dtoData = [
            'data' => [
                'system_install' => [
                    'id'                => $system->getId(),
                    'user'              => $system->getUser(),
                    'token'             => $system->getToken(),
                    'system'            => $system->getSystem(),
                    'encryptedSettings' => CryptManager::encrypt($settings),
                ],
                'topology'       => ['name' => 'top-name-ever'],
            ],
        ];

        $processDto = (new ProcessDto())->setData(Json::encode($dtoData))->setHeaders([]);
        $loop       = Factory::create();

        $process = $connector->processBatch($processDto, $loop, function (SuccessMessage $message): void {
            $this->assertTrue(is_array(Json::decode($message->getData(), TRUE)));
        });

        $process->then(
            function (): void {
                $this->assertTrue(TRUE);
            },
            function (): void {
                $this->assertTrue(FALSE);
            }
        )->done();

        $loop->run();
    }

}