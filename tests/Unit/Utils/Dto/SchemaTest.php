<?php declare(strict_types=1);

namespace Tests\Unit\Utils\Dto;

use FOS\RestBundle\Decoder\XmlDecoder;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Utils\Dto\Schema;
use Hanaboso\PipesFramework\Utils\TopologySchemaUtils;
use Nette\Utils\Json;
use PHPUnit\Framework\TestCase;

/**
 * Class SchemaTest
 *
 * @package Tests\Unit\Utils\Dto
 */
final class SchemaTest extends TestCase
{

    /**
     * @var XmlDecoder
     */
    private $xmlDecoder;

    /**
     * @covers Schema::buildIndex()
     */
    public function testBuildIndex(): void
    {
        $content = $this->load('default.tplg');
        $schema  = TopologySchemaUtils::getSchemaObject($this->getXmlDecoder()->decode($content));

        $this->assertInstanceOf(Schema::class, $schema);
        $this->assertEquals($this->getExpected(), TopologySchemaUtils::getIndexHash($schema));
    }

    /**
     *
     */
    public function testBuildIndexMissingStartNode(): void
    {
        $content = $this->load('missing-start-node.tplg');
        $schema  = TopologySchemaUtils::getSchemaObject($this->getXmlDecoder()->decode($content));

        $this->expectException(TopologyException::class);
        $this->expectExceptionCode(TopologyException::SCHEMA_START_NODE_MISSING);

        $schema->buildIndex();
    }

    /**
     *
     */
    public function testBuildIndexInfiniteLoop(): void
    {
        $content = $this->load('infinite-loop.tplg');
        $schema  = TopologySchemaUtils::getSchemaObject($this->getXmlDecoder()->decode($content));

        $this->expectException(TopologyException::class);
        $this->expectExceptionCode(TopologyException::SCHEMA_INFINITE_LOOP);

        $schema->buildIndex();
    }

    /**
     * @return string
     */
    private function getExpected(): string
    {
        return md5(Json::encode([
            0 => "cleverconnectors-create-subscriptions-connector:connector",
            1 => "cleverconnectors-delete-subscriptions-connector:connector",
            2 => "cleverconnectors-update-subscriptions-connector:connector",
            3 => "hubspot-created-contact-mapper:custom",
            4 => "hubspot-deleted-contact-mapper:custom",
            5 => "hubspot-get-contact-connector:connector",
            6 => "hubspot-updated-contact-connector:webhook",
            7 => "hubspot-updated-contact-mapper:custom",
            8 => "universal-splitter:splitter",
        ]));
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