<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Utils\Dto;

use Exception;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Utils\TopologySchemaUtils;
use Hanaboso\RestBundle\Model\Decoder\XmlDecoder;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class SchemaTest
 *
 * @package PipesFrameworkTests\Unit\Utils\Dto
 */
final class SchemaTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::buildIndex
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::checkStartNode
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::getIndexItem
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::isInfinity
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::addNode
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::addSequence
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::addStartNode
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::getNodes
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::getNextIds
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::addToTree
     *
     * @throws Exception
     */
    public function testBuildIndex(): void
    {
        $content = $this->load('default.tplg');
        $schema  = TopologySchemaUtils::getSchemaObject($this->getXmlDecoder()->decode($content));

        self::assertEquals($this->getExpected(), TopologySchemaUtils::getIndexHash($schema));
    }

    /**
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::buildIndex
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::checkStartNode
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::getIndexItem
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::isInfinity
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::getNextIds
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::addToTree
     *
     * @throws Exception
     */
    public function testBuildIndexNewHash(): void
    {
        $content = $this->load('change-new-hash.tplg');
        $schema  = TopologySchemaUtils::getSchemaObject($this->getXmlDecoder()->decode($content));

        self::assertNotEquals($this->getExpected(), TopologySchemaUtils::getIndexHash($schema));
    }

    /**
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::buildIndex
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::checkStartNode
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::getIndexItem
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::isInfinity
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::getNextIds
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::addToTree
     *
     * @throws Exception
     */
    public function testBuildIndexMissingStartNode(): void
    {
        $content = $this->load('missing-start-node.tplg');
        $schema  = TopologySchemaUtils::getSchemaObject($this->getXmlDecoder()->decode($content));

        self::expectExceptionCode(TopologyException::SCHEMA_START_NODE_MISSING);

        $schema->buildIndex();
    }

    /**
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::buildIndex
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::checkStartNode
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::getIndexItem
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::isInfinity
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::getNextIds
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::addToTree
     *
     * @throws Exception
     */
    public function testFalsePositiveInfinity(): void
    {
        $content = $this->load('false-positive-infinity.tplg');
        $schema  = TopologySchemaUtils::getSchemaObject($this->getXmlDecoder()->decode($content));
        $res     = $schema->buildIndex();

        self::assertCount(2, $res);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::buildIndex
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::checkStartNode
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::getIndexItem
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::isInfinity
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::getNextIds
     *
     * @throws Exception
     */
    public function testBuildIndexInfiniteLoop(): void
    {
        $content = $this->load('infinite-loop.tplg');
        $schema  = TopologySchemaUtils::getSchemaObject($this->getXmlDecoder()->decode($content));

        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::SCHEMA_INFINITE_LOOP);

        $schema->buildIndex();
    }

    /**
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::buildIndex
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::getStartNode
     *
     * @throws Exception
     */
    public function testBuildIndexWithoutNodes(): void
    {
        $content = $this->load('no-nodes.tplg');
        $schema  = TopologySchemaUtils::getSchemaObject($this->getXmlDecoder()->decode($content));

        self::assertEmpty($schema->buildIndex());
        self::assertEmpty($schema->getStartNode());
    }

    /**
     * @return string
     * @throws Exception
     */
    private function getExpected(): string
    {
        return hash(
            'sha256',
            Json::encode(
                [
                    [
                        0 => 'Event_1lqi8dm:hubspot-updated-contact-connector:webhook',
                        1 => 'Task_00wzy7d:hanaboso-create-subscriptions-connector:connector',
                        2 => 'Task_0fzjb0y:hanaboso-delete-subscriptions-connector:connector',
                        3 => 'Task_0h8gpta:hanaboso-update-subscriptions-connector:connector',
                        4 => 'Task_0nwvqkt:hubspot-created-contact-mapper:custom',
                        5 => 'Task_152x7cw:hubspot-deleted-contact-mapper:custom',
                        6 => 'Task_1niijps:hubspot-get-contact-connector:connector',
                        7 => 'Task_1taayin:universal-splitter:splitter',
                        8 => 'Task_1wcc82o:hubspot-updated-contact-mapper:custom',
                    ],
                ],
            ),
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
     * @return XmlDecoder
     */
    private function getXmlDecoder(): XmlDecoder
    {
        return self::getContainer()->get('rest.decoder.xml');
    }

}
