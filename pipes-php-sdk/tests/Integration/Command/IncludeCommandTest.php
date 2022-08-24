<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Command;

use Hanaboso\PipesPhpSdk\Command\IncludeCommands;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;
use Symfony\Component\Console\Command\Command;

/**
 * Class IncludeCommandTest
 *
 * @package PipesPhpSdkTests\Integration\Command
 */
final class IncludeCommandTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Command\IncludeCommands::addIncludedCommand
     * @covers \Hanaboso\PipesPhpSdk\Command\IncludeCommands::getIncludedCommands
     * @covers \Hanaboso\PipesPhpSdk\Command\IncludeCommands::addIncludedCommand
     * @covers \Hanaboso\PipesPhpSdk\Command\IncludeCommands::add
     */
    public function testIncludeCommands(): void
    {
        $commands = new IncludeCommands(self::$kernel);
        $commands->addIncludedCommand('test:command');
        $commands->add(new Command('test'));

        $commands = $commands->getIncludedCommands();
        self::assertEquals(
            [
                0  => 'authorization:install',
                1  => 'cron:refresh',
                2  => 'rabbit_mq:consumer:status-service',
                3  => 'rabbit_mq:publisher:pipes-notifications',
                4  => 'rabbit_mq:publisher:pipes-user-task',
                5  => 'rabbit_mq:publisher:pipes.messages',
                6  => 'topology:install',
                7  => 'usage_stats:send-events',
                8  => 'user:create',
                9  => 'user:delete',
                10 => 'user:list',
                11 => 'user:password:change',
                12 => 'test:command',
            ],
            $commands,
        );
    }

}
