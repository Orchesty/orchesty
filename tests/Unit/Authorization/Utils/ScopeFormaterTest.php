<?php declare(strict_types=1);

namespace Tests\Unit\Authorization\Utils;

use Hanaboso\PipesFramework\Authorization\Utils\ScopeFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Class ScopeFormaterTest
 *
 * @package Tests\Unit\Authorization\Utils
 */
final class ScopeFormaterTest extends TestCase
{

    /**
     * @dataProvider getScopeProvider
     *
     * @param array  $scopes
     * @param string $result
     */
    public function testGetScopes(array $scopes, string $result): void
    {
        $scopes = ScopeFormatter::getScopes($scopes);

        self::assertEquals($result, $scopes);
    }

    /**
     * @return array
     */
    public function getScopeProvider(): array
    {
        return [
            [[], ''],
            [['user', 'article'], '&scope=user,article'],
        ];
    }

}
