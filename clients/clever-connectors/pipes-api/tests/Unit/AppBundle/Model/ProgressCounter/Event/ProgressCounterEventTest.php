<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 3.10.17
 * Time: 19:35
 */

namespace Tests\Unit\AppBundle\Model\ProgressCounter\Event;

use CleverConnectors\AppBundle\Enum\ProgressCounterStatusEnum;
use CleverConnectors\AppBundle\Model\ProgressCounter\Event\ProgressCounterEvent;
use PHPUnit\Framework\TestCase;

/**
 * Class ProgressCounterEventTest
 *
 * @package Tests\Unit\Commons\ProgressCounter\Event
 */
class ProgressCounterEventTest extends TestCase
{

    /**
     * @covers ProgressCounterEvent::getProcessId()
     * @covers ProgressCounterEvent::getStatus()
     */
    public function testCreate(): void
    {
        $event = new ProgressCounterEvent(
            '1234ABCD',
            new ProgressCounterStatusEnum(ProgressCounterStatusEnum::SUCCESS)
        );

        $this->assertEquals(ProgressCounterStatusEnum::SUCCESS, $event->getStatus()->getValue());
        $this->assertEquals('1234ABCD', $event->getProcessId());
    }

}
