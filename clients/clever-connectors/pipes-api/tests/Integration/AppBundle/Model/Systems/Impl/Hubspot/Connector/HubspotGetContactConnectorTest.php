<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems\Impl\Hubspot\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Nette\Utils\Json;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class HubspotGetContactConnectorTest
 *
 * @package Tests\Integration\AppBundle\Model\Systems\Impl\Hubspot\Connector
 */
class HubspotGetContactConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $this->markTestSkipped();
        $connector = $this->container->get('hbpf.connector.hubspot-get-contact-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            "access_token"  => "COPd6sr0KxICAQEY75vzASD-46cCKL-1AzIZADUnP_ZsvWK5R1k2VxSQarO2253kiPZ3PQ",
            "expires_in"    => 21600,
            "refresh_token" => "221f086d-1760-4cb2-8260-c6833137bdd7",
            "app_id"        => 55999,
        ];

        $system = new SystemInstall();
        $system
            ->setUser('hs_u_123')
            ->setToken('hs_t-456')
            ->setSystem('hs_s_-879')
            ->setSettings($settings);
        $this->persistAndFlush($system);

        $dtoData = [
            'objectId'         => 51,
            'subscriptionType' => 'contact.creation',
        ];

        $node = (new Node())
            ->setName('Node')
            ->setTopology($topology->getId());
        $this->persistAndFlush($node);

        $headers[CMHeaders::createKey(CMHeaders::GUID)]       = $system->getUser();
        $headers[CMHeaders::createKey(CMHeaders::TOKEN)]      = $system->getToken();
        $headers[CMHeaders::createKey(CMHeaders::SYSTEM_KEY)] = $system->getSystem();

        $processDto = (new ProcessDto())->setData(Json::encode($dtoData))->setHeaders($headers);
        $dto        = $connector->processEvent($processDto);
        $data       = json_decode($dto->getData(), TRUE);

        self::assertEquals(51, $data['vid']);
        self::assertEquals('bh@hubspot.com', $data['properties']['email']['value']);
        self::assertEquals('Brian', $data['properties']['firstname']['value']);
        self::assertEquals('Halligan (Sample Contact)', $data['properties']['lastname']['value']);
    }

}