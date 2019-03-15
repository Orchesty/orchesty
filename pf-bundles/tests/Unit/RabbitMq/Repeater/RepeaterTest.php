<?php declare(strict_types=1);

namespace Tests\Unit\RabbitMq\Repeater;

use Bunny\Message;
use Exception;
use Hanaboso\PipesFramework\RabbitMq\Impl\Repeater\Repeater;
use Hanaboso\PipesFramework\RabbitMq\Producer\AbstractProducer;
use PHPUnit\Framework\TestCase;

/**
 * Class RepeaterTest
 *
 * @package Tests\Unit\RabbitMq\Repeater
 */
final class RepeaterTest extends TestCase
{

    /**
     * @dataProvider getHopLimit
     * @covers       Repeater::getHopLimit()
     *
     * @param int $limit
     * @param int $result
     *
     * @return void
     */
    public function testGetHopLimit(int $limit, int $result): void
    {
        /** @var AbstractProducer $producer */
        $producer = $this->getMockBuilder(AbstractProducer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repeater = new Repeater($producer, $limit);
        $this->assertEquals($result, $repeater->getHopLimit());
    }

    /**
     * @dataProvider add
     * @covers       Repeater::add()
     *
     * @param array $header
     * @param int   $hopLimit
     * @param array $result
     * @param bool  $return
     *
     * @return void
     * @throws Exception
     */
    public function testAdd(array $header, int $hopLimit, array $result, bool $return): void
    {
        $message = new Message(
            'tag',
            'tag',
            FALSE,
            '',
            '',
            $header,
            'content'
        );

        $producer = $this->getMockBuilder(AbstractProducer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $producer->expects($this->any())
            ->method('publish')
            ->with($message->content, $message->routingKey, $result);

        /** @var AbstractProducer $producer */
        $repeater = new Repeater($producer, $hopLimit);
        $added    = $repeater->add($message);

        $this->assertEquals($return, $added);
    }

    /**
     * @dataProvider validRepeaterMessage
     * @covers       Repeater::validRepeaterMessage()
     *
     * @param array $header
     * @param bool  $result
     *
     * @return void
     */
    public function testValidRepeaterMessage(array $header, bool $result): void
    {
        $message = new Message(
            'tag',
            'tag',
            FALSE,
            '',
            '',
            $header,
            'content'
        );
        $this->assertEquals($result, Repeater::validRepeaterMessage($message));
    }

    /**
     * @return array
     */
    public function validRepeaterMessage(): array
    {
        return [
            [[], FALSE],
            [
                ['repeater_destination_exchange' => 'test', 'repeater_destination_rk' => 'test'],
                TRUE,
            ],
            [
                ['repeater_destination_exchange' => 'test'],
                FALSE,
            ],
        ];
    }

    /**
     * @return array
     */
    public function add(): array
    {
        return [
            [
                [],
                2,
                [
                    'max_hop'                       => 2,
                    'current_hop'                   => 1,
                    'repeater_destination_exchange' => '',
                    'repeater_destination_rk'       => '',
                ],
                TRUE,
            ],
            [
                ['max_hop' => 2, 'current_hop' => 2],
                2,
                [
                    'max_hop'                       => 2,
                    'current_hop'                   => 2,
                    'repeater_destination_exchange' => '',
                    'repeater_destination_rk'       => '',
                ],
                FALSE,
            ],
            [
                ['max_hop' => 3, 'current_hop' => 2],
                3,
                [
                    'max_hop'                       => 3,
                    'current_hop'                   => 3,
                    'repeater_destination_exchange' => '',
                    'repeater_destination_rk'       => '',
                ],
                TRUE,
            ],
            [
                ['max_hop' => 3, 'current_hop' => 1, 'job_id' => 123],
                3,
                [
                    'max_hop'                       => 3,
                    'current_hop'                   => 2,
                    'job_id'                        => 123,
                    'repeater_destination_exchange' => '',
                    'repeater_destination_rk'       => '',
                ],
                TRUE,
            ],
            [
                ['max_hop' => 3, 'job_id' => 123],
                3,
                [
                    'max_hop'                       => 3,
                    'current_hop'                   => 1,
                    'job_id'                        => 123,
                    'repeater_destination_exchange' => '',
                    'repeater_destination_rk'       => '',
                ],
                TRUE,
            ],
        ];
    }

    /**
     * @return array
     */
    public function getHopLimit(): array
    {
        return [
            [3, 3],
            [1, 1],
            [2, 2],
        ];
    }

}
