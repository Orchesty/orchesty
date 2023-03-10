<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Command;

use Exception;
use Hanaboso\PipesFramework\Configurator\Document\ApiToken;
use PipesFrameworkTests\DatabaseTestCaseAbstract;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class CreateApiTokenCommandTest
 *
 * @package PipesFrameworkTests\Integration\Command
 */
final class CreateApiTokenCommandTest extends DatabaseTestCaseAbstract
{

    /**
     * @return void
     * @throws Exception
     */
    public function testExecute(): void
    {
        $application = new Application(self::$kernel);
        $command     = $application->get('api-token:create');

        $commandTester = new CommandTester($command);

        $repository = self::getContainer()->get('hbpf.database_manager_locator')->getDm()?->getRepository(
            ApiToken::class,
        );

        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--user'  => '1',
                '--expireAt' => '2010-10-10',
            ],
        );

        /** @var ApiToken $apiToken */
        $apiToken = $repository?->findOneBy(['user' => '1']);
        self::assertStringContainsString(
            sprintf('New api token generated: %s', $apiToken->getKey()),
            $commandTester->getDisplay(),
        );

        $token = '1234-1234-1234-1234';
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--key'  => $token,
            ],
        );

        /** @var ApiToken $apiToken2 */
        $apiToken2 = $repository?->findOneBy(['key' => $token]);
        self::assertStringContainsString(
            sprintf('New api token generated: %s', $apiToken2->getKey()),
            $commandTester->getDisplay(),
        );

        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--key'  => $token,
            ],
        );

        /** @var ApiToken $apiToken2 */
        $apiToken2 = $repository?->findOneBy(['key' => $token]);
        self::assertStringContainsString(
            sprintf('Api token already exists: %s', $apiToken2->getKey()),
            $commandTester->getDisplay(),
        );
    }

}
