<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\TopologyController;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\ControllerTestCaseAbstract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TopologyControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 */
#[CoversClass(TopologyController::class)]
final class TopologyControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testCreateTopologiesAction(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/TopologyController/createTopologyRequest.json',
            ['_id' => '123456789'],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetTopologies(): void
    {
        $topology = (new Topology())->setName('Topology')->setEnabled(TRUE);
        $this->pfd($topology);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/TopologyController/getTopologiesRequest.json',
            ['_id' => '123456789'],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetCronTopologiesAction(): void
    {
        $this->assertResponseLogged($this->jwt,__DIR__ . '/data/TopologyController/getCronTopologiesRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testGetTopologyAction(): void
    {
        $topology = (new Topology())->setName('Topology')->setEnabled(TRUE);
        $this->pfd($topology);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/TopologyController/getTopologyRequest.json',
            ['_id' => '123456789'],
            [':id' => $topology->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testCreateTopologyAction(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/TopologyController/createTopologyRequest.json',
            ['_id' => '123456789'],
        );
    }

    /**
     * @throws Exception
     */
    public function testUpdateTopologyAction(): void
    {
        $topology = (new Topology())->setName('Topology')->setEnabled(TRUE);
        $this->pfd($topology);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/TopologyController/updateTopologyRequest.json',
            ['_id' => '123456789'],
            [':id' => $topology->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetTopologySchemaAction(): void
    {
        $topology = (new Topology())->setName('Topology')->setRawBpmn(TopologyManager::DEFAULT_SCHEME);
        $this->pfd($topology);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/TopologyController/getTopologySchemaRequest.json',
            ['_id' => '123456789'],
            [':id' => $topology->getId()],
            [],
            [],
            static fn(Response $response): array => [$response->getContent()],
        );
    }

    /**
     * @throws Exception
     */
    public function testSaveTopologySchemaAction(): void
    {
        $topology = (new Topology())->setName('Topology')->setEnabled(TRUE);
        $this->pfd($topology);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/TopologyController/saveTopologySchemaRequest.json',
            ['_id' => '123456789'],
            [':id' => $topology->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testPublishTopologyAction(): void
    {
        $topology = (new Topology())->setName('Topology');
        $this->pfd($topology);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/TopologyController/publishTopologySchemaRequest.json',
            ['_id' => '123456789'],
            [':id' => $topology->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testCloneTopologyAction(): void
    {
        $topology = (new Topology())->setName('Topology');
        $this->pfd($topology);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/TopologyController/cloneTopologyRequest.json',
            ['_id' => '123456789'],
            [':id' => $topology->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testDeleteTopologyAction(): void
    {
        $topology = (new Topology())->setName('Topology');
        $this->pfd($topology);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/TopologyController/deleteTopologyRequest.json',
            ['message' => 'CurlManager::send() failed: cURL error 6: Could not resolve host: topology-api (see https://curl.haxx.se/libcurl/c/libcurl-errors.html)'],
            [':id' => $topology->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testTestTopologyAction(): void
    {
        $topology = (new Topology())->setName('Topology');
        $this->pfd($topology);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/TopologyController/testTopologyRequest.json',
            [],
            [':id' => $topology->getId()],
        );
    }

}
