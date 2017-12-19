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
 * Class FacebookGetLeadformConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\FacebookLeads\Connector
 */
class FacebookGetLeadformConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testGetLeadForms(): void
    {
        $this->markTestSkipped();
        $connector = $this->container->get('hbpf.connector.facebook-get-leadform-connector');
        $system = $this->container->get('systems.facebookleads');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            OAuth2Provider::ACCESS_TOKEN => 'EAAUmsI0AZCFEBAJx7txMNeZBtkZAlhUNckltZCX54EGlZBMrZAe5pPQqOyE7wjxikAboUDp0QHMKlPS5ZCR5mOTqajZBRervKrsa5T0TcQbKzFu8wZBxkwowCsKE59uGqPbHc4t996XvMjsz5MbXjeygpWbi2gzkZBzYZBIW9w3CBxR1BhtiUwaYf2vFckUHg78fZCJyfpwoJArwwAZDZD',
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


        $forms = $connector->getLeadForms($system, $systemInstall, '787114551385792');
        $this->assertTrue(is_array($forms));
        $this->assertCount(2, $forms);

    }

}