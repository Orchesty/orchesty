<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: pavel.severyn
 * Date: 13.12.17
 * Time: 11:19
 */

namespace Tests\Unit\Configurator\TopologyControlling\Messages;

use Hanaboso\PipesFramework\Configurator\TopologyControlling\Messages\TopologyMessage;
use PHPUnit\Framework\TestCase;

/**
 * Class TopologyMessageTest
 *
 * @package Tests\Unit\Configurator\TopologyControlling\Messages
 */
class TopologyMessageTest extends TestCase
{

    /**
     * @covers       TopologyMessage::__construct
     * @covers       TopologyMessage::getMessage()
     * @dataProvider getMessage

     * @param string $topologyId
     * @param string $action
     * @param array  $result
     */
    public function testMessage(string $topologyId, string $action, array $result): void
    {
        $message = new TopologyMessage($topologyId, $action);

        $this->assertEquals($result, $message->getMessage());
        $this->assertEquals($topologyId, $message->getTopologyId());
        $this->assertEquals($action, $message->getAction());

    }

    /**
     * @return array
     */
    public function getMessage(): array
    {
        return [
            ['123456', TopologyMessage::STOP, ['action' => 'stop', 'topologyId' => '123456']],
            ['123456', TopologyMessage::DELETE, ['action' => 'delete', 'topologyId' => '123456']],
            ['123456', 'akce', ['action' => 'akce', 'topologyId' => '123456']],
        ];
    }

}
