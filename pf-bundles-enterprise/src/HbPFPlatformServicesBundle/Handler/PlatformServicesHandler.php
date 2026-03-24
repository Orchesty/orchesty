<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFPlatformServicesBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Document\ServiceBinding;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Exception\PlatformServiceException;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Provider\PlatformServiceProvider;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Repository\ServiceBindingRepository;

/**
 * Class PlatformServicesHandler
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFPlatformServicesBundle\Handler
 */
final class PlatformServicesHandler
{

    /**
     * PlatformServicesHandler constructor.
     *
     * @param DocumentManager          $dm
     * @param ServiceBindingRepository $repository
     * @param PlatformServiceProvider  $provider
     */
    public function __construct(
        private readonly DocumentManager $dm,
        private readonly ServiceBindingRepository $repository,
        private readonly PlatformServiceProvider $provider,
    )
    {
    }

    /**
     * @return mixed[]
     */
    public function getBindings(): array
    {
        /** @var ServiceBinding[] $bindings */
        $bindings = $this->repository->findAll();

        return array_map(
            static fn(ServiceBinding $b): array => $b->toArray(),
            $bindings,
        );
    }

    /**
     * @param string $serviceType
     * @param string $applicationKey
     * @param string $user
     *
     * @return mixed[]
     *
     * @throws MongoDBException
     */
    public function setBinding(string $serviceType, string $applicationKey, string $user = 'system'): array
    {
        /** @var ServiceBinding|null $binding */
        $binding = $this->repository->findOneBy([ServiceBinding::SERVICE_TYPE => $serviceType]);

        if ($binding === NULL) {
            $binding = new ServiceBinding();
            $binding->setServiceType($serviceType);
            $this->dm->persist($binding);
        }

        $binding->setApplicationKey($applicationKey);
        $binding->setUser($user);

        $this->dm->flush();

        return $binding->toArray();
    }

    /**
     * @param string $serviceType
     *
     * @throws MongoDBException
     * @throws PlatformServiceException
     */
    public function removeBinding(string $serviceType): void
    {
        /** @var ServiceBinding|null $binding */
        $binding = $this->repository->findOneBy([ServiceBinding::SERVICE_TYPE => $serviceType]);

        if ($binding === NULL) {
            throw new PlatformServiceException(
                sprintf('Binding for service type "%s" not found', $serviceType),
                PlatformServiceException::BINDING_NOT_FOUND,
            );
        }

        $this->dm->remove($binding);
        $this->dm->flush();
    }

    /**
     * @param string  $serviceType
     * @param string  $method
     * @param mixed[] $data
     *
     * @return mixed[]
     *
     * @throws PlatformServiceException
     */
    public function call(string $serviceType, string $method, array $data = []): array
    {
        return $this->provider->call($serviceType, $method, $data);
    }

}
