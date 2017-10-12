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
     * @covers PipesHeaders::createPermanentKey()
     */
    public function testCreatePermanentKey(): void
    {
        $this->assertSame('pfp_node_id', PipesHeaders::createPermanentKey('node_id'));
    }

    /**
     * @covers PipesHeaders::createKey()
     */
    public function testKey(): void
    {
        $this->assertSame('pf_node_id', PipesHeaders::createKey('node_id'));
    }

    /**
     * @covers PipesHeaders::getPermanentHeaders()
     */
    public function testGetPermanentHeaders(): void
    {
        $this->assertSame(['pfp_node_id' => '123'], PipesHeaders::getPermanentHeaders([
            'content-type' => 'application/json', 'pfp_node_id' => '123', 'pf_token' => '456',
        ]));
    }

    /**
     * @covers PipesHeaders::getPermanentHeader()
     */
    public function testGetPermanentHeader(): void
    {
        $this->assertSame('123', PipesHeaders::getPermanentHeader('node_id', [
            'content-type' => 'application/json', 'pfp_node_id' => '123', 'pf_token' => '456',
        ]));
    }

    /**
     * @covers PipesHeaders::getHeaders()
     */
    public function testGetHeaders(): void
    {
        $this->assertSame(['pf_token' => '456'], PipesHeaders::getHeaders([
            'content-type' => 'application/json', 'pfp_node_id' => '123', 'pf_token' => '456',
        ]));
    }

    /**
     * @covers PipesHeaders::getHeader()
     */
    public function testGetHeader(): void
    {
        $this->assertSame('456', PipesHeaders::getHeader('token', [
            'content-type' => 'application/json', 'pfp_node_id' => '123', 'pf_token' => '456',
        ]));
    }

}