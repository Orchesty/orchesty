<?php declare(strict_types=1);

namespace PipesFrameworkEnterpriseTests\Integration\PlatformServices;

use Exception;
use Hanaboso\PipesFrameworkEnterprise\HbPFPlatformServicesBundle\Handler\PlatformServicesHandler;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Document\ServiceBinding;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Repository\ServiceBindingRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkEnterpriseTests\DatabaseTestCaseAbstract;

/**
 * Class PlatformServicesHandlerTest
 *
 * @package PipesFrameworkEnterpriseTests\Integration\PlatformServices
 */
#[CoversClass(PlatformServicesHandler::class)]
final class PlatformServicesHandlerTest extends DatabaseTestCaseAbstract
{

    private const string SERVICE_TYPE = 'trace-ai-provider';

    /**
     * @var PlatformServicesHandler
     */
    private PlatformServicesHandler $handler;

    /**
     * @var ServiceBindingRepository
     */
    private ServiceBindingRepository $repository;

    /**
     * @throws Exception
     */
    public function testSetBindingPersistsSdk(): void
    {
        $result = $this->handler->setBinding(self::SERVICE_TYPE, 'open-ai', 'sys-worker');

        self::assertSame('open-ai', $result[ServiceBinding::APPLICATION_KEY]);
        self::assertSame('sys-worker', $result[ServiceBinding::SDK]);
        self::assertSame(self::SERVICE_TYPE, $result[ServiceBinding::SERVICE_TYPE]);

        $this->dm->clear();

        /** @var ServiceBinding|null $persisted */
        $persisted = $this->repository->findOneBy([ServiceBinding::SERVICE_TYPE => self::SERVICE_TYPE]);

        self::assertNotNull($persisted);
        self::assertSame('open-ai', $persisted->getApplicationKey());
        self::assertSame('sys-worker', $persisted->getSdk());
    }

    /**
     * @throws Exception
     */
    public function testSetBindingUpdatesExistingBindingWithNewSdk(): void
    {
        $this->handler->setBinding(self::SERVICE_TYPE, 'open-ai', 'sys-worker');
        $this->handler->setBinding(self::SERVICE_TYPE, 'z-ai', 'enterprise-sys-worker');

        $this->dm->clear();

        $bindings = $this->repository->findAll();
        self::assertCount(1, $bindings);

        /** @var ServiceBinding $binding */
        $binding = $bindings[0];
        self::assertSame('z-ai', $binding->getApplicationKey());
        self::assertSame('enterprise-sys-worker', $binding->getSdk());
    }

    /**
     * @throws Exception
     */
    public function testGetBindingsExposesSdk(): void
    {
        $this->handler->setBinding(self::SERVICE_TYPE, 'open-ai', 'sys-worker');

        $bindings = $this->handler->getBindings();

        self::assertCount(1, $bindings);
        self::assertSame('open-ai', $bindings[0][ServiceBinding::APPLICATION_KEY]);
        self::assertSame('sys-worker', $bindings[0][ServiceBinding::SDK]);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler    = self::getContainer()->get('hbpf.platform_services.handler');
        $this->repository = self::getContainer()->get('hbpf.platform_services.repository');
    }

}
