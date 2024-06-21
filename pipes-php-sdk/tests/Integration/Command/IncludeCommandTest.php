<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Command;

use Hanaboso\PipesPhpSdk\Command\IncludeCommands;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\KernelTestCaseAbstract;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class IncludeCommandTest
 *
 * @package PipesPhpSdkTests\Integration\Command
 */
#[CoversClass(IncludeCommands::class)]
final class IncludeCommandTest extends KernelTestCaseAbstract
{

    /**
     * @return void
     */
    public function testIncludeCommands(): void
    {
        /** @var KernelInterface $kernel */
        $kernel   = self::$kernel;
        $commands = new IncludeCommands($kernel);
        $commands->addIncludedCommand('test:command');
        $commands->add(new Command('test'));

        $commands = $commands->getIncludedCommands();
        self::assertEquals(
            [
                'authorization:install',
                'cron:refresh',
                'rabbit_mq:publisher:pipes-user-task',
                'service:install',
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
