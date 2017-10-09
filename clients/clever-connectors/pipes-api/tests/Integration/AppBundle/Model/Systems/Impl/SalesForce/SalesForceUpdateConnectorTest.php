<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems\Impl\SalesForce;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class SalesForceUpdateConnectorTest
 *
 * @package Tests\Integration\AppBundle\Model\Systems\Impl\SalesForce
 */
final class SalesForceUpdateConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $connector = $this->container->get('hbpf.custom_node.salesforce-update-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $system = new SystemInstall();
        $system
            ->setUser('u_123')
            ->setToken('t-456')
            ->setSystem('s_-879')
            ->setSettings([
                'access_token' => '00D1I000001WyE7!ARAAQEza5QDZ3b2kfre2tZhM48dzRlC8nnrrmUBHYtUiUYFLvj8nmL3CCquz29k1Yz6q7SnORxPuW.WTuT2in_pxfYuMH_eA',
                'instance_url' => 'https://na73.salesforce.com/',
            ]);
        $this->persistAndFlush($system);

        $node = (new Node())
            ->setName('Node')
            ->setTopology($topology->getId());
        $this->persistAndFlush($node);

        $processDto = (new ProcessDto())
            ->setData(Json::encode([
                'user'   => $system->getUser(),
                'token'  => $system->getToken(),
                'system' => $system->getSystem(),
            ]))->setHeaders([
                'Authorization' => 'Bearer 00D1I000001WyE7!ARAAQEza5QDZ3b2kfre2tZhM48dzRlC8nnrrmUBHYtUiUYFLvj8nmL3CCquz29k1Yz6q7SnORxPuW.WTuT2in_pxfYuMH_eA',
                'node_id'       => $node->getId(),
            ]);

        $loop = Factory::create();

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