<?php declare(strict_types=1);

namespace DemoTests\Integration\Command;

use DemoTests\KernelTestCaseAbstract;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class KapacitorCommandTest
 *
 * @package DemoTests\Integration\Command
 */
final class KapacitorCommandTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Demo\Command\KapacitorCommand
     * @covers \Demo\Command\KapacitorCommand::configure
     * @covers \Demo\Command\KapacitorCommand::execute
     */
    public function testExecute(): void
    {
        $this->getFunctionMock('Demo\Command', 'usleep')->expects(self::any());

        $application = new Application(self::$kernel);
        $command     = $application->get('kapacitor:run');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ],
        );

        self::assertStringContainsString('Kapacitor start', $commandTester->getDisplay());
    }

}
