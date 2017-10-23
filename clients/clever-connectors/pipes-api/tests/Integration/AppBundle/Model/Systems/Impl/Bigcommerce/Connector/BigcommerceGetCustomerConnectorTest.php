<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems\Impl\Bigcommerce\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Nette\Utils\Json;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class BigcommerceGetCustomerConnectorTest
 *
 * @package Tests\Integration\AppBundle\Model\Systems\Impl\Bigcommerce\Connector
 */
final class BigcommerceGetCustomerConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testEvent(): void
    {
        $this->markTestSkipped();
        $connector = $this->container->get('hbpf.connector.bigcommerce-get-customer-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            'store_id'     => 'ukcfcghi',
            'client_id'    => 'p7f4o1hfl1zdkz1bp1sy7u8qs0fq7q',
            'access_token' => '7ndpkdbqb0h1wycrxhtw43ye0yprtn9',
        ];

        $system = (new SystemInstall())
            ->setUser('u_123')
            ->setToken('t-456')
            ->setSystem('s_-879')
            ->setSettings($settings);
        $this->persistAndFlush($system);

        $node = (new Node())
            ->setName('Node')
            ->setTopology($topology->getId());
        $this->persistAndFlush($node);

        $processDto = (new ProcessDto())->setHeaders([
            'pf-guid'       => $system->getUser(),
            'pf-token'      => $system->getToken(),
            'pf-system-key' => $system->getSystem(),
        ])->setData('{"id":1}');

        $dto = $connector->processAction($processDto);
        $this->assertTrue(is_array(Json::decode($dto->getData(), TRUE)));
    }

}