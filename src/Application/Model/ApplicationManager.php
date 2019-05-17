<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Model;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Application\Base\ApplicationInterface;
use Hanaboso\PipesFramework\Application\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesFramework\Application\Base\OAuth1\OAuth1ApplicationInterface;
use Hanaboso\PipesFramework\Application\Base\OAuth2\OAuth2ApplicationInterface;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesFramework\Application\Model\Webhook\WebhookApplicationInterface;
use Hanaboso\PipesFramework\Application\Model\Webhook\WebhookManager;
use Hanaboso\PipesFramework\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesFramework\HbPFApplicationBundle\Loader\ApplicationLoader;

/**
 * Class ApplicationManager
 *
 * @package Hanaboso\PipesFramework\Application\Model
 */
class ApplicationManager
{

    /**
     * @var ApplicationLoader
     */
    private $loader;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var ObjectRepository|ApplicationInstallRepository
     */
    private $repository;

    /**
     * @var WebhookManager
     */
    private $webhook;

    /**
     * ApplicationManager constructor.
     *
     * @param DocumentManager   $dm
     * @param ApplicationLoader $loader
     * @param WebhookManager    $webhook
     */
    public function __construct(DocumentManager $dm, ApplicationLoader $loader, WebhookManager $webhook)
    {
        $this->loader     = $loader;
        $this->dm         = $dm;
        $this->webhook    = $webhook;
        $this->repository = $this->dm->getRepository(ApplicationInstall::class);
    }

    /**
     * @return array
     */
    public function getApplications(): array
    {
        return $this->loader->getApplications();
    }

    /**
     * @param string $key
     *
     * @return ApplicationInterface
     * @throws Exception
     */
    public function getApplication(string $key): ApplicationInterface
    {
        return $this->loader->getApplication($key);
    }

    /**
     * @param string $user
     *
     * @return array
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
     * @throws DateTimeException
     */
    public function installApplication(string $key, string $user): ApplicationInstall
    {
        if ($this->repository->findOneBy([ApplicationInstall::USER => $user, ApplicationInstall::KEY => $key])) {
            throw new ApplicationInstallException(
                sprintf('Application [%s] was already installed.', $key),
                ApplicationInstallException::APP_ALREADY_INSTALLED
            );
        }

        $applicationInstall = new ApplicationInstall();
        $applicationInstall
            ->setUser($user)
            ->setKey($key);
        $this->dm->persist($applicationInstall);
        $this->dm->flush($applicationInstall);

        return $applicationInstall;
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return ApplicationInstall
     * @throws Exception
     */
    public function uninstallApplication(string $key, string $user): ApplicationInstall
    {
        $applicationInstall = $this->repository->findUserApp($key, $user);
        $this->unsubscribeWebhooks($applicationInstall);

        $this->dm->remove($applicationInstall);
        $this->dm->flush($applicationInstall);

        return $applicationInstall;
    }

    /**
     * @param string $key
     * @param string $user
     * @param array  $data
     *
     * @return ApplicationInstall
     * @throws Exception
     */
    public function saveApplicationSettings(string $key, string $user, array $data): ApplicationInstall
    {
        $applicationInstall = $this->loader->getApplication($key)
            ->setApplicationSettings(
                $this->repository->findUserApp($key, $user),
                $data
            );
        $this->dm->flush($applicationInstall);

        return $applicationInstall;
    }

    /**
     * @param string $key
     * @param string $user
     * @param string $password
     *
     * @return ApplicationInstall
     * @throws Exception
     */
    public function saveApplicationPassword(string $key, string $user, string $password): ApplicationInstall
    {
        /** @var BasicApplicationInterface $application */
        $application        = $this->loader->getApplication($key);
        $applicationInstall = $this->repository->findUserApp($key, $user);
        $application->setApplicationPassword($applicationInstall, $password);
        $this->dm->flush($application);

        return $applicationInstall;
    }

    /**
     * @param string $key
     * @param string $user
     * @param string $redirectUrl
     *
     * @throws ApplicationInstallException
     */
    public function authorizeApplication(string $key, string $user, string $redirectUrl): void
    {
        $applicationInstall = $this->repository->findUserApp($key, $user);

        /** @var OAuth1ApplicationInterface|OAuth2ApplicationInterface $application */
        $application = $this->loader->getApplication($key);
        $application->setFrontendRedirectUrl($applicationInstall, $redirectUrl);
        $this->dm->flush();

        $application->authorize($applicationInstall);
    }

    /**
     * @param string $key
     * @param string $user
     * @param array  $token
     *
     * @return array
     * @throws ApplicationInstallException
     */
    public function saveAuthorizationToken(string $key, string $user, array $token): array
    {
        $applicationInstall = $this->repository->findUserApp($key, $user);

        /** @var OAuth1ApplicationInterface|OAuth2ApplicationInterface $application */
        $application = $this->loader->getApplication($key);
        $application->setAuthorizationToken($applicationInstall, $token);
        $this->dm->flush();

        return [ApplicationInterface::REDIRECT_URL => $application->getFrontendRedirectUrl($applicationInstall)];
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @throws ApplicationInstallException
     */
    public function subscribeWebhooks(ApplicationInstall $applicationInstall): void
    {
        /** @var WebhookApplicationInterface $application */
        $application = $this->loader->getApplication($applicationInstall->getKey());

        if (ApplicationTypeEnum::isWebhook($application->getApplicationType()) && $application->isAuthorized($applicationInstall)) {
            $this->webhook->subscribeWebhooks($application, $applicationInstall->getUser());
        }
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @throws ApplicationInstallException
     * @throws CurlException
     */
    public function unsubscribeWebhooks(ApplicationInstall $applicationInstall): void
    {
        /** @var WebhookApplicationInterface $application */
        $application = $this->loader->getApplication($applicationInstall->getKey());

        if (ApplicationTypeEnum::isWebhook($application->getApplicationType()) && $application->isAuthorized($applicationInstall)) {
            $this->webhook->unsubscribeWebhooks($application, $applicationInstall->getUser());
        }
    }

}
