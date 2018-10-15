<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: stano
 * Date: 9.10.17
 * Time: 16:21
 */

namespace Tests\Unit\AppBundle\Command;

use CleverConnectors\AppBundle\Command\GetInstallsBeforeExpirationCommand;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class GetInstallsBeforeExpirationCommandTest
 *
 * @package Tests\Unit\AppBundle\Command
 */
final class GetInstallsBeforeExpirationCommandTest extends TestCase
{

    /**
     *
     */
    public function testGetInstallsBeforeExpiration(): void
    {
        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->method('findBeforeExpiration')->willReturn([new SystemInstall()]);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);

        $cmd = new GetInstallsBeforeExpirationCommand($dm, 3600);

        $tester = new CommandTester($cmd);
        $tester->execute([]);

        $output = $tester->getDisplay();
        $this->assertSame(json_encode([new SystemInstall()]), trim($output));
    }

}