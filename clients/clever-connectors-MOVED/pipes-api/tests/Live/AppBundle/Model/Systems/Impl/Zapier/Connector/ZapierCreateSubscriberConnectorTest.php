<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Zapier\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\ZapierSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Nette\Utils\Json;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class ZapierCreateSubscriberConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Zapier\Connector
 */
class ZapierCreateSubscriberConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testProcessAction(): void
    {
        $this->markTestSkipped();
        $connector = $this->container->get('hbpf.connector.zapier-create-subscriber-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            ZapierSystem::CREATE_WEBHOOK_URL => 'https://hooks.zapier.com/hooks/catch/2624456/8e4qdj/',
        ];

        $system = new SystemInstall();
        $system
            ->setUser('zapier')
            ->setToken('zapier')
            ->setSystem('zapier')
            ->setSettings($settings);
        $this->persistAndFlush($system);

        $node = (new Node())
            ->setName('Node')
            ->setTopology($topology->getId());
        $this->persistAndFlush($node);

        $dtoData = [
            CleverFieldsEnum::HARD_BOUNCE => FALSE,
            CleverFieldsEnum::UNSUBSCRIBE => FALSE,
            CleverFieldsEnum::EMAIL       => 'test5@asdfg.com',
            CleverFieldsEnum::FIRST_NAME  => 'Karel5',
            CleverFieldsEnum::LAST_NAME   => 'Barel5',
        ];

        $processDto = (new ProcessDto())->setData(Json::encode($dtoData))->setHeaders([
            CMHeaders::createKey(CMHeaders::NODE_NAME)     => 'Node',
            CMHeaders::createKey(CMHeaders::TOPOLOGY_NAME) => 'Topology',
            CMHeaders::createKey(CMHeaders::GUID)          => 'zapier',
            CMHeaders::createKey(CMHeaders::TOKEN)         => 'zapier',
            CMHeaders::createKey(CMHeaders::SYSTEM_KEY)    => 'zapier',
        ]);

        $connector->processAction($processDto);
    }

}