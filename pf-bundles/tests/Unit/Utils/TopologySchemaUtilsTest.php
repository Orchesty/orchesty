<?php declare(strict_types=1);

namespace Tests\Unit\Utils;

use Hanaboso\PipesFramework\Utils\TopologySchemaUtils;
use Hanaboso\RestBundle\Model\Decoder\XmlDecoder;
use Tests\KernelTestCaseAbstract;

/**
 * Class TopologySchemaUtilsTest
 *
 * @package Tests\Unit\Utils
 */
final class TopologySchemaUtilsTest extends KernelTestCaseAbstract
{

    /**
     * @covers TopologySchemaUtils::getSchemaObject()
     */
    public function testGetSchemaObject(): void
    {
        $content = $this->load('default.tplg');
        $schema  = TopologySchemaUtils::getSchemaObject($this->getXmlDecoder()->decode($content));

        self::assertNotEmpty($schema);

        $nodes = $schema->getNodes();
        self::assertCount(9, $schema->getNodes());
        self::assertCount(6, $schema->getSequences());
        self::assertEquals('Event_1lqi8dm', $schema->getStartNode());

        foreach ($nodes as $node) {
            self::assertObjectHasAttribute('handler', $node);
            self::assertObjectHasAttribute('id', $node);
            self::assertObjectHasAttribute('name', $node);
            self::assertObjectHasAttribute('cronTime', $node);
            self::assertObjectHasAttribute('pipesType', $node);
            self::assertObjectHasAttribute('systemConfigs', $node);
        }

        self::assertEquals('bpmn:event', $nodes['Event_1lqi8dm']->getHandler());
        self::assertEquals('Event_1lqi8dm', $nodes['Event_1lqi8dm']->getId());
        self::assertEquals('hubspot-updated-contact-connector', $nodes['Event_1lqi8dm']->getName());
        self::assertEquals('', $nodes['Event_1lqi8dm']->getCronTime());
        self::assertEquals('webhook', $nodes['Event_1lqi8dm']->getPipesType());

        self::assertCount(9, $schema->getNodes());
        self::assertCount(6, $schema->getSequences());
        self::assertEquals('Event_1lqi8dm', $schema->getStartNode());

        self::assertEquals(
            [
                'Event_1lqi8dm' => ['Task_1taayin'],
                'Task_1taayin'  => ['Task_152x7cw', 'Task_1niijps'],
                'Task_1wcc82o'  => ['Task_0h8gpta'],
                'Task_152x7cw'  => ['Task_0fzjb0y'],
                'Task_1niijps'  => ['Task_1wcc82o', 'Task_0nwvqkt'],
                'Task_0nwvqkt'  => ['Task_00wzy7d'],
            ],
            $schema->getSequences()
        );
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function load(string $name): string
    {
        return (string) file_get_contents(sprintf('%s/data/%s', __DIR__, $name));
    }

    /**
     * @return XmlDecoder
     */
    private function getXmlDecoder(): XmlDecoder
    {
        return self::$container->get('rest.decoder.xml');
    }

}
