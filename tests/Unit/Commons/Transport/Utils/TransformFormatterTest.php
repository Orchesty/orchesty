<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/24/17
 * Time: 10:43 AM
 */

namespace Tests\Unit\Commons\Transport\Utils;

use Hanaboso\PipesFramework\Commons\Transport\Utils\TransportFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Class TransformFormatterTest
 *
 * @package Tests\Unit\Commons\Transport\Utils
 */
class TransformFormatterTest extends TestCase
{

    /**
     * @covers TransportFormatter::headersToString()
     */
    public function testHeadersToString(): void
    {
        $this->assertSame(
            'content-type=[application/json, application/js], pf_token=123',
            TransportFormatter::headersToString([
                'content-type' => ['application/json', 'application/js'], 'pf_token' => '123',
            ])
        );
    }

    /**
     * @covers TransportFormatter::requestToString()
     */
    public function testRequestToString(): void
    {
        $this->assertSame(
            'Request: Method: GET, Uri: http://localhost, Headers: content-type=application/json, Body: "{"data":[]}"',
            TransportFormatter::requestToString(
                'get', 'http://localhost', ['content-type' => 'application/json'], '{"data":[]}'
            )
        );
    }

    /**
     * @covers TransportFormatter::responseToString()
     */
    public function testResponseToString(): void
    {
        $this->assertSame(
            'Response: Status Code: 400, Reason Phrase: Bad Request, Headers: content-type=application/json, Body: "{"data":[]}"',
            TransportFormatter::responseToString(
                400, 'Bad Request', ['content-type' => 'application/json'], '{"data":[]}'
            )
        );
    }

}