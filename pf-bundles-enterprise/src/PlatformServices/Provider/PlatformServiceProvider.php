<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\PlatformServices\Provider;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController;
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
     * @param DocumentManager          $dm
     * @param ServiceBindingRepository $bindingRepository
     * @param ServiceLocator           $serviceLocator
     */
    public function __construct(
        private readonly DocumentManager $dm,
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
        $sdk     = $this->resolveSdk($binding);

        try {
            $data['sdk'] = $sdk;

            // Platform services are system-wide: applications they call were installed via the
            // admin UI under ApplicationController::SYSTEM_USER. The caller's user (e.g. the chat
            // session owner) is irrelevant for the ApplicationInstall lookup on the worker, so we
            // override it here. The binding's `user` field stays as audit metadata only.
            $data['user'] = ApplicationController::SYSTEM_USER;

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
     * Resolve the SDK name for a binding.
     *
     * - If the binding has no sdk yet (legacy record), auto-discover one and persist it back.
     * - If the binding has an sdk but the application is no longer installed there, fail hard.
     *
     * @param ServiceBinding $binding
     *
     * @return string
     *
     * @throws PlatformServiceException
     */
    private function resolveSdk(ServiceBinding $binding): string
    {
        $appKey = $binding->getApplicationKey();
        $sdk    = $binding->getSdk();

        if ($sdk === NULL || $sdk === '') {
            $sdk = $this->serviceLocator->getSdkNameByInstalledApplication($appKey);
            $binding->setSdk($sdk);

            try {
                $this->dm->flush();
            } catch (MongoDBException) {
                // Backfill is best-effort; even if persistence fails the call below still works.
            }

            return $sdk;
        }

        if (!$this->serviceLocator->isApplicationInstalledOnSdk($appKey, $sdk)) {
            throw new PlatformServiceException(
                sprintf(
                    'Application "%s" is no longer installed on worker "%s" for platform service "%s". '
                    . 'Update the binding in Settings.',
                    $appKey,
                    $sdk,
                    $binding->getServiceType(),
                ),
                PlatformServiceException::CALL_FAILED,
            );
        }

        return $sdk;
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
