<?php declare(strict_types=1);

namespace PipesFrameworkEnterpriseTests\Integration\Database\Document;

use Exception;
use Hanaboso\PipesFrameworkEnterprise\Database\Document\Topology;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkEnterpriseTests\DatabaseTestCaseAbstract;

/**
 * Class TopologyTest
 *
 * @package PipesFrameworkEnterpriseTests\Integration\Database\Document
 */
#[CoversClass(Topology::class)]
final class TopologyTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testMcpDescription(): void
    {
        $mcpDescription = [
            'input_schema'  => [
                'properties' => [
                    'date'     => ['format' => 'date', 'type' => 'string'],
                    'location' => ['type' => 'string'],
                ],
                'required'   => ['location'],
                'type'       => 'object',
            ],
            'kind'          => 'query',
            'output_schema' => [
                'properties' => [
                    'humidity'    => ['type' => 'number'],
                    'temperature' => ['type' => 'number'],
                    'text'        => ['type' => 'string'],
                ],
                'required'   => ['temperature', 'humidity', 'text'],
                'type'       => 'object',
            ],
        ];

        $topology = (new Topology())
            ->setName('test')
            ->setMcpDescription($mcpDescription);
        $this->persistAndFlush($topology);

        self::assertEquals($mcpDescription, $topology->getMcpDescription());
    }

    /**
     * @throws Exception
     */
    public function testMcpDescriptionEmpty(): void
    {
        $topology = (new Topology())->setName('test');
        $this->persistAndFlush($topology);

        self::assertEquals([], $topology->getMcpDescription());
    }

}
