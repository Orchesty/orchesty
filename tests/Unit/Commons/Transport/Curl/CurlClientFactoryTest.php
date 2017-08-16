<?php declare(strict_types=1);

namespace Tests\Unit\Commons\Transport\Curl;

use GuzzleHttp\Client;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlClientFactory;
use PHPUnit\Framework\TestCase;

/**
 * Class CurlClientFactoryTest
 *
 * @package Tests\Unit\Commons\Transport\Curl
 */
final class CurlClientFactoryTest extends TestCase
{

    /**
     * @covers CurlClientFactory::create()
     */
    public function testCreate(): void
    {
        $curlClientFactory = new CurlClientFactory();
        $result = $curlClientFactory->create();

        $this->assertInstanceOf(Client::class, $result);
    }

}