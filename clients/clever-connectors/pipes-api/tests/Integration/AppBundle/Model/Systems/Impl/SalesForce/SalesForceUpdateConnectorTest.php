<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems\Impl\SalesForce;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
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
        $connector = $this->container->get('hbpf.custom_node.salesforce-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $node = (new Node())
            ->setName('Node')
            ->setTopology($topology->getId());

        $this->persistAndFlush($node);

        $processDto = new ProcessDto();
        $processDto->setData(Json::encode([
            'system'   => 'Bearer 00D1I000001WyE7!ARAAQEza5QDZ3b2kfre2tZhM48dzRlC8nnrrmUBHYtUiUYFLvj8nmL3CCquz29k1Yz6q7SnORxPuW.WTuT2in_pxfYuMH_eA',
            'settings' => [
                'access_token' => 'b',
                'instance_url' => 'https://na73.salesforce.com/',
            ],
        ]))->setHeaders([
            'Authorization' => 'Bearer 00D1I000001WyE7!ARAAQEza5QDZ3b2kfre2tZhM48dzRlC8nnrrmUBHYtUiUYFLvj8nmL3CCquz29k1Yz6q7SnORxPuW.WTuT2in_pxfYuMH_eA',
            'node_id'       => $node->getId(),
        ]);

        $loop = Factory::create();

        $connector->processBatch(
            $processDto,
            $loop,
            function (): void {

            }
        );
    }

}