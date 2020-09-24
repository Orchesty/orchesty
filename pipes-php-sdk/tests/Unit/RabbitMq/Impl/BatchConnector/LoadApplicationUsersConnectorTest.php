<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\RabbitMq\Impl\BatchConnector;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchTrait;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\BatchConnector\LoadApplicationUsersConnector;
use Hanaboso\Utils\System\PipesHeaders;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class LoadApplicationUsersConnectorTest
 *
 * @package PipesPhpSdkTests\Unit\RabbitMq\Impl\BatchConnector
 */
final class LoadApplicationUsersConnectorTest extends KernelTestCaseAbstract
{

    use BatchTrait;

    /**
     * @var callable
     */
    private $callback;

    /**
     *
     */
    public function setUp(): void
    {
        $this->callback = static function (): void {
            self::assertFake();
        };
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\BatchConnector\LoadApplicationUsersConnector::__construct()
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\BatchConnector\LoadApplicationUsersConnector::getId()
     *
     * @throws Exception
     */
    public function testGetId(): void
    {
        self::assertEquals('load-application-users.some-app-key', $this->createConnector()->getId());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\BatchConnector\LoadApplicationUsersConnector::processBatch()
     *
     * @throws Exception
     */
    public function testProcessBatch(): void
    {
        $dto = new ProcessDto();
        $dto->setHeaders([PipesHeaders::createKey(PipesHeaders::USER) => 'user']);

        $connector = $this->createConnector();
        $connector->processBatch($dto, $this->callback);
    }

    /**
     * ------------------------------------------- HELPERS ----------------------------------------
     */

    /**
     * @return LoadApplicationUsersConnector
     *
     * @throws Exception
     */
    private function createConnector(): LoadApplicationUsersConnector
    {
        $appInstall = new ApplicationInstall();
        $appInstall
            ->setKey('some-app-key')
            ->setUser('user');
        $this->setProperty($appInstall, 'id', 'id');

        $repository = self::createMock(ApplicationInstallRepository::class);
        $repository->method('findBy')->willReturn([$appInstall]);

        $dm = self::createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repository);

        $app = self::createMock(ApplicationInterface::class);
        $app->method('isAuthorized')->willReturn(TRUE);

        $connector = new LoadApplicationUsersConnector($dm, 'some-app-key');
        $connector->setApplication($app);

        return $connector;
    }

}
