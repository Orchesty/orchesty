<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Utils;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\NotificationTypeEnum;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class LoggerUtilsTest
 *
 * @package Tests\Unit\AppBundle\Utils
 */
final class LoggerUtilsTest extends KernelTestCaseAbstract
{

    use LoggerTrait;

    /**
     *
     */
    public function testGetMessage(): void
    {
        /** @var SystemInterface|MockObject $system */
        $system = $this->createMock(SystemInterface::class);
        $system->method('getKey')->willReturn('Key');
        $system->method('getName')->willReturn('Name');

        /** @var SystemInstall|MockObject $systemInstall */
        $systemInstall = $this->createMock(SystemInstall::class);
        $systemInstall->method('getUser')->willReturn('User');
        $systemInstall->method('getToken')->willReturn('Token');

        $this->assertEquals([
            'notification_type' => 'data_error',
            'guid'              => 'User',
            'token'             => 'Token',
            'system_key'        => 'Key',
            'system_name'       => 'Name',
        ], self::getMessage(NotificationTypeEnum::DATA_ERROR, $system, $systemInstall));
    }

}