<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Command;

use Hanaboso\CommonsBundle\Exception\CronException;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Configurator\Cron\CronManager;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\RefreshCronCommand;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class RefreshCronCommandTest
 *
 * @package PipesFrameworkTests\Integration\Command
 */
#[CoversClass(RefreshCronCommand::class)]
#[AllowMockObjectsWithoutExpectations]
final class RefreshCronCommandTest extends DatabaseTestCaseAbstract
{

    /**
     * @return void
     */
    public function testExecute(): void
    {
        self::getContainer()->set('hbpf.transport.curl_manager', self::createMock(CurlManagerInterface::class));

        /** @var KernelInterface $kernel */
        $kernel      = self::$kernel;
        $application = new Application($kernel);
        self::assertSame(0, (new CommandTester($application->get('cron:refresh')))->execute([]));
    }

    /**
     * @return void
     */
    public function testExecuteError(): void
    {
        $manager = self::createPartialMock(CronManager::class, ['batchUpsert']);
        $manager->expects(self::any())->method('batchUpsert')->willThrowException(new CronException());

        self::assertSame(1, (new CommandTester(new RefreshCronCommand($this->dm, $manager)))->execute([]));
    }

}
