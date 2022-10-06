<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Command;

use PipesFrameworkTests\DatabaseTestCaseAbstract;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class InstallServiceCommandTest
 *
 * @package PipesFrameworkTests\Integration\Command
 */
final class InstallServiceCommandTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\InstallServiceCommand
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\InstallServiceCommand::configure
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\InstallServiceCommand::execute
     */
    public function testExecute(): void
    {
        $application = new Application(self::$kernel);
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
