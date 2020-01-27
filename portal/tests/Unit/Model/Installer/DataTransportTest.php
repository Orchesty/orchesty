<?php declare(strict_types=1);

namespace PortalTests\Unit\Model\Installer;

use Hanaboso\Portal\Model\Installer\DataTransport;
use Hanaboso\Portal\Model\Installer\Exception\InstallerException;
use Hanaboso\Portal\Model\Installer\Installer;
use PortalTests\KernelTestCaseAbstract;

/**
 * Class DataTransportTest
 *
 * @package PortalTests\Unit\Model\Installer
 */
final class DataTransportTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\Portal\Model\Installer\DataTransport
     */
    public function testConstructor(): void
    {
        self::expectException(InstallerException::class);
        self::expectExceptionMessage('Insert correct value to metric');
        new DataTransport(Installer::ELASTICSEARCH, 'something');
    }

}
