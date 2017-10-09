<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems\Impl\Shopify;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class ShopifySyncConnectorTest
 *
 * @package Tests\Integration\AppBundle\Model\Systems\Impl\Shopify
 */
class ShopifySyncConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $connector = $this->container->get('hbpf.custom_node.shopify-sync-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $node = (new Node())
            ->setName('Node')
            ->setTopology($topology->getId());
        $this->persistAndFlush($node);

        $processDto = (new ProcessDto())
            ->setData(Json::encode([
                'settings' => [
                    'access_token' => '676ae188bd76d1957884be07c4af4e85',
                    'system_url'   => 'ndflakee',
                ],
            ]))->setHeaders([
                'X-Shopify-Access-Token' => '676ae188bd76d1957884be07c4af4e85',
                'node_id'                => $node->getId(),
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