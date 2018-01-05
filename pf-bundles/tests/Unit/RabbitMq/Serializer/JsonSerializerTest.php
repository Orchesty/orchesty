<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 23.8.17
 * Time: 14:07
 */

namespace Tests\Unit\RabbitMq\Serializer;

use Hanaboso\PipesFramework\RabbitMq\Serializers\IMessageSerializer;
use Hanaboso\PipesFramework\RabbitMq\Serializers\JsonSerializer;
use PHPUnit\Framework\TestCase;

/**
 * Class JsonSerializerTest
 *
 * @package Tests\Unit\RabbitMq\Serializer
 */
class JsonSerializerTest extends TestCase
{

    /**
     * @var IMessageSerializer
     */
    protected $serializer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = new JsonSerializer();
    }

    /**
     * @return void
     */
    public function testGetInstance(): void
    {
        $this->assertInstanceOf(JsonSerializer::class, $this->serializer->getInstance());
    }

    /**
     * @dataProvider toJsonProvider
     *
     * @param array  $src
     * @param string $result
     *
     * @return void
     */
    public function testToJson(array $src, string $result): void
    {
        $serialized = $this->serializer->toJson($src);
        $this->assertEquals($result, $serialized);
    }

    /**
     * @dataProvider fromJsonProvider
     *
     * @param string $src
     * @param array  $result
     *
     * @return void
     */
    public function testFromJson(string $src, array $result): void
    {
        $serialized = $this->serializer->fromJson($src);
        $this->assertEquals($result, $serialized);
    }

    /**
     * @return array
     */
    public function toJsonProvider(): array
    {
        return [
            [[1, 2, 3], '[1,2,3]'],
            [['root' => ['body' => 1]], '{"root":{"body":1}}'],
        ];
    }

    /**
     * @return array
     */
    public function fromJsonProvider(): array
    {
        return [
            ['[1,2,3]', [1, 2, 3]],
            ['{"root":{"body":1}}', ['root' => ['body' => 1]]],
        ];
    }

}
