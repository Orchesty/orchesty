<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Manager;

use Doctrine\Common\Annotations\PsrCachedReader;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
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
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Application\Utils\SynchronousAction;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;
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

    public const APPLICATION_SETTINGS = 'applicationSettings';

    /**
     * @var ObjectRepository<ApplicationInstall>&ApplicationInstallRepository
     */
    private ApplicationInstallRepository $repository;

    /**
     * ApplicationManager constructor.
     *
     * @param DocumentManager   $dm
     * @param ApplicationLoader $loader
     * @param PsrCachedReader   $reader
     * @param WebhookManager    $webhook
     */
    public function __construct(
        protected DocumentManager $dm,
        protected ApplicationLoader $loader,
        private PsrCachedReader $reader,
        private WebhookManager $webhook,
    )
    {
        $this->repository = $this->dm->getRepository(ApplicationInstall::class);
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
            if ($this->reader->getMethodAnnotation($method, SynchronousAction::class)) {
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
     * @throws MongoDBException
     */
    public function saveApplicationSettings(string $key, string $user, array $data): array
    {
        /** @var BasicApplicationInterface $application */
        $application        = $this->loader->getApplication($key);
        $applicationInstall = $this->repository->findUserApp($key, $user);
        $res                = $application->saveApplicationForms($applicationInstall, $data)->toArray();
        $this->dm->flush();
        $this->dm->refresh($applicationInstall);

        return [
            ...$res,
            self::APPLICATION_SETTINGS => $application->getApplicationForms($applicationInstall),
        ];
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
     * @throws MongoDBException
     */
    public function saveApplicationPassword(
        string $key,
        string $user,
        string $formKey,
        string $fieldKey,
        string $password,
    ): ApplicationInstall
    {
        $applicationInstall = $this->repository->findUserApp($key, $user);

        /** @var BasicApplicationInterface $application */
        $application = $this->loader->getApplication($key);
        $application = $application->savePassword($applicationInstall, $formKey, $fieldKey, $password);
        $this->dm->flush();
        $this->dm->refresh($applicationInstall);

        return $application;
    }

    /**
     * @param string $key
     * @param string $user
     * @param string $redirectUrl
     *
     * @return string
     * @throws ApplicationInstallException
     * @throws MongoDBException
     */
    public function authorizeApplication(string $key, string $user, string $redirectUrl): string
    {
        $applicationInstall = $this->repository->findUserApp($key, $user);

        /** @var OAuth1ApplicationInterface|OAuth2ApplicationInterface $application */
        $application = $this->loader->getApplication($key);
        $application->setFrontendRedirectUrl($applicationInstall, $redirectUrl);
        $this->dm->flush();
        $this->dm->refresh($applicationInstall);

        return $application->authorize($applicationInstall);
    }

    /**
     * @param string  $key
     * @param string  $user
     * @param mixed[] $token
     *
     * @return string
     * @throws ApplicationInstallException
     * @throws MongoDBException
     */
    public function saveAuthorizationToken(string $key, string $user, array $token): string
    {
        $applicationInstall = $this->repository->findUserApp($key, $user);

        /** @var OAuth1ApplicationInterface|OAuth2ApplicationInterface $application */
        $application = $this->loader->getApplication($key);
        $application->setAuthorizationToken($applicationInstall, $token);
        $this->dm->flush();
        $this->dm->refresh($applicationInstall);

        return $application->getFrontendRedirectUrl($applicationInstall);
    }

    /**
     * @param string $user
     *
     * @return mixed[]
     */
    public function getInstalledApplications(string $user): array
    {
        return $this->repository->findBy([ApplicationInstall::USER => $user]);
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     */
    public function getInstalledApplicationDetail(string $key, string $user): ApplicationInstall
    {
        return $this->repository->findUserApp($key, $user);
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     * @throws MongoDBException
     */
    public function installApplication(string $key, string $user): ApplicationInstall
    {
        if ($this->repository->findOneBy([ApplicationInstall::USER => $user, ApplicationInstall::KEY => $key])) {
            throw new ApplicationInstallException(
                sprintf('Application [%s] was already installed.', $key),
                ApplicationInstallException::APP_ALREADY_INSTALLED,
            );
        }

        $applicationInstall = new ApplicationInstall();
        $applicationInstall
            ->setUser($user)
            ->setKey($key);
        $this->dm->persist($applicationInstall);
        $this->dm->flush();

        return $applicationInstall;
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     * @throws MongoDBException
     * @throws CurlException
     */
    public function uninstallApplication(string $key, string $user): ApplicationInstall
    {
        $applicationInstall = $this->repository->findUserApp($key, $user);
        $this->unsubscribeWebhooks($applicationInstall);

        $this->dm->remove($applicationInstall);
        $this->dm->flush();

        return $applicationInstall;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param mixed[]            $data
     *
     * @throws ApplicationInstallException
     * @throws MongoDBException
     * @throws CurlException
     */
    public function subscribeWebhooks(ApplicationInstall $applicationInstall, array $data = []): void
    {
        /** @var WebhookApplicationInterface $application */
        $application = $this->loader->getApplication($applicationInstall->getKey());

        if (ApplicationTypeEnum::isWebhook($application->getApplicationType()) &&
            $application->isAuthorized($applicationInstall)
        ) {
            $this->webhook->subscribeWebhooks($application, $applicationInstall->getUser(), $data);
        }
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param mixed[]            $data
     *
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws MongoDBException
     */
    public function unsubscribeWebhooks(ApplicationInstall $applicationInstall, array $data = []): void
    {
        /** @var WebhookApplicationInterface $application */
        $application = $this->loader->getApplication($applicationInstall->getKey());

        if (ApplicationTypeEnum::isWebhook($application->getApplicationType()) &&
            $application->isAuthorized($applicationInstall)
        ) {
            $this->webhook->unsubscribeWebhooks($application, $applicationInstall->getUser(), $data);
        }
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return mixed[]
     * @throws ApplicationInstallException
     */
    public function getApplicationSettings(string $key, string $user): array
    {
        $applicationInstall = $this->repository->findUserApp($key, $user);
        /** @var ApplicationAbstract $application */
        $application = $this->loader->getApplication($key);

        return $application->getApplicationForms($applicationInstall);
    }

    /**
     * @param string   $user
     * @param string[] $applications
     *
     * @return string[]
     */
    public function getApplicationsLimits(string $user, array $applications): array
    {
        $applicationInstalls = $this->repository->findUserApps($user, $applications);

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

        return array_filter($appLimits);
    }

    /**
     * @param string $key
     * @param string $user
     * @param bool   $enabled
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     * @throws MongoDBException
     */
    public function changeStateOfApplication(string $key, string $user, bool $enabled): ApplicationInstall
    {
        $applicationInstall = $this->repository->findUserApp($key, $user);
        $applicationInstall->setEnabled($enabled);

        $this->dm->flush();

        return $applicationInstall;
    }

}
