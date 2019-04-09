<?php declare(strict_types=1);

namespace Tests\Unit\Utils;

use Hanaboso\PipesFramework\Utils\StringUtil;
use PHPUnit\Framework\TestCase;

/**
 * Class StringUtilTest
 *
 * @package Tests\Unit\Utils
 */
final class StringUtilTest extends TestCase
{

    /**
     * @covers       StringUtil::toCamelCase()
     * @dataProvider toCamelCaseDataProvider
     *
     * @param string $string
     * @param string $assert
     * @param bool   $firstUpper
     */
    public function testToCamelCase(string $string, string $assert, bool $firstUpper): void
    {
        $camelCase = StringUtil::toCamelCase($string, $firstUpper);
        self::assertSame($assert, $camelCase);
    }

    /**
     * @return array
     */
    public function toCamelCaseDataProvider(): array
    {
        return [
            [
                'some_group',
                'SomeGroup',
                FALSE,
            ],
            [
                'some_group',
                'someGroup',
                TRUE,
            ],
            [
                'some_group_some_group',
                'someGroupSomeGroup',
                TRUE,
            ],
        ];
    }

    /**
     * @covers StringUtil::getShortClassName()
     */
    public function testGetShortClassName(): void
    {
        self::assertSame('StringUtilTest', StringUtil::getShortClassName($this));
    }

}
