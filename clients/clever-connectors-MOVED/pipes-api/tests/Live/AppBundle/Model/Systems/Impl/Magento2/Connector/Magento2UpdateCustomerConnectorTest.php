<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Magento2\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Hanaboso\CommonsBundle\Crypt\CryptManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class Magento2UpdateCustomerConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Magento2Old\Connector
 */
final class Magento2UpdateCustomerConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $this->markTestSkipped();
        $connector = $this->container->get('hbpf.connector.magento2-update-customer-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            'access_token' => 'd4y2ujldkrjwifkegbfspesf23mm5lsq',
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

        $processDto = (new ProcessDto())->setData(Json::encode($dtoData))->setHeaders(['node_id' => $node->getId()]);
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