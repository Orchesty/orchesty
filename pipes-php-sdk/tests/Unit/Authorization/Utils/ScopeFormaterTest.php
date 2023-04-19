<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Authorization\Utils;

use Hanaboso\PipesPhpSdk\Authorization\Utils\ScopeFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Class ScopeFormaterTest
 *
 * @package PipesPhpSdkTests\Unit\Authorization\Utils
 */
final class ScopeFormaterTest extends TestCase
{

    /**
     * @dataProvider getScopeProvider
     *
     * @param string[] $scopes
     * @param string   $result
     */
    public function testGetScopes(array $scopes, string $result): void
    {
        $scopes = ScopeFormatter::getScopes($scopes);

        self::assertEquals($result, $scopes);
    }

    /**
     * @return mixed[]
     */
    public static function getScopeProvider(): array
    {
        return [
            [[], ''],
            [['user', 'article'], '&scope=user,article'],
        ];
    }

}
