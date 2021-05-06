<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Command;

use Exception;
use PipesFrameworkTests\DatabaseTestCaseAbstract;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class InstallTopologyCommandTest
 *
 * @package PipesFrameworkTests\Integration\Command
 */
final class InstallTopologyCommandTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\InstallTopologyCommand
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\InstallTopologyCommand::configure
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\InstallTopologyCommand::execute
     */
    public function testExecute(): void
    {
        $application = new Application(self::$kernel);
        $command     = $application->get('topology:install');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '-c'      => NULL,
                '-u'      => NULL,
                '-d'      => NULL,
                '--force' => NULL,
            ],
        );

        self::assertStringContainsString('Topology name', $commandTester->getDisplay());
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\InstallTopologyCommand::insertRows
     *
     * @throws Exception
     */
    public function testInsertRows(): void
    {
        $table   = new Table(new ConsoleOutput());
        $command = self::$container->get('hbpf.command.topology_install');
        $this->invokeMethod($command, 'insertRows', [$table, ['foo1' => 'bar1'], 'create', TRUE]);
        $this->invokeMethod($command, 'insertRows', [$table, ['foo2' => 'bar2'], 'update', FALSE]);

        ob_start();
        $table->render();
        $content = ob_get_clean();

        self::assertStringContainsString('', (string) $content);
    }

}
