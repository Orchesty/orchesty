<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Command;

use Hanaboso\CommonsBundle\Exception\CronException;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
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
        self::getContainer()->set('hbpf.transport.curl_manager', self::createMock(CurlManagerInterface::class));

        self::assertEquals(0, (new CommandTester((new Application(self::$kernel))->get('cron:refresh')))->execute([]));
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\RefreshCronCommand
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\RefreshCronCommand::execute
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\RefreshCronCommand::configure
     */
    public function testExecuteError(): void
    {
        $manager = self::createPartialMock(CronManager::class, ['batchUpsert']);
        $manager->expects(self::any())->method('batchUpsert')->willThrowException(new CronException());

        self::assertEquals(1, (new CommandTester(new RefreshCronCommand($this->dm, $manager)))->execute([]));
    }

}
