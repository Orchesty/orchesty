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
                'authorization:install',
                'cron:refresh',
                'rabbit_mq:publisher:pipes-user-task',
                'rabbit_mq:publisher:pipes.messages',
                'topology:install',
                'usage_stats:send-events',
                'user:create',
                'user:delete',
                'user:list',
                'user:password:change',
                'test:command',
            ],
            $commands,
        );
    }

}
