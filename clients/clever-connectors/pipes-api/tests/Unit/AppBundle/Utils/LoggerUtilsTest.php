<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Utils;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use CleverConnectors\AppBundle\Utils\LoggerUtils;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class LoggerUtilsTest
 *
 * @package Tests\Unit\AppBundle\Utils
 */
final class LoggerUtilsTest extends KernelTestCaseAbstract
{

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
            'guid'        => 'User',
            'token'       => 'Token',
            'system_key'  => 'Key',
            'system_name' => 'Name',
        ], LoggerUtils::getMessage($system, $systemInstall));
    }

}