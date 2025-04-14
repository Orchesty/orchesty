<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Manager;

use GuzzleHttp\Exception\GuzzleException;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Loader\ApplicationLoader;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookManager;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallFilter;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\System\PipesHeaders;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ApplicationManager
 *
 * @package Hanaboso\PipesPhpSdk\Application\Manager
 */
final class ApplicationManager
{

    public const string APPLICATION_SETTINGS = 'applicationSettings';

    /**
     * ApplicationManager constructor.
     *
     * @param ApplicationInstallRepository $applicationInstallRepository
     * @param ApplicationLoader            $loader
     * @param WebhookManager               $webhook
     */
    public function __construct(
        protected readonly ApplicationInstallRepository $applicationInstallRepository,
        protected ApplicationLoader $loader,
        private readonly WebhookManager $webhook,
    )
    {
    }

    /**
     * @return mixed[]
     */
    public function getApplications(): array
    {
        return $this->loader->getApplications();
    }

    /**
     * @param string $key
     *
     * @return ApplicationInterface
     * @throws ApplicationInstallException
     */
    public function getApplication(string $key): ApplicationInterface
    {
        return $this->loader->getApplication($key);
    }

    /**
     * @param string $key
     *
     * @return string[]
     * @throws ApplicationInstallException
     */
    public function getSynchronousActions(string $key): array
    {
        $actions    = [];
        $reflection = new ReflectionClass($this->getApplication($key));
        $methods    = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if ($this->isSynchronous($method)) {
                $actions = array_merge($actions, [$method->getName()]);
            }
        }

        return $actions;
    }

    /**
     * @param string  $key
     * @param string  $method
     * @param Request $request
     *
     * @return mixed
     * @throws ApplicationInstallException
     */
    public function runSynchronousAction(string $key, string $method, Request $request): mixed
    {
        $app = $this->getApplication($key);

        if (method_exists($app, $method)) {
            if ($request->getMethod() === CurlManager::METHOD_GET) {
                return $app->$method();
            }

            return $app->$method($request);
        }

        throw new ApplicationInstallException(
            sprintf('Method "%s" was not found for Application "%s".', $method, $key),
            ApplicationInstallException::METHOD_NOT_FOUND,
        );
    }

    /**
     * @param string  $key
     * @param string  $user
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws ApplicationInstallException
     * @throws GuzzleException
     */
    public function saveApplicationSettings(string $key, string $user, array $data): array
    {
        /** @var BasicApplicationInterface $application */
        $application        = $this->loader->getApplication($key);
        $applicationInstall = $this->applicationInstallRepository->findUserApp($key, $user);
        $res                = $application->saveApplicationForms($applicationInstall, $data)->toArray();
        $this->applicationInstallRepository->update($applicationInstall);

        return array_merge(
            $res,
            [self::APPLICATION_SETTINGS => $application->getApplicationForms($applicationInstall)],
        );
    }

    /**
     * @param string $key
     * @param string $user
     * @param string $formKey
     * @param string $fieldKey
     * @param string $password
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     * @throws GuzzleException
     */
    public function saveApplicationPassword(
        string $key,
        string $user,
        string $formKey,
        string $fieldKey,
        string $password,
    ): ApplicationInstall
    {
        $applicationInstall = $this->applicationInstallRepository->findUserApp($key, $user);

        /** @var BasicApplicationInterface $application */
        $application = $this->loader->getApplication($key);
        $application = $application->savePassword($applicationInstall, $formKey, $fieldKey, $password);
        $this->applicationInstallRepository->update($applicationInstall);

        return $application;
    }

    /**
     * @param string $key
     * @param string $user
     * @param string $redirectUrl
     *
     * @return string
     * @throws ApplicationInstallException
     * @throws GuzzleException
     */
    public function authorizeApplication(string $key, string $user, string $redirectUrl): string
    {
        $applicationInstall = $this->applicationInstallRepository->findUserApp($key, $user);

        /** @var OAuth1ApplicationInterface|OAuth2ApplicationInterface $application */
        $application = $this->loader->getApplication($key);
        $application->setFrontendRedirectUrl($applicationInstall, $redirectUrl);
        $this->applicationInstallRepository->update($applicationInstall);

        return $application->authorize($applicationInstall);
    }

    /**
     * @param string  $key
     * @param string  $user
     * @param mixed[] $token
     *
     * @return string
     * @throws ApplicationInstallException
     * @throws GuzzleException
     */
    public function saveAuthorizationToken(string $key, string $user, array $token): string
    {
        $applicationInstall = $this->applicationInstallRepository->findUserApp($key, $user);

        /** @var OAuth1ApplicationInterface|OAuth2ApplicationInterface $application */
        $application = $this->loader->getApplication($key);
        $application->setAuthorizationToken($applicationInstall, $token);
        $this->applicationInstallRepository->update($applicationInstall);

        return $application->getFrontendRedirectUrl($applicationInstall);
    }

    /**
     * @param string $user
     *
     * @return mixed[]
     * @throws GuzzleException
     */
    public function getInstalledApplications(string $user): array
    {
        return $this->applicationInstallRepository->findMany(new ApplicationInstallFilter(users: [$user]));
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     * @throws GuzzleException
     */
    public function getInstalledApplicationDetail(string $key, string $user): ApplicationInstall
    {
        return $this->applicationInstallRepository->findUserApp($key, $user);
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     * @throws GuzzleException
     */
    public function installApplication(string $key, string $user): ApplicationInstall
    {
        if ($this->applicationInstallRepository->findOne(new ApplicationInstallFilter(names: [$key], users: [$user]))) {
            throw new ApplicationInstallException(
                sprintf('Application [%s] was already installed.', $key),
                ApplicationInstallException::APP_ALREADY_INSTALLED,
            );
        }

        $applicationInstall = new ApplicationInstall();
        $applicationInstall
            ->setUser($user)
            ->setKey($key);
        $this->applicationInstallRepository->insert($applicationInstall);

        return $applicationInstall;
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws GuzzleException
     */
    public function uninstallApplication(string $key, string $user): ApplicationInstall
    {
        $applicationInstall = $this->applicationInstallRepository->findUserApp($key, $user);
        $this->unsubscribeWebhooks($applicationInstall);

        $this->applicationInstallRepository->remove($applicationInstall);

        return $applicationInstall;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param mixed[]            $data
     *
     * @return void
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws GuzzleException
     * @throws DateTimeException
     */
    public function subscribeWebhooks(ApplicationInstall $applicationInstall, array $data = []): void
    {
        /** @var WebhookApplicationInterface $application */
        $application = $this->loader->getApplication($applicationInstall->getKey() ?? '');

        if (ApplicationTypeEnum::isWebhook($application->getApplicationType()) &&
            $application->isAuthorized($applicationInstall)
        ) {
            $this->webhook->subscribeWebhooks($application, $applicationInstall->getUser() ?? '', $data);
        }
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param mixed[]            $data
     *
     * @return void
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws GuzzleException
     */
    public function unsubscribeWebhooks(ApplicationInstall $applicationInstall, array $data = []): void
    {
        /** @var WebhookApplicationInterface $application */
        $application = $this->loader->getApplication($applicationInstall->getKey() ?? '');

        if (ApplicationTypeEnum::isWebhook($application->getApplicationType()) &&
            $application->isAuthorized($applicationInstall)
        ) {
            $this->webhook->unsubscribeWebhooks($application, $applicationInstall->getUser() ?? '', $data);
        }
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return mixed[]
     * @throws ApplicationInstallException
     * @throws GuzzleException
     */
    public function getApplicationSettings(string $key, string $user): array
    {
        $applicationInstall = $this->applicationInstallRepository->findUserApp($key, $user);
        /** @var ApplicationAbstract $application */
        $application = $this->loader->getApplication($key);

        return $application->getApplicationForms($applicationInstall);
    }

    /**
     * @param string  $user
     * @param mixed[] $applications
     *
     * @return mixed[]
     * @throws GuzzleException
     */
    public function getApplicationsLimits(string $user, array $applications): array
    {
        $applicationInstalls = $this->applicationInstallRepository->findUserApps($user, $applications);

        $appLimits = array_map(static function(ApplicationInstall $appInstall) {
            /** @var Form|null $limiterForm */
            $limiterForm = $appInstall->getSettings()[ApplicationInterface::LIMITER_FORM] ?? NULL;
            if (!$limiterForm) {
                return NULL;
            }

            $fields = $limiterForm->getFields();

            $useLimit = $fields[ApplicationInterface::USE_LIMIT] ?? NULL;
            $time     = $fields[ApplicationInterface::TIME] ?? NULL;
            $value    = $fields[ApplicationInterface::VALUE] ?? NULL;

            $groupTime  = $fields[ApplicationInterface::GROUP_TIME] ?? NULL;
            $groupValue = $fields[ApplicationInterface::GROUP_VALUE] ?? NULL;

            if (!$useLimit || !$time || !$value) {
                return NULL;
            }

            if($groupTime && $groupValue){
                return PipesHeaders::getLimiterKeyWithGroup(
                    sprintf('%s|%s', $appInstall->getUser(), $appInstall->getKey()),
                    (int) $time->getValue(),
                    (int) $value->getValue(),
                    sprintf('|%s', $appInstall->getKey()),
                    (int) $groupTime->getValue(),
                    (int) $groupValue->getValue(),
                );
            }

            return PipesHeaders::getLimiterKey(
                sprintf('%s|%s', $appInstall->getUser(), $appInstall->getKey()),
                (int) $time->getValue(),
                (int) $value->getValue(),
            );
        }, $applicationInstalls);

        // @phpstan-ignore-next-line
        return array_filter($appLimits);
    }

    /**
     * @param string $key
     * @param string $user
     * @param bool   $enabled
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     * @throws GuzzleException
     */
    public function changeStateOfApplication(string $key, string $user, bool $enabled): ApplicationInstall
    {
        $applicationInstall = $this->applicationInstallRepository->findUserApp($key, $user);
        $applicationInstall->setEnabled($enabled);

        $this->applicationInstallRepository->update($applicationInstall);

        return $applicationInstall;
    }

    /**
     * @param ReflectionMethod $method
     *
     * @return bool
     */
    private function isSynchronous(ReflectionMethod $method): bool {
        $doc = $method->getDocComment();
        preg_match_all('#@SynchronousAction#s', $doc ?: '', $annotations);

        return $annotations[0] !== [];
    }

}
