<?php
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 1/17/18
 * Time: 4:28 PM
 */

namespace Tests\Live\AppBundle\Model\Systems\Impl\Zapier\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\ZapierSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
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
        $connector = $this->container->get('hbpf.connector.zapier-create-subscriber-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            ZapierSystem::CREATE_WEBHOOK_URL => 'https://hooks.zapier.com/hooks/catch/2624456/8lttm4/',
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
            'system_install' => [
                '_id'               => $system->getId(),
                'user'              => $system->getUser(),
                'token'             => $system->getToken(),
                'system'            => $system->getSystem(),
                'encryptedSettings' => CryptManager::encrypt($settings),
            ],
            'data'           => json_encode([
                CleverFieldsEnum::HARD_BOUNCE => FALSE,
                CleverFieldsEnum::UNSUBSCRIBE => FALSE,
                CleverFieldsEnum::EMAIL       => 'test@asdfg.com',
                CleverFieldsEnum::FIRST_NAME  => 'Karel',
                CleverFieldsEnum::LAST_NAME   => 'Barel',
            ]),
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