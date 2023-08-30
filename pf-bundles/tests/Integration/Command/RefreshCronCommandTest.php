<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Command;

use Hanaboso\CommonsBundle\Exception\CronException;
use Hanaboso\PipesFramework\Configurator\Cron\CronManager;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\RefreshCronCommand;
use PipesFrameworkTests\DatabaseTestCaseAbstract;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class RefreshCronCommandTest
 *
 * @package PipesFrameworkTests\Integration\Command
 */
final class RefreshCronCommandTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\RefreshCronCommand
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\RefreshCronCommand::execute
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\RefreshCronCommand::configure
     */
    public function testExecute(): void
    {
        $application = new Application(self::$kernel);
        $command     = $application->get('cron:refresh');

        $commandTester = new CommandTester($command);
        $result        = $commandTester->execute([]);

        self::assertEquals(0, $result);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\RefreshCronCommand
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\RefreshCronCommand::execute
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\RefreshCronCommand::configure
     */
    public function testExecuteErr(): void
    {
        $manager = self::createPartialMock(CronManager::class, ['batchCreate']);
        $manager->expects(self::any())->method('batchCreate')->willThrowException(new CronException());
        $command = new RefreshCronCommand($this->dm, $manager);

        $commandTester = new CommandTester($command);
        $result        = $commandTester->execute([]);

        self::assertEquals(1, $result);
    }

}
