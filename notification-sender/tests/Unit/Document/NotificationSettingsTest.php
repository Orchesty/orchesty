<?php declare(strict_types=1);

namespace NotificationSenderTests\Unit\Document;

use Hanaboso\CommonsBundle\Enum\NotificationEventEnum;
use Hanaboso\NotificationSender\Document\NotificationSettings;
use NotificationSenderTests\KernelTestCaseAbstract;

/**
 * Class NotificationSettingsTest
 *
 * @package NotificationSenderTests\Unit\Document
 *
 * @covers  \Hanaboso\NotificationSender\Document\NotificationSettings
 */
final class NotificationSettingsTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testDocument(): void
    {
        $settings = (new NotificationSettings())
            ->setClass('Class')
            ->setEvents([NotificationEventEnum::ACCESS_EXPIRATION])
            ->setSettings([]);

        self::assertEquals('Class', $settings->getClass());
        self::assertNotEmpty($settings->getEvents());
        self::assertEmpty($settings->getSettings());

        $settings = $settings->toArray('Type', 'Name');

        self::assertEquals(
            [
                NotificationSettings::ID         => NULL,
                NotificationSettings::CREATED    => $settings[NotificationSettings::CREATED],
                NotificationSettings::UPDATED    => $settings[NotificationSettings::UPDATED],
                NotificationSettings::NAME       => 'Name',
                NotificationSettings::TYPE       => 'Type',
                NotificationSettings::CLASS_NAME => 'Class',
                NotificationSettings::EVENTS     => [NotificationEventEnum::ACCESS_EXPIRATION],
                NotificationSettings::SETTINGS   => [],
            ],
            $settings
        );
    }

}
