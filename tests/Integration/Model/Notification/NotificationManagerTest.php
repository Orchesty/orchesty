<?php declare(strict_types=1);

namespace Tests\Integration\Model\Notification;

use Exception;
use Hanaboso\CommonsBundle\Enum\NotificationEventEnum;
use Hanaboso\NotificationSender\Document\NotificationSettings;
use Hanaboso\NotificationSender\Model\Notification\Dto\CurlDto;
use Hanaboso\NotificationSender\Model\Notification\Dto\EmailDto;
use Hanaboso\NotificationSender\Model\Notification\Dto\RabbitDto;
use Hanaboso\NotificationSender\Model\Notification\NotificationManager;
use Tests\DatabaseTestCaseAbstract;
use Tests\Integration\Model\Notification\Handler\Impl\NullCurlHandler;
use Tests\Integration\Model\Notification\Handler\Impl\NullEmailHandler;
use Tests\Integration\Model\Notification\Handler\Impl\NullRabitHandler;

/**
 * Class NotificationManagerTest
 *
 * @package Tests\Integration\Model\Notification
 */
final class NotificationManagerTest extends DatabaseTestCaseAbstract
{

    private const EVENTS = [
        NotificationEventEnum::ACCESS_EXPIRATION,
        NotificationEventEnum::DATA_ERROR,
        NotificationEventEnum::SERVICE_UNAVAILABLE,
    ];

    /**
     * @var NotificationManager
     */
    private $manager;

    /**
     * @covers NotificationManager::send
     *
     * @throws Exception
     */
    public function testSend(): void
    {
        $this->dm->persist(
            (new NotificationSettings())
                ->setClass(NullCurlHandler::class)
                ->setEvents(self::EVENTS)
                ->setSettings([CurlDto::METHOD => 'POST', CurlDto::URL => 'https://example.com'])
        );

        $this->dm->persist(
            (new NotificationSettings())
                ->setClass(NullEmailHandler::class)
                ->setEvents(self::EVENTS)
                ->setSettings([EmailDto::EMAILS => ['one@example.com', 'two@example.com']])
        );

        $this->dm->persist(
            (new NotificationSettings())
                ->setClass(NullRabitHandler::class)
                ->setEvents(self::EVENTS)
                ->setSettings([RabbitDto::QUEUE => 'queue'])
        );

        $this->dm->flush();

        $this->manager->send(NotificationEventEnum::ACCESS_EXPIRATION, []);

        self::assertTrue(TRUE); // No exception were thrown...
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = self::$container->get('notification.manager');
    }

}
