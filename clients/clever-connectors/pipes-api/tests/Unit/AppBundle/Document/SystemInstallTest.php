<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 24.10.17
 * Time: 12:56
 */

namespace Tests\Unit\AppBundle\Document;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use DateTime;
use Hanaboso\CommonsBundle\Crypt\CryptManager;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Class SystemInstallTest
 *
 * @package Tests\Unit\AppBundle\Document
 */
final class SystemInstallTest extends TestCase
{

    /**
     *
     */
    public function testFrom(): void
    {
        $date     = new DateTime();
        $settings = ['pass' => 'pass'];

        $data = [
            '_id'                             => 123,
            SystemInstall::USER               => 'user-1',
            SystemInstall::TOKEN              => 'tok-1',
            SystemInstall::SYSTEM             => 'sys-1',
            SystemInstall::EXPIRES            => ['sec' => 1234567, 'usec' => 1234],
            SystemInstall::SYNCHRONIZED_TIME  => $date->format(DateTime::W3C),
            SystemInstall::ENCRYPTED_SETTINGS => CryptManager::encrypt($settings),
            SystemInstall::EVENT_CREATE       => TRUE,
            SystemInstall::EVENT_UNSUBSCRIBE  => FALSE,
            SystemInstall::EVENT_HARD_BOUNCE  => TRUE,
        ];

        $sysInstall = SystemInstall::from($data);

        self::assertEquals('user-1', $sysInstall->getUser());
        self::assertEquals('tok-1', $sysInstall->getToken());
        self::assertEquals('sys-1', $sysInstall->getSystem());
        self::assertEquals('1970-01-15T06:56:07+00:00', $sysInstall->getExpires()->format(DateTime::W3C));
        self::assertEquals(FALSE, $sysInstall->isSynchronized());
        self::assertEquals($date->format(DateTime::W3C), $sysInstall->getSynchronizedTime()->format(DateTime::W3C));
        self::assertNotEmpty($sysInstall->getCreated());
        self::assertEquals($settings, $sysInstall->getSettings());
        self::assertEquals(TRUE, $sysInstall->isEventCreate());
        self::assertEquals(FALSE, $sysInstall->isEventUnsubscribe());
        self::assertEquals(TRUE, $sysInstall->isEventHardBounce());
    }

    /**
     *
     */
    public function testIsEvent(): void
    {
        SystemInstall::checkEvent(SystemInstall::EVENT_HARD_BOUNCE);
        SystemInstall::checkEvent(SystemInstall::EVENT_CREATE);
        SystemInstall::checkEvent(SystemInstall::EVENT_UNSUBSCRIBE);

        $this->expectException(CleverConnectorsException::class);
        SystemInstall::checkEvent(SystemInstall::TOKEN);
    }

    /**
     *
     */
    public function testGetEventState(): void
    {
        $systemInstall = new SystemInstall();
        self::assertFalse($systemInstall->getEventState(SystemInstall::EVENT_CREATE));
        self::assertFalse($systemInstall->getEventState(SystemInstall::EVENT_UNSUBSCRIBE));
        self::assertFalse($systemInstall->getEventState(SystemInstall::EVENT_HARD_BOUNCE));

        $systemInstall
            ->setEventCreate(TRUE)
            ->setEventUnsubscribe(TRUE);

        self::assertTrue($systemInstall->getEventState(SystemInstall::EVENT_CREATE));
        self::assertTrue($systemInstall->getEventState(SystemInstall::EVENT_UNSUBSCRIBE));
        self::assertFalse($systemInstall->getEventState(SystemInstall::EVENT_HARD_BOUNCE));

        $this->expectException(InvalidArgumentException::class);
        $systemInstall->getEventState(SystemInstall::CREATED);
    }

    /**
     *
     */
    public function testSetEventState(): void
    {
        $systemInstall = new SystemInstall();
        $systemInstall
            ->setEventState(SystemInstall::EVENT_CREATE, TRUE)
            ->setEventState(SystemInstall::EVENT_UNSUBSCRIBE, TRUE);
        self::assertTrue($systemInstall->getEventState(SystemInstall::EVENT_CREATE));
        self::assertTrue($systemInstall->getEventState(SystemInstall::EVENT_UNSUBSCRIBE));
        self::assertFalse($systemInstall->getEventState(SystemInstall::EVENT_HARD_BOUNCE));

        $this->expectException(InvalidArgumentException::class);
        $systemInstall->setEventState(SystemInstall::CREATED, TRUE);
    }

}