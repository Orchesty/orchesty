<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 8.9.17
 * Time: 9:40
 */

namespace Tests\Unit\TopologyGenerator;

use Hanaboso\PipesFramework\Commons\Enum\TypeEnum;
use Hanaboso\PipesFramework\TopologyGenerator\HostMapper;
use PHPUnit\Framework\TestCase;

/**
 * Class HostMapperTest
 *
 * @package Tests\Unit\TopologyGenerator
 */
class HostMapperTest extends TestCase
{

    /**
     * @covers       HostMapper::getHost()
     * @dataProvider getHostProvider
     *
     * @param string $type
     * @param string $result
     */
    public function testGetHost(string $type, string $result): void
    {
        $hostMapper = new HostMapper();
        $this->assertSame($result, $hostMapper->getHost(new TypeEnum($type)));
    }

    /**
     * @return array
     */
    public function getHostProvider(): array
    {
        return [
            [TypeEnum::XML_PARSER, 'xml-parser'],
            [TypeEnum::MAPPER, 'pipes-api'],
            [TypeEnum::CONNECTOR, 'pipes-api'],
        ];
    }

    /**
     * @covers       HostMapper::getRoute()
     * @dataProvider getRouteProvider
     *
     * @param string $type
     * @param string $result
     */
    public function testGetRoute(string $type, string $result): void
    {
        $hostMapper = new HostMapper();
        $this->assertSame($result, $hostMapper->getRoute(new TypeEnum($type)));
    }

    /**
     * @return array
     */
    public function getRouteProvider(): array
    {
        return [
            [TypeEnum::XML_PARSER, 'api/parser'],
            [TypeEnum::MAPPER, 'api/mapper'],
            [TypeEnum::CONNECTOR, 'api/connector'],
        ];
    }

    /**
     * @covers HostMapper::getUrl()
     */
    public function testGetUrl(): void
    {
        $hostMapper = new HostMapper();
        $this->assertSame('pipes-api/api/mapper', $hostMapper->getUrl(new TypeEnum('mapper')));
    }

}