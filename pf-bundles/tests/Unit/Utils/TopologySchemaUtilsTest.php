<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Utils;

use Exception;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Utils\TopologySchemaUtils;
use Hanaboso\RestBundle\Model\Decoder\XmlDecoder;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class TopologySchemaUtilsTest
 *
 * @package PipesFrameworkTests\Unit\Utils
 */
#[CoversClass(TopologySchemaUtils::class)]
final class TopologySchemaUtilsTest extends KernelTestCaseAbstract
{

    /**
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

        self::assertSame('bpmn:event', $nodes['Event_1lqi8dm']->getHandler());
        self::assertSame('Event_1lqi8dm', $nodes['Event_1lqi8dm']->getId());
        self::assertSame('hubspot-updated-contact-connector', $nodes['Event_1lqi8dm']->getName());
        self::assertSame('', $nodes['Event_1lqi8dm']->getCronTime());
        self::assertSame('webhook', $nodes['Event_1lqi8dm']->getPipesType());

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
            self::assertSame('Unsupported schema!', $e->getMessage());
            self::assertSame(TopologyException::UNSUPPORTED_SCHEMA, $e->getCode());
        }

        $content = $this->load('tplg-no-type.tplg');
        $schema  = TopologySchemaUtils::getSchemaObject($this->getXmlDecoder()->decode($content));
        self::assertCount(9, $schema->getNodes());
    }

    /**
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
     * @throws Exception
     */
    public function testGetSchemaObjectFromJson(): void
    {
        $data      = $this->loadJson('schema-json-editor.json');
        $sdkUrlMap = ['php-sdk' => 'php-sdk:8080'];
        $schema    = TopologySchemaUtils::getSchemaObjectFromJson($data, $sdkUrlMap);

        $nodes = $schema->getNodes();
        self::assertCount(7, $nodes);
        self::assertCount(2, $schema->getSequences());
        self::assertEquals(['node-cron'], $schema->getStartNode());

        self::assertSame('bpmn:event', $nodes['node-start']->getHandler());
        self::assertSame('start', $nodes['node-start']->getPipesType());
        self::assertSame('start', $nodes['node-start']->getName());

        self::assertSame('bpmn:task', $nodes['node-connector']->getHandler());
        self::assertSame('connector', $nodes['node-connector']->getPipesType());
        self::assertSame('Connector DEF', $nodes['node-connector']->getName());
        self::assertSame('php-sdk:8080', $nodes['node-connector']->getSystemConfigs()->getSdkHost());

        self::assertSame('bpmn:task', $nodes['node-mapper']->getHandler());
        self::assertSame('mapper', $nodes['node-mapper']->getPipesType());
        self::assertSame('Mapper XYZ', $nodes['node-mapper']->getName());

        self::assertSame('bpmn:task', $nodes['node-parser']->getHandler());
        self::assertSame('xml_parser', $nodes['node-parser']->getPipesType());
        self::assertSame('Parser ABC', $nodes['node-parser']->getName());
        self::assertSame('php-sdk:8080', $nodes['node-parser']->getSystemConfigs()->getSdkHost());

        self::assertSame('bpmn:task', $nodes['node-splitter']->getHandler());
        self::assertSame('splitter', $nodes['node-splitter']->getPipesType());
        self::assertSame('Splitter SPI', $nodes['node-splitter']->getName());

        self::assertSame('bpmn:event', $nodes['node-cron']->getHandler());
        self::assertSame('cron', $nodes['node-cron']->getPipesType());
        self::assertSame('cron', $nodes['node-cron']->getName());
        self::assertSame('', $nodes['node-cron']->getSystemConfigs()->getSdkHost());

        self::assertSame('bpmn:event', $nodes['node-webhook']->getHandler());
        self::assertSame('webhook', $nodes['node-webhook']->getPipesType());
        self::assertSame('webhook', $nodes['node-webhook']->getName());

        self::assertEquals(
            [
                'node-cron'   => ['node-parser'],
                'node-parser' => ['node-connector'],
            ],
            $schema->getSequences(),
        );
    }

    /**
     * @throws Exception
     */
    public function testGetSchemaObjectFromJsonEmpty(): void
    {
        $schema = TopologySchemaUtils::getSchemaObjectFromJson([], []);

        self::assertCount(0, $schema->getNodes());
        self::assertCount(0, $schema->getSequences());
    }

    /**
     * @throws Exception
     */
    public function testGetSchemaObjectFromJsonNoNodes(): void
    {
        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::UNSUPPORTED_SCHEMA);

        TopologySchemaUtils::getSchemaObjectFromJson(['foo' => 'bar'], []);
    }

    /**
     * @throws Exception
     */
    public function testGetSchemaObjectFromJsonRefusesTwoOutputsToSameNode(): void
    {
        $data                  = $this->loadJson('schema-json-editor.json');
        $data['connections'][] = ['from' => 'node-cron', 'id' => 'conn-x', 'sourceOutput' => 'no', 'to' => 'node-parser'];

        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::DUPLICATE_SEQUENCE);

        TopologySchemaUtils::getSchemaObjectFromJson($data, ['php-sdk' => 'php-sdk:8080']);
    }

    /**
     * @throws Exception
     */
    public function testSwappedConfirmOutputsChangeTheHashes(): void
    {
        $sdkUrlMap = ['php-sdk' => 'php-sdk:8080'];
        $plain     = $this->loadJson('schema-json-editor.json');
        $labelled  = static function (string $toConnector, string $toMapper) use ($plain): array {
            $data                = $plain;
            $data['connections'] = [
                ['from' => 'node-cron', 'id' => 'conn-1', 'to' => 'node-parser'],
                ['from' => 'node-parser', 'id' => 'conn-2', 'sourceOutput' => $toConnector, 'to' => 'node-connector'],
                ['from' => 'node-parser', 'id' => 'conn-3', 'sourceOutput' => $toMapper, 'to' => 'node-mapper'],
            ];

            return $data;
        };

        $yesNo = TopologySchemaUtils::getSchemaObjectFromJson($labelled('yes', 'no'), $sdkUrlMap);
        $noYes = TopologySchemaUtils::getSchemaObjectFromJson($labelled('no', 'yes'), $sdkUrlMap);

        self::assertNotSame(TopologySchemaUtils::getIndexHash($yesNo), TopologySchemaUtils::getIndexHash($noYes));
        self::assertNotSame(
            TopologySchemaUtils::getSchemaFullIndexHash($yesNo),
            TopologySchemaUtils::getSchemaFullIndexHash($noYes),
        );

        $unlabelled = TopologySchemaUtils::getSchemaObjectFromJson($plain, $sdkUrlMap);
        self::assertSame(
            hash('sha256', Json::encode($unlabelled->buildIndex())),
            TopologySchemaUtils::getIndexHash($unlabelled),
        );
        self::assertCount(3, $unlabelled->buildIndex()[0]);
    }

    /**
     * @throws Exception
     */
    public function testDefaultOutputKeepsTheHashes(): void
    {
        $sdkUrlMap = ['php-sdk' => 'php-sdk:8080'];
        $plain     = $this->loadJson('schema-json-editor.json');
        $saved     = $plain;

        $saved['connections'] = array_map(
            static fn(array $connection): array => [...$connection, 'sourceOutput' => 'output'],
            $plain['connections'],
        );

        $before = TopologySchemaUtils::getSchemaObjectFromJson($plain, $sdkUrlMap);
        $after  = TopologySchemaUtils::getSchemaObjectFromJson($saved, $sdkUrlMap);

        self::assertSame(TopologySchemaUtils::getIndexHash($before), TopologySchemaUtils::getIndexHash($after));
        self::assertSame(
            TopologySchemaUtils::getSchemaFullIndexHash($before),
            TopologySchemaUtils::getSchemaFullIndexHash($after),
        );
    }

    /**
     * @throws Exception
     */
    public function testNumericNodeIdsAreHashed(): void
    {
        $sdkUrlMap = ['php-sdk' => 'php-sdk:8080'];
        $numeric   = static function (string $toConnector, string $toMapper): array {
            $data                = Json::decode(
                strtr(
                    File::getContent(sprintf('%s/data/schema-json-editor.json', __DIR__)),
                    [
                        '"node-connector"' => '"12"',
                        '"node-cron"'      => '"10"',
                        '"node-mapper"'    => '"13"',
                        '"node-parser"'    => '"11"',
                        '"node-splitter"'  => '"14"',
                        '"node-start"'     => '"15"',
                        '"node-webhook"'   => '"16"',
                    ],
                ),
            );
            $data['connections'] = [
                ['from' => '10', 'id' => 'conn-1', 'to' => '11'],
                ['from' => '11', 'id' => 'conn-2', 'sourceOutput' => $toConnector, 'to' => '12'],
                ['from' => '11', 'id' => 'conn-3', 'sourceOutput' => $toMapper, 'to' => '13'],
            ];

            return $data;
        };

        $yesNo = TopologySchemaUtils::getSchemaObjectFromJson($numeric('yes', 'no'), $sdkUrlMap);
        $noYes = TopologySchemaUtils::getSchemaObjectFromJson($numeric('no', 'yes'), $sdkUrlMap);

        self::assertNotSame(TopologySchemaUtils::getIndexHash($yesNo), TopologySchemaUtils::getIndexHash($noYes));
        self::assertNotSame(
            TopologySchemaUtils::getSchemaFullIndexHash($yesNo),
            TopologySchemaUtils::getSchemaFullIndexHash($noYes),
        );
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
     * @param string $name
     *
     * @return mixed[]
     *
     * @throws Exception
     */
    private function loadJson(string $name): array
    {
        return Json::decode(File::getContent(sprintf('%s/data/%s', __DIR__, $name)));
    }

    /**
     * @return XmlDecoder
     */
    private function getXmlDecoder(): XmlDecoder
    {
        return self::getContainer()->get('rest.decoder.xml');
    }

}
