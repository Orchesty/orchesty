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

        self::assertInstanceOf(Schema::class, $schema);

        $nodes = $schema->getNodes();
        self::assertCount(9, $schema->getNodes());
        self::assertCount(6, $schema->getSequences());
        self::assertEquals('Event_1lqi8dm', $schema->getStartNode());

        foreach ($nodes as $node) {
            $this->assertObjectHasAttribute('handler', $node);
            $this->assertObjectHasAttribute('id', $node);
            $this->assertObjectHasAttribute('name', $node);
            $this->assertObjectHasAttribute('cronTime', $node);
            $this->assertObjectHasAttribute('pipesType', $node);
            $this->assertObjectHasAttribute('systemConfigs', $node);
        }

        $this->assertEquals('bpmn:event', $nodes['Event_1lqi8dm']->getHandler());
        $this->assertEquals('Event_1lqi8dm', $nodes['Event_1lqi8dm']->getId());
        $this->assertEquals('hubspot-updated-contact-connector', $nodes['Event_1lqi8dm']->getName());
        $this->assertEquals('', $nodes['Event_1lqi8dm']->getCronTime());
        $this->assertEquals('webhook', $nodes['Event_1lqi8dm']->getPipesType());

        $this->assertCount(9, $schema->getNodes());
        $this->assertCount(6, $schema->getSequences());
        $this->assertEquals('Event_1lqi8dm', $schema->getStartNode());

        self::assertEquals([
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
