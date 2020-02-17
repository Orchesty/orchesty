<?php declare(strict_types=1);

namespace NotificationSenderTests\Unit\Document;

use Exception;
use Hanaboso\CommonsBundle\Enum\NotificationEventEnum;
use Hanaboso\NotificationSender\Document\NotificationSettings;
use Hanaboso\NotificationSender\Model\Notification\Dto\EmailDto;
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
     * @throws Exception
     */
    public function testDocument(): void
    {
        $settings = (new NotificationSettings())
            ->setClass('Class')
            ->setEvents([NotificationEventEnum::ACCESS_EXPIRATION])
            ->setSettings([EmailDto::EMAILS => ['one@example.com', 'two@example.com']]);

        self::assertEquals('Class', $settings->getClass());
        self::assertNotEmpty($settings->getEvents());
        self::assertNotEmpty($settings->getSettings());

        $settings->preFlush();
        $settings->postLoad();

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
                NotificationSettings::SETTINGS   => [EmailDto::EMAILS => ['one@example.com', 'two@example.com']],
            ],
            $settings
        );
    }

}
