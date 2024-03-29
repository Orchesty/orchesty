<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Utils;

use Exception;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Utils\TopologySchemaUtils;
use Hanaboso\RestBundle\Model\Decoder\XmlDecoder;
use Hanaboso\Utils\File\File;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class TopologySchemaUtilsTest
 *
 * @package PipesFrameworkTests\Unit\Utils
 */
final class TopologySchemaUtilsTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Utils\TopologySchemaUtils::getSchemaObject
     * @covers \Hanaboso\PipesFramework\Utils\TopologySchemaUtils::getPipesType
     * @covers \Hanaboso\PipesFramework\Utils\TopologySchemaUtils::createConfigDto
     *
     * @throws Exception
     */
    public function testGetSchemaObject(): void
    {
        $content = $this->load('default.tplg');
        $schema  = TopologySchemaUtils::getSchemaObject($this->getXmlDecoder()->decode($content));

        $nodes = $schema->getNodes();
        self::assertCount(9, $schema->getNodes());
        self::assertCount(6, $schema->getSequences());
        self::assertEquals(['Event_1lqi8dm'], $schema->getStartNode());

        self::assertEquals('bpmn:event', $nodes['Event_1lqi8dm']->getHandler());
        self::assertEquals('Event_1lqi8dm', $nodes['Event_1lqi8dm']->getId());
        self::assertEquals('hubspot-updated-contact-connector', $nodes['Event_1lqi8dm']->getName());
        self::assertEquals('', $nodes['Event_1lqi8dm']->getCronTime());
        self::assertEquals('webhook', $nodes['Event_1lqi8dm']->getPipesType());

        self::assertCount(9, $schema->getNodes());
        self::assertCount(6, $schema->getSequences());
        self::assertEquals(['Event_1lqi8dm'], $schema->getStartNode());

        self::assertEquals(
            [
                'Event_1lqi8dm' => ['Task_1taayin'],
                'Task_0nwvqkt'  => ['Task_00wzy7d'],
                'Task_1niijps'  => ['Task_1wcc82o', 'Task_0nwvqkt'],
                'Task_1taayin'  => ['Task_152x7cw', 'Task_1niijps'],
                'Task_1wcc82o'  => ['Task_0h8gpta'],
                'Task_152x7cw'  => ['Task_0fzjb0y'],
            ],
            $schema->getSequences(),
        );

        $content = $this->load('tplg-no-process.tplg');
        try {
            TopologySchemaUtils::getSchemaObject($this->getXmlDecoder()->decode($content));
        } catch (TopologyException $e) {
            self::assertEquals('Unsupported schema!', $e->getMessage());
            self::assertEquals(TopologyException::UNSUPPORTED_SCHEMA, $e->getCode());
        }

        $content = $this->load('tplg-no-type.tplg');
        $schema  = TopologySchemaUtils::getSchemaObject($this->getXmlDecoder()->decode($content));
        self::assertCount(9, $schema->getNodes());
    }

    /**
     * @covers \Hanaboso\PipesFramework\Utils\TopologySchemaUtils::getPipesType
     *
     * @throws Exception
     */
    public function testGetPipesType(): void
    {
        $topo   = new TopologySchemaUtils();
        $result = $this->invokeMethod($topo, 'getPipesType', ['']);
        self::assertEquals('', $result);

        $result = $this->invokeMethod($topo, 'getPipesType', ['bpmn:exclusiveGateway']);
        self::assertEquals('gateway', $result);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function load(string $name): string
    {
        return File::getContent(sprintf('%s/data/%s', __DIR__, $name));
    }

    /**
     * @return XmlDecoder
     */
    private function getXmlDecoder(): XmlDecoder
    {
        return self::getContainer()->get('rest.decoder.xml');
    }

}
