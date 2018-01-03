<?php declare(strict_types=1);

namespace Tests\Unit\Utils;

use FOS\RestBundle\Decoder\XmlDecoder;
use Hanaboso\PipesFramework\Utils\Dto\Schema;
use Hanaboso\PipesFramework\Utils\TopologySchemaUtils;
use PHPUnit\Framework\TestCase;

/**
 * Class TopologySchemaUtilsTest
 *
 * @package Tests\Unit\Utils
 */
final class TopologySchemaUtilsTest extends TestCase
{

    /**
     * @var XmlDecoder
     */
    private $xmlDecoder;

    /**
     * @covers TopologySchemaUtils::getSchemaObject()
     */
    public function testGetSchemaObject(): void
    {
        $content = $this->load('default.tplg');
        $schema  = TopologySchemaUtils::getSchemaObject($this->getXmlDecoder()->decode($content));

        $this->assertInstanceOf(Schema::class, $schema);

        $nodes = $schema->getNodes();
        $this->assertCount(9, $schema->getNodes());
        $this->assertCount(6, $schema->getSequences());
        $this->assertEquals('Event_1lqi8dm', $schema->getStartNode());

        foreach ($nodes as $node) {
            $this->assertArrayHasKey('handler', $node);
            $this->assertArrayHasKey('id', $node);
            $this->assertArrayHasKey('name', $node);
            $this->assertArrayHasKey('cron_time', $node);
            $this->assertArrayHasKey('pipes_type', $node);
        }

        $this->assertEquals('bpmn:event', $nodes['Event_1lqi8dm']['handler']);
        $this->assertEquals('Event_1lqi8dm', $nodes['Event_1lqi8dm']['id']);
        $this->assertEquals('hubspot-updated-contact-connector', $nodes['Event_1lqi8dm']['name']);
        $this->assertEquals('', $nodes['Event_1lqi8dm']['cron_time']);
        $this->assertEquals('webhook', $nodes['Event_1lqi8dm']['pipes_type']);

        $this->assertEquals([
            'Event_1lqi8dm' => ['Task_1taayin'],
            'Task_1taayin'  => ['Task_152x7cw', 'Task_1niijps'],
            'Task_1wcc82o'  => ['Task_0h8gpta'],
            'Task_152x7cw'  => ['Task_0fzjb0y'],
            'Task_1niijps'  => ['Task_1wcc82o', 'Task_0nwvqkt'],
            'Task_0nwvqkt'  => ['Task_00wzy7d'],
        ], $schema->getSequences());
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function load(string $name): string
    {
        return file_get_contents(sprintf('%s/data/%s', __DIR__, $name));
    }

    /**
     * @return XmlDecoder
     */
    private function getXmlDecoder(): XmlDecoder
    {
        if (!$this->xmlDecoder) {
            $this->xmlDecoder = new XmlDecoder();
        }

        return $this->xmlDecoder;
    }

}