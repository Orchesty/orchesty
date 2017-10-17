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
            [TypeEnum::XML_PARSER, 'xml-parser-api'],
            [TypeEnum::MAPPER, 'mapper-api'],
            [TypeEnum::CONNECTOR, 'connector-api'],
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
        $this->assertSame($result, $hostMapper->getRoute(new TypeEnum($type), '1'));
    }

    /**
     * @return array
     */
    public function getRouteProvider(): array
    {
        return [
            [TypeEnum::MAPPER, 'mapper/1'],
            [TypeEnum::EMAIL, 'mailer/1'],
            [TypeEnum::CONNECTOR, 'connector/1/action'],
            [TypeEnum::WEBHOOK, 'connector/1/webhook'],
        ];
    }

    /**
     * @covers HostMapper::getUrl()
     */
    public function testGetUrl(): void
    {
        $hostMapper = new HostMapper();
        $this->assertSame('http://mapper-api/mapper/1', $hostMapper->getUrl(new TypeEnum('mapper'), '1'));
    }

}
