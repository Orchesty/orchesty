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
                0  => 'user:create',
                1  => 'user:delete',
                2  => 'user:list',
                3  => 'user:password:change',
                4  => 'rabbit_mq:async-consumer',
                5  => 'rabbit_mq:consumer',
                6  => 'rabbit_mq:setup',
                7  => 'rabbit_mq:publisher:pipes.messages',
                8  => 'authorization:install',
                9  => 'cron:refresh',
                10 => 'test:command',
            ],
            $commands,
        );
    }

}
