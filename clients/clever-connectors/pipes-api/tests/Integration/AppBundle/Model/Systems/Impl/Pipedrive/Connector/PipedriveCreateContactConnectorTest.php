<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems\Impl\Pipedrive\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Nette\Utils\Json;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class PipedriveCreateContactConnectorTest
 *
 * @package Tests\Integration\AppBundle\Model\Systems\Impl\Pipedrive\Connector
 */
final class PipedriveCreateContactConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $this->markTestSkipped();
        $connector = $this->container->get('hbpf.connector.pipedrive-create-person-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            'api_token' => '8fa78c69f4127817268e031d1ca54091189a1a2a',
        ];

        $system = new SystemInstall();
        $system
            ->setUser('pipes')
            ->setToken('pipes')
            ->setSystem('pipes')
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
                'c053adc8eac18eba88966df2c2792d48bfd504ec' => 'false',
                '82b9786646675437c4a64aa85e321da1b31dd6ba' => 'true',
                CleverFieldsEnum::EMAIL                    => 'test@asdfg.com',
                'name'                                     => 'namae tora',
            ]),
        ];

        $processDto = (new ProcessDto())->setData(Json::encode($dtoData))->setHeaders([
            CMHeaders::createKey(CMHeaders::NODE_NAME)     => 'Node',
            CMHeaders::createKey(CMHeaders::TOPOLOGY_NAME) => 'Topology',
            CMHeaders::createKey(CMHeaders::GUID)          => 'pipes',
            CMHeaders::createKey(CMHeaders::TOKEN)         => 'pipes',
            CMHeaders::createKey(CMHeaders::SYSTEM_KEY)    => 'pipes',
        ]);

        $connector->processEvent($processDto);
    }

}