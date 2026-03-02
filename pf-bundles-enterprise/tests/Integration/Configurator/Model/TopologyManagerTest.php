<?php declare(strict_types=1);

namespace PipesFrameworkEnterpriseTests\Integration\Configurator\Model;

use Exception;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFrameworkEnterprise\Configurator\Model\TopologyManager;
use Hanaboso\PipesFrameworkEnterprise\Database\Document\Topology;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkEnterpriseTests\DatabaseTestCaseAbstract;

/**
 * Class TopologyManagerTest
 *
 * @package PipesFrameworkEnterpriseTests\Integration\Configurator\Model
 */
#[CoversClass(TopologyManager::class)]
#[AllowMockObjectsWithoutExpectations]
final class TopologyManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var TopologyManager
     */
    private TopologyManager $manager;

    /**
     * @throws Exception
     */
    public function testCreateTopologyWithMcpDescription(): void
    {
        $mcpDescription = [
            'input_schema'  => [
                'properties' => ['location' => ['type' => 'string']],
                'required'   => ['location'],
                'type'       => 'object',
            ],
            'kind'          => 'query',
            'output_schema' => [
                'properties' => ['text' => ['type' => 'string']],
                'required'   => ['text'],
                'type'       => 'object',
            ],
        ];

        $topology = $this->manager->createTopology([
            'mcp_description' => $mcpDescription,
            'name'            => 'TestTopology',
        ]);

        self::assertInstanceOf(Topology::class, $topology);
        self::assertEquals($mcpDescription, $topology->getMcpDescription());
    }

    /**
     * @throws Exception
     */
    public function testUpdateTopologyWithMcpDescription(): void
    {
        $topology = (new Topology())->setName('TestTopology');
        $this->persistAndFlush($topology);

        $mcpDescription = [
            'input_schema'  => ['properties' => ['name' => ['type' => 'string']], 'type' => 'object'],
            'kind'          => 'command',
            'output_schema' => ['properties' => ['result' => ['type' => 'string']], 'type' => 'object'],
        ];

        $this->manager->updateTopology($topology, ['mcp_description' => $mcpDescription]);
        $this->dm->clear();

        /** @var Topology $updatedTopology */
        $updatedTopology = $this->dm->getRepository(Topology::class)->findOneBy(['id' => $topology->getId()]);
        self::assertEquals($mcpDescription, $updatedTopology->getMcpDescription());
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = self::getContainer()->get('hbpf.configurator.manager.topology');

        $cronManager = self::getContainer()->get('hbpf.cron.manager');
        $this->setProperty($cronManager, 'curlManager', self::createMock(CurlManagerInterface::class));
        $this->setProperty($this->manager, 'cronManager', $cronManager);
    }

}
