<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 13.9.17
 * Time: 9:35
 */

namespace Tests\Unit\Commons\Monolog;

use Exception;
use Hanaboso\PipesFramework\Commons\Monolog\LogstashFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Class LogstashFormatterTest
 *
 * @package Tests\Unit\Commons\Monolog
 */
class LogstashFormatterTest extends TestCase
{

    /**
     * @var LogstashFormatter
     */
    private $logstashFormatter;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->logstashFormatter = new LogstashFormatter('test-service');
    }

    /**
     * @covers LogstashFormatter::format()
     */
    public function testFormat(): void
    {
        $message = $this->logstashFormatter->format([
            'message'    => 'Test message',
            'context'    => [],
            'level_name' => 'INFO',
            'channel'    => 'test',
        ]);

        $message = $this->correctMessage(json_decode($message, TRUE));

        $this->assertEquals([
            'timestamp' => 1505381163375,
            'hostname'  => 'localhost',
            'type'      => 'test-service',
            'message'   => 'Test message',
            'channel'   => 'test',
            'severity'  => 'INFO',
        ], $message);
    }

    /**
     * @covers LogstashFormatter::format()
     */
    public function testFormatPipes(): void
    {
        $message = $this->logstashFormatter->format([
            'message'    => 'Test message',
            'context'    => [
                'correlation_id' => '123',
                'node_id'        => '456',
            ],
            'level_name' => 'INFO',
            'channel'    => 'test',
        ]);

        $message = $this->correctMessage(json_decode($message, TRUE));

        $this->assertEquals([
            'timestamp'      => 1505381163375,
            'hostname'       => 'localhost',
            'type'           => 'test-service',
            'message'        => 'Test message',
            'channel'        => 'test',
            'severity'       => 'INFO',
            'correlation_id' => '123',
            'node_id'        => '456',
        ], $message);
    }

    /**
     * @covers LogstashFormatter::format()
     */
    public function testFormatException(): void
    {
        $message = $this->logstashFormatter->format([
            'message'    => 'Test message',
            'context'    => [
                'exception' => new Exception('Default exception'),
            ],
            'level_name' => 'INFO',
            'channel'    => 'test',
        ]);

        $message = $this->correctMessage(json_decode($message, TRUE));

        $this->assertEquals([
            'timestamp'  => 1505381163375,
            'hostname'   => 'localhost',
            'type'       => 'test-service',
            'message'    => 'Test message',
            'channel'    => 'test',
            'severity'   => 'INFO',
            'stacktrace' => [
                'class'   => 'Exception',
                'message' => 'Default exception',
                'code'    => 0,
                'file'    => '/srv/project/tests/Unit/Commons/Monolog/LogstashFormatterTest.php:99',
                'trace'   => '',
            ],
        ], $message);
    }

    /**
     * @covers LogstashFormatter::format()
     */
    public function testFormatExceptionPipes(): void
    {
        $message = $this->logstashFormatter->format([
            'message'    => 'Test message',
            'context'    => [
                'correlation_id' => '123',
                'node_id'        => '456',
                'exception'      => new Exception('Default exception'),
            ],
            'level_name' => 'INFO',
            'channel'    => 'test',
        ]);

        $message = $this->correctMessage(json_decode($message, TRUE));

        $this->assertEquals([
            'timestamp'      => 1505381163375,
            'hostname'       => 'localhost',
            'type'           => 'test-service',
            'message'        => 'Test message',
            'channel'        => 'test',
            'severity'       => 'INFO',
            'stacktrace'     => [
                'class'   => 'Exception',
                'message' => 'Default exception',
                'code'    => 0,
                'file'    => '/srv/project/tests/Unit/Commons/Monolog/LogstashFormatterTest.php:134',
                'trace'   => '',
            ],
            'correlation_id' => '123',
            'node_id'        => '456',
        ], $message);
    }

    /**
     * @param array $message
     *
     * @return array
     */
    private function correctMessage(array $message): array
    {
        $message['timestamp'] = 1505381163375;
        $message['hostname']  = 'localhost';

        if (isset($message['stacktrace']['trace'])) {
            $message['stacktrace']['trace'] = '';
        }

        return $message;
    }

    /**
     * @covers LogstashFormatter::format()
     */
    public function testContext(): void
    {
        $message = $this->logstashFormatter->format([
            'message'    => 'Test message',
            'context'    => [
                'type'          => 'starting_point',
                'topology_name' => 'topology_1',
            ],
            'level_name' => 'INFO',
            'channel'    => 'test',
        ]);

        $message = $this->correctMessage(json_decode($message, TRUE));

        $this->assertEquals([
            'timestamp'     => 1505381163375,
            'hostname'      => 'localhost',
            'type'          => 'starting_point',
            'message'       => 'Test message',
            'channel'       => 'test',
            'severity'      => 'INFO',
            'topology_name' => 'topology_1',
        ], $message);
    }

}