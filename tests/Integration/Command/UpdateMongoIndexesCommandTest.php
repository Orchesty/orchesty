<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Command;

use PipesFrameworkTests\DatabaseTestCaseAbstract;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UpdateMongoIndexesCommandTest
 *
 * @package PipesFrameworkTests\Integration\Command
 */
final class UpdateMongoIndexesCommandTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\UpdateMongoIndexesCommand
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\UpdateMongoIndexesCommand::execute
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\UpdateMongoIndexesCommand::configure
     */
    public function testExecute(): void
    {
        $application = new Application(self::$kernel);
        $command     = $application->get('mongodb:index:update');

        $commandTester = new CommandTester($command);
        $result        = $commandTester->execute([]);

        self::assertEquals(0, $result);
    }

}
