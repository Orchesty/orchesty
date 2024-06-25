<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Command;

use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\InstallServiceCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class InstallServiceCommandTest
 *
 * @package PipesFrameworkTests\Integration\Command
 */
#[CoversClass(InstallServiceCommand::class)]
final class InstallServiceCommandTest extends DatabaseTestCaseAbstract
{

    /**
     * @return void
     */
    public function testExecute(): void
    {
        /** @var KernelInterface $kernel */
        $kernel      = self::$kernel;
        $application = new Application($kernel);
        $command     = $application->get('service:install');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                'name'    => '',
                'url'     => '',
            ],
        );

        self::assertStringContainsString('Done!', $commandTester->getDisplay());
    }

}
