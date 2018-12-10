<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 5.10.17
 * Time: 10:30
 */

namespace Tests\Unit\Utils;

use Hanaboso\PipesFramework\Utils\StringUtil;
use PHPUnit\Framework\TestCase;

/**
 * Class StringUtilTest
 *
 * @package Tests\Utils
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
        $this->assertSame($assert, $camelCase);
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
        $this->assertSame('StringUtilTest', StringUtil::getShortClassName($this));
    }

}