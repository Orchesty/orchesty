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
use Nette\Utils\Json;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class FacebookGetPageConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\FacebookLeads\Connector
 */
class FacebookGetPageConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testGetAccounts(): void
    {
        $this->markTestSkipped();
        $connector = $this->container->get('hbpf.connector.facebook-get-page-connector');
        $system = $this->container->get('systems.facebookleads');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            OAuth2Provider::ACCESS_TOKEN=> 'EAAUmsI0AZCFEBAP06pWBjNw7xgTn93PtqOkR9WRlkjlNi8z78Ptogsnz5XiIxirUdboKN7oAwNk2QjHxfDr10KZCpPL68baLsbQG4MKxyW6YZAj5z0q1VapCX0cCrdhJCiwxpnsxRxYSX9KCvlZAWwvKO2vKnpRQryRY5EQKKg9fgJ84yZBLvFsl74WasfOnj2CtnsmOUWgZDZD',
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
            CMHeaders::createKey(CMHeaders::TOKEN)      => 't-456',
            CMHeaders::createKey(CMHeaders::GUID)       => 'u_123',
            CMHeaders::createKey(CMHeaders::SYSTEM_KEY) => 's_-666',
        ]);


        $pages = $connector->getAccounts($system, $systemInstall);
        $this->assertTrue(is_array($pages));
        $this->assertCount(5, $pages);

    }

}