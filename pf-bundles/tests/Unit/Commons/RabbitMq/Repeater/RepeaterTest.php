<?php
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 30.8.17
 * Time: 18:13
 */

namespace Tests\Unit\Commons\RabbitMq\Repeater;

use Bunny\Message;
use Hanaboso\PipesFramework\Commons\RabbitMq\Repeater\Repeater;
use Hanaboso\PipesFramework\RabbitMqBundle\Producer\AbstractProducer;
use PHPUnit\Framework\TestCase;

/**
 * Class RepeaterTest
 *
 * @package Tests\Unit\Commons\RabbitMq\Repeater
 */
class RepeaterTest extends TestCase
{

    /**
     * @dataProvider getHopLimit
     * @covers       Repeater::getHopLimit()
     *
     * @param int $limit
     * @param int $result
     */
    public function testGetHopLimit(int $limit, int $result)
    {
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
     * @param bool      $return
     */
    public function testAdd(array $header, int $hopLimit, array $result, bool $return)
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

        $producer
            ->expects($this->any())
            ->method('publish')
            ->with($message->content, $message->routingKey, $result);

        $repeater = new Repeater($producer, $hopLimit);
        $added    = $repeater->add($message);

        $this->assertEquals($return, $added);

    }

    /**
     * @return array
     */
    public function add()
    {
        return [
            [
                [],
                2,
                ['max_hop' => 2, 'current_hop' => 1],
                TRUE,
            ],
            [
                ['max_hop' => 2, 'current_hop' => 2],
                2,
                ['max_hop' => 2, 'current_hop' => 2],
                FALSE,
            ],
            [
                ['max_hop' => 3, 'current_hop' => 2],
                3,
                ['max_hop' => 3, 'current_hop' => 3],
                TRUE,
            ],
            [
                ['max_hop' => 3, 'current_hop' => 1, 'job_id' => 123],
                3,
                ['max_hop' => 3, 'current_hop' => 2, 'job_id' => 123],
                TRUE,
            ],
            [
                ['max_hop' => 3, 'job_id' => 123],
                3,
                ['max_hop' => 3, 'current_hop' => 1, 'job_id' => 123],
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
