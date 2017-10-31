<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CMEvents;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventObject;
use CleverConnectors\AppBundle\Model\CMEvents\Traits\CMEventSystemTrait;
use Tests\KernelTestCaseAbstract;

/**
 * Class CMEventTraitTest
 *
 * @package Tests\Unit\AppBundle\Model\CMEvents
 */
class CMEventTraitTest extends KernelTestCaseAbstract
{

    use CMEventSystemTrait;

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->addCMEvent(new CMEventObject('', SystemInstall::EVENT_CREATE, ''));
        $this->addCMEvent(new CMEventObject('field', SystemInstall::EVENT_UNSUBSCRIBE, 'url'));
    }

    /**
     *
     */
    public function testIsEventAllowed(): void
    {
        self::assertTrue($this->isEventAllowed(SystemInstall::EVENT_CREATE));
        self::assertFalse($this->isEventAllowed('someEventTets'));
    }

    /**
     *
     */
    public function testIsEventProcessAllowed(): void
    {
        self::assertTrue($this->isEventProcessAllowed(SystemInstall::EVENT_UNSUBSCRIBE));
        self::assertFalse($this->isEventProcessAllowed(SystemInstall::EVENT_CREATE));
        self::assertFalse($this->isEventProcessAllowed('someEventTets'));
    }

    /**
     *
     */
    public function testGetEventObject(): void
    {
        self::assertEquals('field', $this->getEventObject(SystemInstall::EVENT_UNSUBSCRIBE)->getField());
    }

}