<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Command;

use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\InstallServiceCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class InstallServiceCommandTest
 *
 * @package PipesFrameworkTests\Integration\Command
 */
#[CoversClass(InstallServiceCommand::class)]
final class InstallServiceCommandTest extends DatabaseTestCaseAbstract
{

    /**
     * @return void
     */
    public function testExecute(): void
    {
        $commandTester = $this->createCommandTester();
        $commandTester->execute(
            [
                'command' => 'service:install',
                'name'    => 'http-sdk',
                'url'     => 'http-sdk:8080',
            ],
        );

        self::assertStringContainsString('Done!', $commandTester->getDisplay());
        self::assertSame(0, $commandTester->getStatusCode());

        /** @var Sdk|null $sdk */
        $sdk = $this->dm->getRepository(Sdk::class)->findOneBy([Sdk::NAME => 'http-sdk']);
        self::assertNotNull($sdk);
        self::assertSame(Sdk::TYPE_HTTP, $sdk->getType());
    }

    /**
     * @return void
     */
    public function testExecuteTunnel(): void
    {
        $commandTester = $this->createCommandTester();
        $commandTester->execute(
            [
                'command' => 'service:install',
                'name'    => 'node-sdk-tunnel',
                'type'    => Sdk::TYPE_TUNNEL,
                'url'     => '',
            ],
        );

        self::assertStringContainsString('Done!', $commandTester->getDisplay());
        self::assertSame(0, $commandTester->getStatusCode());

        /** @var Sdk|null $sdk */
        $sdk = $this->dm->getRepository(Sdk::class)->findOneBy([Sdk::NAME => 'node-sdk-tunnel']);
        self::assertNotNull($sdk);
        self::assertSame(Sdk::TYPE_TUNNEL, $sdk->getType());
        self::assertSame('', $sdk->getUrl());
    }

    /**
     * @return void
     */
    public function testExecuteInvalidType(): void
    {
        $commandTester = $this->createCommandTester();
        $commandTester->execute(
            [
                'command' => 'service:install',
                'name'    => 'bad-sdk',
                'type'    => 'bad-type',
                'url'     => 'bad-sdk:8080',
            ],
        );

        self::assertStringContainsString('Invalid type', $commandTester->getDisplay());
        self::assertSame(1, $commandTester->getStatusCode());
        self::assertCount(0, $this->dm->getRepository(Sdk::class)->findAll());
    }

    /**
     * @return CommandTester
     */
    private function createCommandTester(): CommandTester
    {
        /** @var KernelInterface $kernel */
        $kernel      = self::$kernel;
        $application = new Application($kernel);

        return new CommandTester($application->get('service:install'));
    }

}
