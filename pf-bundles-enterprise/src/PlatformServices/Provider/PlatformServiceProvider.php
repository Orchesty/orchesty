<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\PlatformServices\Provider;

use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Document\ServiceBinding;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Exception\PlatformServiceException;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Repository\ServiceBindingRepository;
use Hanaboso\Utils\String\Json;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

/**
 * Class PlatformServiceProvider
 *
 * @package Hanaboso\PipesFrameworkEnterprise\PlatformServices\Provider
 */
final class PlatformServiceProvider
{

    /**
     * PlatformServiceProvider constructor.
     *
     * @param ServiceBindingRepository $bindingRepository
     * @param ServiceLocator           $serviceLocator
     */
    public function __construct(
        private readonly ServiceBindingRepository $bindingRepository,
        private readonly ServiceLocator $serviceLocator,
    )
    {
    }

    /**
     * Call a sync action on the platform-bound application.
     *
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
        $binding = $this->resolveBinding($serviceType);
        $appKey  = $binding->getApplicationKey();

        try {
            $sdk      = $this->serviceLocator->getSdkNameByInstalledApplication($appKey);
            $request  = new Request([], $data, [], [], [], ['REQUEST_METHOD' => 'POST']);
            $response = $this->serviceLocator->runSyncActions($request, $appKey, $sdk, $method);

            return Json::decode($response);
        } catch (Throwable $e) {
            throw new PlatformServiceException(
                sprintf('Platform service call failed [%s/%s]: %s', $serviceType, $method, $e->getMessage()),
                PlatformServiceException::CALL_FAILED,
                $e,
            );
        }
    }

    /**
     * @param string $serviceType
     *
     * @return bool
     */
    public function isConfigured(string $serviceType): bool
    {
        return $this->bindingRepository->findOneBy(
            [ServiceBinding::SERVICE_TYPE => $serviceType],
        ) !== NULL;
    }

    /**
     * @param string $serviceType
     *
     * @return ServiceBinding
     *
     * @throws PlatformServiceException
     */
    private function resolveBinding(string $serviceType): ServiceBinding
    {
        /** @var ServiceBinding|null $binding */
        $binding = $this->bindingRepository->findOneBy(
            [ServiceBinding::SERVICE_TYPE => $serviceType],
        );

        if ($binding === NULL) {
            throw new PlatformServiceException(
                sprintf('No application configured for platform service "%s"', $serviceType),
                PlatformServiceException::BINDING_NOT_FOUND,
            );
        }

        return $binding;
    }

}
