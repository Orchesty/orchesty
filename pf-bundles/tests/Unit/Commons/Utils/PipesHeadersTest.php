<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/12/17
 * Time: 1:59 PM
 */

namespace Tests\Unit\Commons\Utils;

use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;
use PHPUnit\Framework\TestCase;

/**
 * Class PipesHeadersTest
 *
 * @package Tests\Unit\Commons\Utils
 */
class PipesHeadersTest extends TestCase
{

    /**
     * @covers PipesHeaders::createKey()
     */
    public function testCreateKey(): void
    {
        $this->assertSame('pf-node-id', PipesHeaders::createKey('node-id'));
    }

    /**
     * @covers PipesHeaders::clear()
     */
    public function testClear(): void
    {
        $this->assertSame(['content-type' => 'application/json', 'pf-token' => '456'], PipesHeaders::clear([
            'content-type' => 'application/json', 'pfp-node-id' => '123', 'pf-token' => '456',
        ]));
    }

    /**
     * @covers PipesHeaders::get()
     */
    public function testGet(): void
    {
        $this->assertSame('456', PipesHeaders::get('token', [
            'content-type' => 'application/json', 'pfp-node-id' => '123', 'pf-token' => '456',
        ]));
    }

    /**
     * @covers PipesHeaders::debugInfo()
     */
    public function testDebugInfo(): void
    {
        $this->assertSame([
            'node_id'        => '123',
            'correlation_id' => '456',
        ], PipesHeaders::debugInfo([
            'content-type'      => 'application/json',
            'pf-node-id'        => '123',
            'pf-token'          => '456',
            'pf-correlation-id' => '456',
        ]));
    }

}